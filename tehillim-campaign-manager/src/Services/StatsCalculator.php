<?php
/**
 * Pure statistics calculator.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pure (side-effect-free) campaign progress math. Extracted so it can be unit
 * tested without WordPress or a database.
 */
final class StatsCalculator {

    const CHAPTERS_PER_BOOK = 150;

    /**
     * Compute progress figures.
     *
     * @param int $target     Base goal in books (>= 1).
     * @param int $bonus      Bonus books (>= 0).
     * @param int $total_done Completed chapters across all rounds (>= 0).
     * @return array{
     *     target:int, bonus:int, goal_total:int, total_done:int,
     *     completed_books:int, base_completed:int, bonus_completed:int, percent:float
     * }
     */
    public static function compute($target, $bonus, $total_done) {
        $target     = max(1, (int) $target);
        $bonus      = max(0, (int) $bonus);
        $total_done = max(0, (int) $total_done);

        $goal_total      = $target + $bonus;
        $completed_books = intdiv($total_done, self::CHAPTERS_PER_BOOK);
        $base_completed  = min($completed_books, $target);
        $bonus_completed = max(0, $completed_books - $target);

        $goal_chapters = $goal_total * self::CHAPTERS_PER_BOOK;
        $percent       = $goal_chapters > 0
            ? min(100.0, round(($total_done / $goal_chapters) * 100, 1))
            : 0.0;

        return array(
            'target'          => $target,
            'bonus'           => $bonus,
            'goal_total'      => $goal_total,
            'total_done'      => $total_done,
            'completed_books' => $completed_books,
            'base_completed'  => $base_completed,
            'bonus_completed' => $bonus_completed,
            'percent'         => $percent,
        );
    }
}
