<?php
/**
 * Campaign service.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Database\AssignmentsRepository;
use TCM\Support\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Orchestrates claiming chapters. The previous engine used three near-identical
 * methods with manual rollback; here a single transactional path covers one
 * chapter, several chapters and a full book.
 */
final class CampaignService {

    const CHAPTERS_PER_BOOK = 150;

    /**
     * @var AssignmentsRepository
     */
    private $assignments;

    /**
     * @var RoundService
     */
    private $rounds;

    /**
     * @var StatsService
     */
    private $stats;

    /**
     * @param AssignmentsRepository|null $assignments Repository.
     * @param RoundService|null          $rounds      Round service.
     * @param StatsService|null          $stats       Stats service.
     */
    public function __construct($assignments = null, $rounds = null, $stats = null) {
        $this->assignments = $assignments ? $assignments : new AssignmentsRepository();
        $this->rounds      = $rounds ? $rounds : new RoundService($this->assignments);
        $this->stats       = $stats ? $stats : new StatsService($this->assignments, $this->rounds);
    }

    /**
     * Claim one or more chapters in the current round.
     *
     * @param int                  $campaign_id Campaign.
     * @param int                  $count       How many (1..150).
     * @param array<string,string> $participant name/email/phone (pre-sanitised).
     * @param string               $token       Per-claim token.
     * @param int                  $preferred   Specific chapter (only when count=1, 0=any).
     * @return array<int,object>|false Claimed rows, or false if unavailable.
     */
    public function claim_chapters($campaign_id, $count, array $participant, $token, $preferred = 0) {
        $campaign_id = (int) $campaign_id;
        $count       = max(1, min(self::CHAPTERS_PER_BOOK, (int) $count));
        $round       = $this->rounds->current_round($campaign_id);

        if (1 === $count && $preferred > 0) {
            $row    = $this->assignments->free_chapter($campaign_id, $round, (int) $preferred);
            $candidates = $row ? array($row) : array();
        } else {
            $candidates = $this->assignments->free_chapters($campaign_id, $round, $count);
        }

        if (count($candidates) < $count) {
            return false;
        }

        return $this->claim_rows($campaign_id, $candidates, $participant, $token, 'claim_chapters');
    }

    /**
     * Claim a whole untouched book (150 chapters).
     *
     * @param int                  $campaign_id Campaign.
     * @param int                  $goal_total  target + bonus (to scope the search).
     * @param array<string,string> $participant name/email/phone (pre-sanitised).
     * @param string               $token       Per-claim token.
     * @return array<int,object>|false
     */
    public function claim_full_book($campaign_id, $goal_total, array $participant, $token) {
        $campaign_id = (int) $campaign_id;
        $round       = $this->rounds->find_empty_full_book_round($campaign_id, $goal_total);
        if ($round < 1) {
            return false;
        }
        if (!$this->rounds->has_round($campaign_id, $round)) {
            $this->rounds->generate($campaign_id, $round);
        }

        $free = $this->assignments->count_status($campaign_id, $round, 'free');
        if (self::CHAPTERS_PER_BOOK !== $free) {
            return false;
        }

        $candidates = $this->assignments->free_chapters($campaign_id, $round, self::CHAPTERS_PER_BOOK);
        if (count($candidates) !== self::CHAPTERS_PER_BOOK) {
            return false;
        }

        return $this->claim_rows($campaign_id, $candidates, $participant, $token, 'claim_full_book');
    }

    /**
     * Transactionally claim a set of candidate rows. Rolls back on any contended
     * row so a partial claim never persists.
     *
     * @param int                  $campaign_id Campaign.
     * @param array<int,object>    $candidates  Free rows to claim.
     * @param array<string,string> $participant Participant.
     * @param string               $token       Token.
     * @param string               $event       Event name for logging.
     * @return array<int,object>|false
     */
    private function claim_rows($campaign_id, array $candidates, array $participant, $token, $event) {
        $this->assignments->begin();
        $claimed = array();

        foreach ($candidates as $candidate) {
            $affected = $this->assignments->claim((int) $candidate->id, $participant, $token);
            if (1 !== $affected) {
                $this->assignments->rollback();
                Logger::log(
                    Logger::WARN,
                    $event . '_contended',
                    array('campaign_id' => $campaign_id, 'assignment_id' => (int) $candidate->id)
                );
                return false;
            }
            $claimed[] = (int) $candidate->id;
        }

        $this->assignments->commit();
        StatsService::flush($campaign_id);

        $rows = array();
        foreach ($claimed as $id) {
            $row = $this->assignments->find($id);
            if ($row) {
                $rows[] = $row;
            }
        }

        Logger::log(
            Logger::INFO,
            $event,
            array('campaign_id' => $campaign_id, 'count' => count($rows))
        );
        return $rows;
    }

    /**
     * React to a chapter being completed: when a round is fully done, either
     * mark the campaign complete (goal reached) or open the next round.
     *
     * Fires `tcm_book_completed` and `tcm_campaign_completed` so notification
     * subsystems can subscribe without this service knowing about them.
     *
     * @param int $campaign_id Campaign.
     * @param int $round       Completed round.
     * @return void
     */
    public function after_chapter_done($campaign_id, $round) {
        $campaign_id = (int) $campaign_id;
        $round       = (int) $round;

        StatsService::flush($campaign_id);

        if ($this->assignments->count_status($campaign_id, $round, 'done') < self::CHAPTERS_PER_BOOK) {
            return;
        }

        $stats = $this->stats->for_campaign($campaign_id);

        /** Fires when a full book (round) is completed. */
        do_action('tcm_book_completed', $campaign_id, $round, $stats);

        if ($stats['completed_books'] >= $stats['goal_total']) {
            update_post_meta($campaign_id, '_tcm_status', 'completed');
            /** Fires when the campaign goal is reached. */
            do_action('tcm_campaign_completed', $campaign_id, $stats);
            return;
        }

        $this->rounds->generate($campaign_id, $round + 1);
        StatsService::flush($campaign_id);
    }
}
