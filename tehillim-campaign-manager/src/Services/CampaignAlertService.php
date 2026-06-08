<?php
/**
 * Near-completion campaign alerts.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Watches chapter completions and, when a campaign's current book drops to a
 * few remaining chapters, fires `tcm_campaign_nearly_done` exactly once per
 * round so subscribers can be nudged to come and finish it.
 */
final class CampaignAlertService implements Registerable {

	/**
	 * Remaining-chapter threshold that triggers the "almost done" nudge.
	 */
	const THRESHOLD = 10;

	/**
	 * @var AssignmentsRepository
	 */
	private $assignments;

	/**
	 * @param AssignmentsRepository|null $assignments Repository.
	 */
	public function __construct( $assignments = null ) {
		$this->assignments = $assignments ? $assignments : new AssignmentsRepository();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'tcm_chapter_done', array( $this, 'on_done' ), 30, 1 );
	}

	/**
	 * Evaluate the current book after a chapter is completed.
	 *
	 * @param object $row Assignment row.
	 * @return void
	 */
	public function on_done( $row ) {
		if ( ! is_object( $row ) ) {
			return;
		}
		$campaign_id = (int) ( $row->campaign_id ?? 0 );
		$round       = (int) ( $row->round_number ?? 0 );
		if ( $campaign_id <= 0 || $round <= 0 ) {
			return;
		}

		$remaining = $this->assignments->count_round( $campaign_id, $round )
			- $this->assignments->count_status( $campaign_id, $round, 'done' );

		if ( $remaining <= 0 || $remaining > self::THRESHOLD ) {
			return;
		}

		// Fire at most once per campaign + round.
		$key = '_tcm_nearly_alerted_r' . $round;
		if ( get_post_meta( $campaign_id, $key, true ) ) {
			return;
		}
		update_post_meta( $campaign_id, $key, 1 );

		/** Fires once when a campaign's current book is nearly complete. */
		do_action( 'tcm_campaign_nearly_done', $campaign_id, $round, (int) $remaining );
	}
}
