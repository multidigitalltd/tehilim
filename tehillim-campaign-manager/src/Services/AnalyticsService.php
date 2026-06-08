<?php
/**
 * Admin analytics aggregation.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Database\AdStatsRepository;
use TCM\Database\AmbassadorsRepository;
use TCM\Database\AssignmentsRepository;
use TCM\Database\SubscribersRepository;
use TCM\PostTypes\CampaignPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Composes site-wide key figures for the admin analytics strip from the
 * existing repositories. Read-only and cheap (a handful of COUNT/SUM queries).
 */
final class AnalyticsService {

	/**
	 * @var AssignmentsRepository
	 */
	private $assignments;

	/**
	 * @var SubscribersRepository
	 */
	private $subscribers;

	/**
	 * @var AmbassadorsRepository
	 */
	private $ambassadors;

	/**
	 * @var AdStatsRepository
	 */
	private $ad_stats;

	/**
	 * Constructor.
	 *
	 * @param AssignmentsRepository|null $assignments Assignments repo.
	 * @param SubscribersRepository|null $subscribers Subscribers repo.
	 * @param AmbassadorsRepository|null $ambassadors Ambassadors repo.
	 * @param AdStatsRepository|null     $ad_stats    Ad stats repo.
	 */
	public function __construct( $assignments = null, $subscribers = null, $ambassadors = null, $ad_stats = null ) {
		$this->assignments = $assignments ? $assignments : new AssignmentsRepository();
		$this->subscribers = $subscribers ? $subscribers : new SubscribersRepository();
		$this->ambassadors = $ambassadors ? $ambassadors : new AmbassadorsRepository();
		$this->ad_stats    = $ad_stats ? $ad_stats : new AdStatsRepository();
	}

	/**
	 * Click-through rate as a percentage, rounded to one decimal.
	 *
	 * @param int $impressions Impressions.
	 * @param int $clicks      Clicks.
	 * @return float
	 */
	public static function ctr( $impressions, $clicks ) {
		$impressions = (int) $impressions;
		if ( $impressions <= 0 ) {
			return 0.0;
		}
		return round( ( (int) $clicks / $impressions ) * 100, 1 );
	}

	/**
	 * Site-wide analytics summary.
	 *
	 * @return array<string,int|float>
	 */
	public function summary() {
		$totals = $this->assignments->global_totals();
		$ads    = $this->ad_stats->totals();

		$published = wp_count_posts( CampaignPostType::POST_TYPE );
		$campaigns = isset( $published->publish ) ? (int) $published->publish : 0;

		// completed_at is stored in site-local time, so shift the local "now".
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) - ( 30 * DAY_IN_SECONDS ) );

		return array(
			'campaigns'          => $campaigns,
			'chapters_done'      => (int) $totals['done'],
			'participants'       => (int) $totals['participants'],
			'done_30d'           => $this->assignments->count_done_since( $cutoff ),
			'subscribers_active' => $this->subscribers->count_active(),
			'ambassadors'        => $this->ambassadors->count_all(),
			'ad_impressions'     => (int) $ads['impressions'],
			'ad_clicks'          => (int) $ads['clicks'],
			'ad_ctr'             => self::ctr( $ads['impressions'], $ads['clicks'] ),
		);
	}
}
