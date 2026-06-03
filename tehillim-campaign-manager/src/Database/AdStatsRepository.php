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
}
