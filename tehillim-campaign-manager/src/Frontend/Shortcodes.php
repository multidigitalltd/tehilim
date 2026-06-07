<?php
/**
 * Shortcodes.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\ChapterTextService;
use TCM\Services\RoundService;
use TCM\Services\StatsService;
use TCM\Support\Tokens;
use TCM\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the public shortcodes through a single registry (with aliases) and
 * auto-renders the full campaign on a singular campaign page.
 */
final class Shortcodes implements Registerable {

	/**
	 * @var AssignmentsRepository
	 */
	private $assignments;

	/**
	 * @var StatsService
	 */
	private $stats;

	/**
	 * @var RoundService
	 */
	private $rounds;

	/**
	 * @var ChapterTextService
	 */
	private $chapter_text;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->assignments  = new AssignmentsRepository();
		$this->rounds       = new RoundService( $this->assignments );
		$this->stats        = new StatsService( $this->assignments, $this->rounds );
		$this->chapter_text = new ChapterTextService();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		$map = array(
			'tehillim_campaign'  => 'campaign',
			'tehillim_campaigns' => 'campaigns',
			'tehillim_join_form' => 'join_form',
			'tehillim_join'      => 'join_form',
			'tehillim_chapters'  => 'chapters',
			'tehillim_progress'  => 'progress',
		);
		foreach ( $map as $tag => $method ) {
			add_shortcode( $tag, array( $this, $method ) );
		}
		add_filter( 'the_content', array( $this, 'auto_content' ) );
	}

	/**
	 * Auto-render the campaign on its singular page.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function auto_content( $content ) {
		if ( ! is_singular( CampaignPostType::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		// If the author built a custom layout with Tehillim blocks/shortcodes,
		// respect it instead of replacing the page with the default campaign view.
		$raw = (string) get_post_field( 'post_content', get_the_ID() );
		if ( false !== strpos( $raw, 'wp:tehillim/' ) || false !== strpos( $raw, '[tehillim_' ) ) {
			return $content;
		}
		return $this->campaign( array( 'id' => get_the_ID() ) );
	}

	/**
	 * Full campaign page.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function campaign( $atts ) {
		$id = $this->resolve_id( $atts );
		if ( ! $id ) {
			return '';
		}
		Assets::ensure();
		return '<div class="tcm-wrap" id="tcm">'
			. $this->notice_html()
			. $this->progress_card( $id )
			. '<div class="tcm-campaign-layout">'
			. '<div class="tcm-campaign-main">'
			. $this->reader_card( $id )
			. do_shortcode( '[tehillim_ad slot="campaign_header"]' )
			. $this->join_card( $id )
			. do_shortcode( '[tehillim_ad slot="after_join"]' )
			. $this->chapters_card( $id )
			. do_shortcode( '[tehillim_ambassador_invite id="' . (int) $id . '"]' )
			. do_shortcode( '[tehillim_activity id="' . (int) $id . '"]' )
			. '</div>'
			. '<aside class="tcm-campaign-rail">'
			. $this->share_card( $id )
			. do_shortcode( '[tehillim_leaderboard id="' . (int) $id . '"]' )
			. '</aside>'
			. '</div>'
			. '</div>';
	}

	/**
	 * Campaign archive.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function campaigns( $atts ) {
		Assets::ensure();
		$query     = new \WP_Query(
			array(
				'post_type'      => CampaignPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'no_found_rows'  => true,
			)
		);
		$campaigns = array();
		foreach ( $query->posts as $post ) {
			$campaigns[] = array(
				'id'        => $post->ID,
				'title'     => get_the_title( $post ),
				'permalink' => get_permalink( $post ),
				'excerpt'   => wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post ) ), 22 ),
				'thumb'     => get_the_post_thumbnail_url( $post, 'large' ),
				'stats'     => $this->stats->for_campaign( $post->ID ),
			);
		}
		return do_shortcode( '[tehillim_ad slot="archive_top"]' )
			. Templating::render( 'campaigns-archive', array( 'campaigns' => $campaigns ) );
	}

	/**
	 * Standalone progress header.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function progress( $atts ) {
		$id = $this->resolve_id( $atts );
		return $id ? '<div class="tcm-wrap">' . $this->progress_card( $id ) . '</div>' : '';
	}

	/**
	 * Standalone join form.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function join_form( $atts ) {
		$id = $this->resolve_id( $atts );
		if ( ! $id ) {
			return '';
		}
		Assets::ensure();
		return '<div class="tcm-wrap">' . $this->join_card( $id ) . '</div>';
	}

	/**
	 * Standalone chapter grid.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function chapters( $atts ) {
		$id = $this->resolve_id( $atts );
		return $id ? '<div class="tcm-wrap">' . $this->chapters_card( $id ) . '</div>' : '';
	}

	/**
	 * Header card.
	 *
	 * @param int $id Campaign id.
	 * @return string
	 */
	private function progress_card( $id ) {
		$stats = $this->stats->for_campaign( $id );
		return Templating::render(
			'partials/progress',
			array(
				'title'        => get_the_title( $id ),
				'dedicated_to' => (string) get_post_meta( $id, '_tcm_dedicated_to', true ),
				'image'        => (string) get_the_post_thumbnail_url( $id, 'large' ),
				'description'  => wpautop( get_post_field( 'post_content', $id ) ),
				'stats'        => $stats,
				'participants' => $this->assignments->participant_count( $id ),
				'ambassadors'  => ( new \TCM\Database\AmbassadorsRepository() )->count_for_campaign( $id ),
			)
		);
	}

	/**
	 * Share card for the right rail (WhatsApp + copy link).
	 *
	 * @param int $id Campaign id.
	 * @return string
	 */
	private function share_card( $id ) {
		return Templating::render(
			'partials/share',
			array(
				'permalink' => (string) get_permalink( $id ),
				'title'     => get_the_title( $id ),
			)
		);
	}

	/**
	 * Join card.
	 *
	 * @param int $id Campaign id.
	 * @return string
	 */
	private function join_card( $id ) {
		$status = get_post_meta( $id, '_tcm_status', true );
		$status = $status ? $status : 'active';
		if ( 'active' !== $status ) {
			return '<div class="tcm-card">' . esc_html__( 'This campaign is not currently active.', 'tehillim-campaign-manager' ) . '</div>';
		}

		$options = get_option( 'tcm_options', array() );
		$stats   = $this->stats->for_campaign( $id );
		$free    = $this->assignments->free_chapters( $id, $stats['round'] );

		$multi_options = array_filter( array_map( 'absint', explode( ',', (string) ( $options['multi_chapter_options'] ?? '3,5,10' ) ) ) );
		$allow_full    = ( '0' !== ( $options['allow_full_book'] ?? '1' ) )
			&& $this->rounds->find_empty_full_book_round( $id, (int) $stats['goal_total'] ) > 0;

		$site_key = isset( $options['turnstile_site_key'] ) ? trim( (string) $options['turnstile_site_key'] ) : '';
		if ( $site_key ) {
			wp_enqueue_script( 'tcm-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}

		return Templating::render(
			'partials/join-form',
			array(
				'campaign_id'   => (int) $id,
				'permalink'     => get_permalink( $id ),
				'free'          => $free,
				'allow_multi'   => '0' !== ( $options['allow_multi_chapters'] ?? '1' ),
				'multi_options' => $multi_options ? $multi_options : array( 3, 5, 10 ),
				'allow_full'    => $allow_full,
				'site_key'      => $site_key,
				'join_title'    => $options['join_title'] ?? __( 'Join the reading', 'tehillim-campaign-manager' ),
				'button_text'   => $options['join_button_text'] ?? __( 'Join the reading', 'tehillim-campaign-manager' ),
			)
		);
	}

	/**
	 * Chapter grid card.
	 *
	 * @param int $id Campaign id.
	 * @return string
	 */
	private function chapters_card( $id ) {
		$round = $this->rounds->current_round( $id );
		return Templating::render(
			'partials/chapters-grid',
			array( 'rows' => $this->assignments->round_rows( $id, $round ) )
		);
	}

	/**
	 * Reader card, only when a valid ?tcm_read + token is present.
	 *
	 * @param int $id Campaign id.
	 * @return string
	 */
	private function reader_card( $id ) {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- token-authorised read, not a state change.
		if ( empty( $_GET['tcm_read'] ) || empty( $_GET['token'] ) ) {
			return '';
		}
		$assignment_id = absint( $_GET['tcm_read'] );
		$token         = sanitize_text_field( wp_unslash( $_GET['token'] ) );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

		$row = $this->assignments->find( $assignment_id );
		if ( ! $row || (int) $row->campaign_id !== (int) $id || ! Tokens::verify( (string) $row->token, $token ) ) {
			return '<div class="tcm-card" id="tcm-read">' . esc_html__( 'This reading link is not valid.', 'tehillim-campaign-manager' ) . '</div>';
		}

		return Templating::render(
			'partials/reader',
			array(
				'campaign_id' => (int) $id,
				'permalink'   => get_permalink( $id ),
				'row'         => $row,
				'siblings'    => $this->assignments->claim_siblings( $id, (int) $row->round_number, $token ),
				'text'        => $this->chapter_text->get( (int) $row->chapter_number ),
				'token'       => $token,
			)
		);
	}

	/**
	 * Render a status notice from the tcm_msg query arg, if present.
	 *
	 * @return string
	 */
	private function notice_html() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display.
		if ( empty( $_GET['tcm_msg'] ) ) {
			return '';
		}
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key      = sanitize_key( wp_unslash( $_GET['tcm_msg'] ) );
		$messages = array(
			'done'     => __( 'Thank you! The chapter was marked as completed.', 'tehillim-campaign-manager' ),
			'done_all' => __( 'Thank you! All your chapters were marked as completed.', 'tehillim-campaign-manager' ),
			'taken'    => __( 'That chapter was just taken. Please choose another.', 'tehillim-campaign-manager' ),
			'full'     => __( 'All chapters in the current book are taken. Please check back soon.', 'tehillim-campaign-manager' ),
			'released' => __( 'The chapter was released and is available again.', 'tehillim-campaign-manager' ),
		);
		if ( empty( $messages[ $key ] ) ) {
			return '';
		}
		return '<div class="tcm-notice" role="status">' . esc_html( $messages[ $key ] ) . '</div>';
	}

	/**
	 * Resolve the campaign id from attributes or context.
	 *
	 * @param array $atts Attributes.
	 * @return int
	 */
	private function resolve_id( $atts ) {
		$id = is_array( $atts ) && ! empty( $atts['id'] ) ? absint( $atts['id'] ) : 0;
		if ( ! $id && CampaignPostType::POST_TYPE === get_post_type( get_the_ID() ) ) {
			$id = (int) get_the_ID();
		}
		if ( $id && CampaignPostType::POST_TYPE !== get_post_type( $id ) ) {
			return 0;
		}
		return $id;
	}
}
