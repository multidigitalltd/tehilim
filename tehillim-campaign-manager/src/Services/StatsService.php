<?php
/**
 * Stats service.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Database\AssignmentsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Computes campaign statistics with a short-lived cache to avoid the N+1
 * pattern on archive/dashboard listings. The cache is invalidated on writes
 * via {@see self::flush()}.
 */
final class StatsService {

	const CACHE_TTL = 60;

	/**
	 * @var AssignmentsRepository
	 */
	private $assignments;

	/**
	 * @var RoundService
	 */
	private $rounds;

	/**
	 * @param AssignmentsRepository|null $assignments Repository.
	 * @param RoundService|null          $rounds      Round service.
	 */
	public function __construct( $assignments = null, $rounds = null ) {
		$this->assignments = $assignments ? $assignments : new AssignmentsRepository();
		$this->rounds      = $rounds ? $rounds : new RoundService( $this->assignments );
	}

	/**
	 * Full stats array for a campaign.
	 *
	 * @param int $campaign_id Campaign.
	 * @return array<string,mixed>
	 */
	public function for_campaign( $campaign_id ) {
		$campaign_id = (int) $campaign_id;
		$cache_key   = 'tcm_stats_' . $campaign_id;
		$cached      = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$target     = (int) get_post_meta( $campaign_id, '_tcm_target_books', true );
		$bonus      = (int) get_post_meta( $campaign_id, '_tcm_bonus_books', true );
		$total_done = $this->assignments->count_total_done( $campaign_id );

		$stats = StatsCalculator::compute( $target, $bonus, $total_done );
		$round = $this->rounds->current_round( $campaign_id );

		$stats['round']       = $round;
		$stats['round_done']  = $this->assignments->count_status( $campaign_id, $round, 'done' );
		$stats['round_taken'] = $this->assignments->count_status( $campaign_id, $round, 'taken' );
		$stats['round_free']  = $this->assignments->count_status( $campaign_id, $round, 'free' );

		set_transient( $cache_key, $stats, self::CACHE_TTL );
		return $stats;
	}

	/**
	 * Invalidate the cache for a campaign (call after any write).
	 *
	 * @param int $campaign_id Campaign.
	 * @return void
	 */
	public static function flush( $campaign_id ) {
		delete_transient( 'tcm_stats_' . (int) $campaign_id );
	}
}
