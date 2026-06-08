<?php
/**
 * Tests for the pure stats calculator.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TCM\Services\StatsCalculator;

/**
 * @covers \TCM\Services\StatsCalculator
 */
final class StatsCalculatorTest extends TestCase {

	public function test_empty_campaign() {
		$s = StatsCalculator::compute( 1, 0, 0 );
		$this->assertSame( 1, $s['goal_total'] );
		$this->assertSame( 0, $s['completed_books'] );
		$this->assertSame( 0.0, $s['percent'] );
	}

	public function test_one_full_book_is_complete() {
		$s = StatsCalculator::compute( 1, 0, 150 );
		$this->assertSame( 1, $s['completed_books'] );
		$this->assertSame( 1, $s['base_completed'] );
		$this->assertSame( 100.0, $s['percent'] );
	}

	public function test_half_of_two_book_goal() {
		$s = StatsCalculator::compute( 2, 0, 150 );
		$this->assertSame( 2, $s['goal_total'] );
		$this->assertSame( 50.0, $s['percent'] );
	}

	public function test_percent_capped_at_100() {
		$s = StatsCalculator::compute( 1, 0, 450 );
		$this->assertSame( 100.0, $s['percent'] );
	}

	public function test_bonus_split() {
		$s = StatsCalculator::compute( 2, 3, 0 );
		$this->assertSame( 2, $s['target'] );
		$this->assertSame( 3, $s['bonus'] );
		$this->assertSame( 5, $s['goal_total'] );
	}

	public function test_base_and_bonus_completed() {
		// Target 1 book, completed 2 books -> 1 base + 1 over base.
		$s = StatsCalculator::compute( 1, 1, 300 );
		$this->assertSame( 2, $s['completed_books'] );
		$this->assertSame( 1, $s['base_completed'] );
		$this->assertSame( 1, $s['bonus_completed'] );
	}

	public function test_negative_inputs_are_clamped() {
		$s = StatsCalculator::compute( -5, -5, -5 );
		$this->assertSame( 1, $s['target'] );
		$this->assertSame( 0, $s['bonus'] );
		$this->assertSame( 0, $s['total_done'] );
	}
}
