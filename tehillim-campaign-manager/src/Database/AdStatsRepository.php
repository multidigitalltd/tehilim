<?php
/**
 * Ad statistics repository.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggregated, privacy-friendly impression/click counters per ad/zone/day.
 */
final class AdStatsRepository extends Repository {

	/**
	 * {@inheritDoc}
	 */
	protected function table_suffix() {
		return 'tcm_ad_stats';
	}

	/**
	 * Increment a counter for today (upsert on the ad/zone/date unique key).
	 *
	 * @param int    $ad_id Ad post id.
	 * @param string $zone  Placement zone.
	 * @param string $field "impressions" or "clicks".
	 * @return void
	 */
	public function increment( $ad_id, $zone, $field ) {
		$field = ( 'clicks' === $field ) ? 'clicks' : 'impressions';
		$today = current_time( 'Y-m-d' );

		$sql = $this->db->prepare(
			"INSERT INTO {$this->table} (ad_id, zone, impressions, clicks, stat_date)
             VALUES (%d, %s, %d, %d, %s)
             ON DUPLICATE KEY UPDATE {$field} = {$field} + 1",
			$ad_id,
			$zone,
			'impressions' === $field ? 1 : 0,
			'clicks' === $field ? 1 : 0,
			$today
		);
		$this->db->query( $sql );
	}

	/**
	 * Site-wide ad totals (impressions, clicks) for admin analytics.
	 *
	 * @return array{impressions:int,clicks:int}
	 */
	public function totals() {
		$row = $this->db->get_row(
			"SELECT COALESCE(SUM(impressions),0) AS impressions, COALESCE(SUM(clicks),0) AS clicks
             FROM {$this->table}"
		);
		return array(
			'impressions' => $row ? (int) $row->impressions : 0,
			'clicks'      => $row ? (int) $row->clicks : 0,
		);
	}
}
