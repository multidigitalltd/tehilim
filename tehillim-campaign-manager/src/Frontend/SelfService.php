<?php
/**
 * Front-end self-service (create campaign, my campaigns, my activity).
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\StatsService;
use TCM\Support\Hebrew;
use TCM\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logged-in users can open a campaign, manage the ones they own, and see their
 * personal reading activity.
 */
final class SelfService implements Registerable {

	/**
	 * @var AssignmentsRepository
	 */
	private $assignments;

	/**
	 * @var StatsService
	 */
	private $stats;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->assignments = new AssignmentsRepository();
		$this->stats       = new StatsService( $this->assignments );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_shortcode( 'tehillim_create_campaign_form', array( $this, 'create_form' ) );
		add_shortcode( 'tehillim_my_campaigns', array( $this, 'my_campaigns' ) );
		add_shortcode( 'tehillim_my_activity', array( $this, 'my_activity' ) );
	}

	/**
	 * Render the create-campaign form.
	 *
	 * @return string
	 */
	public function create_form() {
		Assets::ensure();
		return Templating::render( 'partials/create-campaign', array() );
	}

	/**
	 * Render the owner's campaign management list.
	 *
	 * @return string
	 */
	public function my_campaigns() {
		Assets::ensure();
		if ( ! is_user_logged_in() ) {
			return Templating::render(
				'partials/my-campaigns',
				array(
					'logged_in' => false,
					'campaigns' => array(),
				)
			);
		}
		$query     = new \WP_Query(
			array(
				'post_type'      => CampaignPostType::POST_TYPE,
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => 50,
				'author'         => get_current_user_id(),
				'no_found_rows'  => true,
			)
		);
		$campaigns = array();
		foreach ( $query->posts as $post ) {
			$campaigns[] = array(
				'id'        => $post->ID,
				'title'     => get_the_title( $post ),
				'content'   => get_post_field( 'post_content', $post ),
				'permalink' => get_permalink( $post ),
				'stats'     => $this->stats->for_campaign( $post->ID ),
			);
		}
		return Templating::render(
			'partials/my-campaigns',
			array(
				'logged_in' => true,
				'campaigns' => $campaigns,
			)
		);
	}

	/**
	 * Render the user's reading activity.
	 *
	 * @return string
	 */
	public function my_activity() {
		if ( ! is_user_logged_in() ) {
			return Templating::render(
				'partials/my-activity',
				array(
					'logged_in' => false,
					'rows'      => array(),
				)
			);
		}
		$user = wp_get_current_user();
		$rows = array();
		if ( is_email( $user->user_email ) ) {
			foreach ( $this->assignments->by_participant_email( $user->user_email, 50 ) as $row ) {
				$rows[] = array(
					'campaign_title' => get_the_title( (int) $row->campaign_id ),
					'chapter'        => Hebrew::chapter_label( (int) $row->chapter_number ),
					'status'         => $row->status,
					'read_url'       => 'taken' === $row->status ? Urls::read( (int) $row->campaign_id, (int) $row->id, (string) $row->token ) : '',
					'permalink'      => get_permalink( (int) $row->campaign_id ),
				);
			}
		}
		return Templating::render(
			'partials/my-activity',
			array(
				'logged_in' => true,
				'rows'      => $rows,
			)
		);
	}
}
