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
	 * Completed-chapters series for the last N days, gap-filled with zeros and
	 * ordered oldest - newest. Powers the dashboard trend chart.
	 *
	 * @param int $days Number of days.
	 * @return array<int,array{date:string,count:int}>
	 */
	public function daily_trend( $days = 30 ) {
		$days      = max( 1, (int) $days );
		$now_local = strtotime( current_time( 'mysql' ) );
		$since     = gmdate( 'Y-m-d H:i:s', $now_local - ( $days * DAY_IN_SECONDS ) );

		$by_day = array();
		foreach ( $this->assignments->done_by_day( $since ) as $r ) {
			$by_day[ (string) $r->day ] = (int) $r->c;
		}

		$today_ts = strtotime( gmdate( 'Y-m-d', $now_local ) );
		$series   = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$date     = gmdate( 'Y-m-d', $today_ts - ( $i * DAY_IN_SECONDS ) );
			$series[] = array(
				'date'  => $date,
				'count' => isset( $by_day[ $date ] ) ? (int) $by_day[ $date ] : 0,
			);
		}
		return $series;
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
