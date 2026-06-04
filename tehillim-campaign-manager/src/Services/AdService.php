<?php
/**
 * Advertising service.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Database\AdStatsRepository;
use TCM\Frontend\Templating;
use TCM\PostTypes\AdPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Selects and renders ads for a placement zone, recording impressions/clicks.
 *
 * The chapter/prayer reading area is intentionally NOT a zone — ads attach
 * around the experience, never inside it (see docs/UPGRADE-PLAN.md §9.0).
 */
final class AdService {

	/**
	 * Allowed placement zones (the reader is deliberately excluded).
	 *
	 * @var string[]
	 */
	const ZONES = array(
		'archive_top',
		'archive_inline',
		'campaign_header',
		'before_join',
		'after_join',
		'pre_reading',
		'post_done',
		'completion',
		'share_screen',
		'sidebar',
		'email_footer',
	);

	/**
	 * @var AdStatsRepository
	 */
	private $stats;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->stats = new AdStatsRepository();
	}

	/**
	 * Render an ad for a zone (or '' when none is eligible).
	 *
	 * @param string $zone Zone key.
	 * @return string
	 */
	public function render_zone( $zone ) {
		$zone = sanitize_key( $zone );
		if ( ! in_array( $zone, self::ZONES, true ) ) {
			return '';
		}
		$ad = $this->pick( $zone );
		if ( ! $ad ) {
			return '';
		}

		$this->stats->increment( (int) $ad->ID, $zone, 'impressions' );

		return Templating::render(
			'partials/ad',
			array(
				'image'     => get_post_meta( $ad->ID, '_tcm_ad_image', true ),
				'title'     => get_the_title( $ad ),
				'click_url' => add_query_arg(
					array(
						'tcm_ad_click' => (int) $ad->ID,
						'zone'         => $zone,
					),
					home_url( '/' )
				),
			)
		);
	}

	/**
	 * Record a click and return the (validated) destination URL.
	 *
	 * @param int    $ad_id Ad id.
	 * @param string $zone  Zone.
	 * @return string Destination URL or '' when invalid.
	 */
	public function record_click( $ad_id, $zone ) {
		$ad_id = absint( $ad_id );
		$zone  = sanitize_key( $zone );
		if ( ! $ad_id || AdPostType::POST_TYPE !== get_post_type( $ad_id ) ) {
			return '';
		}
		$target = (string) get_post_meta( $ad_id, '_tcm_ad_url', true );
		if ( ! preg_match( '#^https?://#i', $target ) ) {
			return '';
		}
		$this->stats->increment( $ad_id, $zone, 'clicks' );
		return $target;
	}

	/**
	 * Pick one eligible ad for a zone (random rotation).
	 *
	 * @param string $zone Zone.
	 * @return \WP_Post|null
	 */
	private function pick( $zone ) {
		$today = current_time( 'Y-m-d' );
		$query = new \WP_Query(
			array(
				'post_type'      => AdPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'rand',
				'no_found_rows'  => true,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_tcm_ad_zone',
						'value' => $zone,
					),
					array(
						'key'   => '_tcm_ad_active',
						'value' => '1',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_tcm_ad_start',
							'value'   => '',
							'compare' => '=',
						),
						array(
							'key'     => '_tcm_ad_start',
							'value'   => $today,
							'compare' => '<=',
							'type'    => 'DATE',
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_tcm_ad_end',
							'value'   => '',
							'compare' => '=',
						),
						array(
							'key'     => '_tcm_ad_end',
							'value'   => $today,
							'compare' => '>=',
							'type'    => 'DATE',
						),
					),
				),
			)
		);
		return $query->have_posts() ? $query->posts[0] : null;
	}
}
