<?php
/**
 * Round service.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Database\AssignmentsRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Owns "round" (book) lifecycle: which round is current, generating new rounds,
 * and locating an empty full book to claim.
 */
final class RoundService {

    const CHAPTERS_PER_BOOK = 150;

    /**
     * @var AssignmentsRepository
     */
    private $assignments;

    /**
     * @param AssignmentsRepository|null $assignments Repository.
     */
    public function __construct($assignments = null) {
        $this->assignments = $assignments ? $assignments : new AssignmentsRepository();
    }

    /**
     * The active round: the first not-fully-completed round. Generates the next
     * round on demand when every existing round is complete.
     *
     * @param int $campaign_id Campaign.
     * @return int
     */
    public function current_round($campaign_id) {
        $round = $this->assignments->first_incomplete_round($campaign_id);
        if ($round >= 1) {
            return $round;
        }
        $round = max(1, $this->assignments->max_round($campaign_id) + 1);
        $this->generate($campaign_id, $round);
        return $round;
    }

    /**
     * Ensure a round's 150 chapters exist.
     *
     * @param int $campaign_id Campaign.
     * @param int $round       Round number.
     * @return void
     */
    public function generate($campaign_id, $round) {
        $this->assignments->insert_round($campaign_id, $round);
    }

    /**
     * Whether a round has been generated.
     *
     * @param int $campaign_id Campaign.
     * @param int $round       Round.
     * @return bool
     */
    public function has_round($campaign_id, $round) {
        return $this->assignments->round_exists($campaign_id, $round);
    }

    /**
     * Find a round that is an untouched full book (150 free, 0 taken/done),
     * scanning within the campaign goal. Returns 0 when none is available.
     *
     * @param int $campaign_id Campaign.
     * @param int $goal_total  target + bonus.
     * @return int
     */
    public function find_empty_full_book_round($campaign_id, $goal_total) {
        $goal_total = max(1, (int) $goal_total);

        for ($round = 1; $round <= $goal_total; $round++) {
            $total = $this->assignments->count_round($campaign_id, $round);
            if (0 === $total) {
                return $round;
            }
            $free        = $this->assignments->count_status($campaign_id, $round, 'free');
            $taken       = $this->assignments->count_status($campaign_id, $round, 'taken');
            $done        = $this->assignments->count_status($campaign_id, $round, 'done');
            if (self::CHAPTERS_PER_BOOK === $free && 0 === $taken && 0 === $done) {
                return $round;
            }
        }
        return 0;
    }
}
