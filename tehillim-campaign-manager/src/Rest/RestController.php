<?php
/**
 * REST API controller.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Rest;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\AssignmentService;
use TCM\Services\CampaignService;
use TCM\Services\OwnerService;
use TCM\Services\StatsService;
use TCM\Services\SubscriptionService;
use TCM\Support\Logger;
use TCM\Support\RateLimiter;
use TCM\Support\Tokens;
use TCM\Support\Turnstile;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public REST endpoints (namespace `tcm/v1`).
 *
 * Action endpoints are authorised by the per-assignment token (validated in the
 * service in constant time). The join endpoint is gated by rate limiting and
 * Cloudflare Turnstile. All input is validated/sanitised on the way in, and
 * failures return a safe message + reference id — never an internal trace.
 */
final class RestController implements Registerable {

	const NS = 'tcm/v1';

	/**
	 * @var AssignmentService
	 */
	private $assignments;

	/**
	 * @var CampaignService
	 */
	private $campaigns;

	/**
	 * @var StatsService
	 */
	private $stats;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->assignments = new AssignmentService();
		$this->campaigns   = new CampaignService();
		$this->stats       = new StatsService();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/campaigns',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_campaigns' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/campaigns/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_campaign' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'id' => $this->id_arg() ),
			)
		);

		register_rest_route(
			self::NS,
			'/campaigns/(?P<id>\d+)/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'status' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'id' => $this->id_arg() ),
			)
		);

		register_rest_route(
			self::NS,
			'/campaigns/(?P<id>\d+)/join',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'join' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'id' => $this->id_arg() ),
			)
		);

		register_rest_route(
			self::NS,
			'/campaigns',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_campaign' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		foreach ( array( 'update', 'bonus' ) as $owner_action ) {
			register_rest_route(
				self::NS,
				'/campaigns/(?P<id>\d+)/' . $owner_action,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'owner_action' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'id'     => $this->id_arg(),
						'action' => array( 'default' => $owner_action ),
					),
				)
			);
		}

		register_rest_route(
			self::NS,
			'/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'subscribe' ),
				'permission_callback' => '__return_true',
			)
		);

		foreach ( array( 'done', 'take-more', 'release' ) as $action ) {
			register_rest_route(
				self::NS,
				'/assignments/(?P<id>\d+)/' . $action,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'assignment_action' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'id'     => $this->id_arg(),
						'token'  => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'action' => array( 'default' => $action ),
					),
				)
			);
		}
	}

	/**
	 * GET a list of published campaigns (headless/JSON, no PII).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_campaigns( WP_REST_Request $request ) {
		$per_page = $request->get_param( 'per_page' ) ? min( 100, absint( $request->get_param( 'per_page' ) ) ) : 50;
		$query    = new \WP_Query(
			array(
				'post_type'      => CampaignPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, $per_page ),
				'no_found_rows'  => true,
			)
		);
		$items    = array();
		foreach ( $query->posts as $post ) {
			$items[] = $this->shape_campaign( $post );
		}
		return new WP_REST_Response( $items, 200 );
	}

	/**
	 * GET a single campaign (headless/JSON, no PII).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_campaign( WP_REST_Request $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || CampaignPostType::POST_TYPE !== get_post_type( $post ) || 'publish' !== get_post_status( $post ) ) {
			return $this->error( 'not_found', __( 'Campaign not found.', 'tehillim-campaign-manager' ), 404 );
		}
		return new WP_REST_Response( $this->shape_campaign( $post ), 200 );
	}

	/**
	 * Shape a campaign post into the public JSON contract consumed by a headless
	 * front-end (matches the Lovable client's campaign shape).
	 *
	 * @param \WP_Post $post Campaign post.
	 * @return array<string,mixed>
	 */
	private function shape_campaign( $post ) {
		$id    = (int) $post->ID;
		$stats = $this->stats->for_campaign( $id );
		$image = get_the_post_thumbnail_url( $post, 'large' );

		return array(
			'id'            => $id,
			'slug'          => $post->post_name,
			'title'         => get_the_title( $post ),
			'dedicated_to'  => get_post_meta( $id, '_tcm_dedicated_to', true ) ? (string) get_post_meta( $id, '_tcm_dedicated_to', true ) : get_the_title( $post ),
			'purpose'       => wp_strip_all_tags( (string) get_the_excerpt( $post ) ),
			'description'   => wp_strip_all_tags( (string) get_post_field( 'post_content', $post ) ),
			'goal_books'    => (int) $stats['goal_total'],
			'goal_chapters' => (int) $stats['goal_total'] * 150,
			'image_url'     => $image ? $image : null,
			'permalink'     => get_permalink( $post ),
			'status'        => get_post_meta( $id, '_tcm_status', true ) ? get_post_meta( $id, '_tcm_status', true ) : 'active',
			'stats'         => array(
				'books'        => (int) $stats['completed_books'],
				'chapters'     => (int) $stats['total_done'],
				'participants' => ( new AssignmentsRepository() )->participant_count( $id ),
			),
		);
	}

	/**
	 * GET campaign status (public, no PII).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function status( WP_REST_Request $request ) {
		$id = (int) $request['id'];
		if ( CampaignPostType::POST_TYPE !== get_post_type( $id ) ) {
			return $this->error( 'not_found', __( 'Campaign not found.', 'tehillim-campaign-manager' ), 404 );
		}
		$stats = $this->stats->for_campaign( $id );
		return new WP_REST_Response(
			array(
				'percent'         => $stats['percent'],
				'completed_books' => $stats['completed_books'],
				'goal_total'      => $stats['goal_total'],
				'round'           => $stats['round'],
				'round_free'      => $stats['round_free'],
			),
			200
		);
	}

	/**
	 * POST join — claim chapter(s) or a full book.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function join( WP_REST_Request $request ) {
		$campaign_id = (int) $request['id'];

		if ( RateLimiter::exceeded( 'join', 15, 60 ) ) {
			return $this->error( 'rate_limited', __( 'Too many attempts. Please try again shortly.', 'tehillim-campaign-manager' ), 429 );
		}
		if ( CampaignPostType::POST_TYPE !== get_post_type( $campaign_id ) ) {
			return $this->error( 'not_found', __( 'Campaign not found.', 'tehillim-campaign-manager' ), 404 );
		}
		$status = get_post_meta( $campaign_id, '_tcm_status', true );
		$status = $status ? $status : 'active';
		if ( 'active' !== $status ) {
			return $this->error( 'inactive', __( 'This campaign is not currently active.', 'tehillim-campaign-manager' ), 409 );
		}
		if ( ! Turnstile::verify( (string) $request->get_param( 'turnstile' ) ) ) {
			return $this->error( 'verification_failed', __( 'Security verification failed. Please try again.', 'tehillim-campaign-manager' ), 400 );
		}

		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		if ( '' !== $email && ! is_email( $email ) ) {
			return $this->error( 'bad_email', __( 'The email address is invalid.', 'tehillim-campaign-manager' ), 400 );
		}
		$participant = array(
			'name'  => sanitize_text_field( (string) $request->get_param( 'name' ) ),
			'email' => $email,
			'phone' => sanitize_text_field( (string) $request->get_param( 'phone' ) ),
		);

		$mode  = sanitize_key( (string) $request->get_param( 'mode' ) );
		$mode  = $mode ? $mode : 'single';
		$token = Tokens::generate();

		try {
			if ( 'book' === $mode ) {
				$stats = $this->stats->for_campaign( $campaign_id );
				$rows  = $this->campaigns->claim_full_book( $campaign_id, (int) $stats['goal_total'], $participant, $token );
			} elseif ( 'multi' === $mode ) {
				$count = max( 2, min( 150, absint( $request->get_param( 'count' ) ) ) );
				$rows  = $this->campaigns->claim_chapters( $campaign_id, $count, $participant, $token );
			} else {
				$chapter = absint( $request->get_param( 'chapter' ) );
				$rows    = $this->campaigns->claim_chapters( $campaign_id, 1, $participant, $token, $chapter );
			}
		} catch ( \Throwable $e ) {
			return $this->fail(
				$e,
				'join_failed',
				array(
					'campaign_id' => $campaign_id,
					'mode'        => $mode,
				)
			);
		}

		if ( ! $rows ) {
			return $this->error( 'unavailable', __( 'Those chapters are no longer available. Please choose again.', 'tehillim-campaign-manager' ), 409 );
		}

		$first = $rows[0];

		/** Fires after one or more chapters are successfully claimed. */
		do_action( 'tcm_chapter_claimed', $campaign_id, $rows, $token, $participant );

		return new WP_REST_Response(
			array(
				'ok'            => true,
				'assignment_id' => (int) $first->id,
				'token'         => $token,
				'count'         => count( $rows ),
				'chapters'      => array_map(
					static function ( $r ) {
						return (int) $r->chapter_number;
					},
					$rows
				),
			),
			201
		);
	}

	/**
	 * POST create a campaign (logged-in users).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_campaign( WP_REST_Request $request ) {
		if ( RateLimiter::exceeded( 'create_campaign', 10, 300 ) ) {
			return $this->error( 'rate_limited', __( 'Too many attempts. Please try again shortly.', 'tehillim-campaign-manager' ), 429 );
		}
		try {
			$result = ( new OwnerService() )->create(
				array(
					'title'        => (string) $request->get_param( 'title' ),
					'content'      => (string) $request->get_param( 'content' ),
					'target'       => absint( $request->get_param( 'target' ) ),
					'bonus'        => absint( $request->get_param( 'bonus' ) ),
					'dedicated_to' => (string) $request->get_param( 'dedicated_to' ),
				),
				get_current_user_id()
			);
		} catch ( \Throwable $e ) {
			return $this->fail( $e, 'create_campaign_failed', array() );
		}
		if ( empty( $result['ok'] ) ) {
			return $this->error( 'create_invalid', __( 'Please provide a dedication / title.', 'tehillim-campaign-manager' ), 400 );
		}
		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * POST owner update / add-bonus.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function owner_action( WP_REST_Request $request ) {
		$id      = (int) $request['id'];
		$action  = sanitize_key( (string) $request->get_param( 'action' ) );
		$service = new OwnerService();
		$user    = get_current_user_id();

		try {
			if ( 'bonus' === $action ) {
				$result = $service->add_bonus( $id, $user );
			} else {
				$result = $service->update(
					$id,
					array(
						'title'   => (string) $request->get_param( 'title' ),
						'content' => (string) $request->get_param( 'content' ),
						'target'  => absint( $request->get_param( 'target' ) ),
					),
					$user
				);
			}
		} catch ( \Throwable $e ) {
			return $this->fail(
				$e,
				'owner_action_failed',
				array(
					'campaign_id' => $id,
					'action'      => $action,
				)
			);
		}

		if ( empty( $result['ok'] ) ) {
			$code = ( $result['code'] ?? '' ) === 'forbidden' ? 403 : 400;
			return $this->error( 'owner_invalid', __( 'The action could not be completed.', 'tehillim-campaign-manager' ), $code );
		}
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * POST subscribe to a list (e.g. daily chapter).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function subscribe( WP_REST_Request $request ) {
		if ( RateLimiter::exceeded( 'subscribe', 10, 60 ) ) {
			return $this->error( 'rate_limited', __( 'Too many attempts. Please try again shortly.', 'tehillim-campaign-manager' ), 429 );
		}

		$service = new SubscriptionService();
		try {
			$result = $service->subscribe(
				(string) $request->get_param( 'list' ),
				array(
					'name'    => sanitize_text_field( (string) $request->get_param( 'name' ) ),
					'email'   => sanitize_email( (string) $request->get_param( 'email' ) ),
					'phone'   => sanitize_text_field( (string) $request->get_param( 'phone' ) ),
					'channel' => sanitize_key( (string) $request->get_param( 'channel' ) ),
					'consent' => (bool) $request->get_param( 'consent' ),
				)
			);
		} catch ( \Throwable $e ) {
			return $this->fail( $e, 'subscribe_failed', array( 'list' => (string) $request->get_param( 'list' ) ) );
		}

		if ( empty( $result['ok'] ) ) {
			return $this->error( 'subscribe_invalid', __( 'Please check the form and try again.', 'tehillim-campaign-manager' ), 400 );
		}
		return new WP_REST_Response( array( 'ok' => true ), 201 );
	}

	/**
	 * POST done / take-more / release.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function assignment_action( WP_REST_Request $request ) {
		$id     = (int) $request['id'];
		$token  = (string) $request->get_param( 'token' );
		$action = sanitize_key( (string) $request->get_param( 'action' ) );

		try {
			switch ( $action ) {
				case 'take-more':
					$result = $this->assignments->take_more( $id, $token );
					break;
				case 'release':
					$result = $this->assignments->release( $id, $token );
					break;
				default:
					$result = $this->assignments->mark_done( $id, $token );
			}
		} catch ( \Throwable $e ) {
			return $this->fail(
				$e,
				'assignment_action_failed',
				array(
					'assignment_id' => $id,
					'action'        => $action,
				)
			);
		}

		if ( empty( $result['ok'] ) ) {
			return $this->error( 'invalid_link', __( 'This link is not valid.', 'tehillim-campaign-manager' ), 403 );
		}
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Shared `id` argument schema.
	 *
	 * @return array<string,mixed>
	 */
	private function id_arg() {
		return array(
			'required'          => true,
			'validate_callback' => static function ( $value ) {
				return is_numeric( $value ) && (int) $value > 0;
			},
			'sanitize_callback' => 'absint',
		);
	}

	/**
	 * Build a safe WP_Error.
	 *
	 * @param string $code    Machine code.
	 * @param string $message Human message.
	 * @param int    $http    HTTP status.
	 * @return WP_Error
	 */
	private function error( $code, $message, $http ) {
		return new WP_Error( 'tcm_' . $code, $message, array( 'status' => $http ) );
	}

	/**
	 * Log an exception fully and return a safe, opaque error (DO/LOG/SHOW).
	 *
	 * @param \Throwable          $e       Exception.
	 * @param string              $event   Event key.
	 * @param array<string,mixed> $context Context.
	 * @return WP_Error
	 */
	private function fail( \Throwable $e, $event, array $context ) {
		$ref              = substr( md5( uniqid( '', true ) ), 0, 8 );
		$context['ref']   = $ref;
		$context['error'] = $e->getMessage();
		Logger::log( Logger::ERROR, $event, $context );

		return new WP_Error(
			'tcm_server_error',
			sprintf(
				/* translators: %s: support reference id. */
				__( 'Something went wrong. Reference: %s', 'tehillim-campaign-manager' ),
				$ref
			),
			array( 'status' => 500 )
		);
	}
}
