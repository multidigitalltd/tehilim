<?php
/**
 * Tests for the pure badge-tier selection.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TCM\Services\BadgeService;

/**
 * @covers \TCM\Services\BadgeService
 */
final class BadgeServiceTest extends TestCase {

	/**
	 * @return array<int,array{min:int,slug:string,label:string,icon:string}>
	 */
	private function tiers() {
		return array(
			array(
				'min'   => 25,
				'slug'  => 'gold',
				'label' => 'Gold',
				'icon'  => 'G',
			),
			array(
				'min'   => 10,
				'slug'  => 'star',
				'label' => 'Star',
				'icon'  => 'S',
			),
			array(
				'min'   => 1,
				'slug'  => 'rookie',
				'label' => 'Rookie',
				'icon'  => 'R',
			),
		);
	}

	public function test_below_lowest_threshold_has_no_badge() {
		$this->assertNull( BadgeService::highest( $this->tiers(), 0 ) );
	}

	public function test_picks_the_lowest_reached_tier() {
		$this->assertSame( 'rookie', BadgeService::highest( $this->tiers(), 1 )['slug'] );
		$this->assertSame( 'rookie', BadgeService::highest( $this->tiers(), 9 )['slug'] );
	}

	public function test_picks_the_highest_reached_tier() {
		$this->assertSame( 'star', BadgeService::highest( $this->tiers(), 10 )['slug'] );
		$this->assertSame( 'gold', BadgeService::highest( $this->tiers(), 25 )['slug'] );
		$this->assertSame( 'gold', BadgeService::highest( $this->tiers(), 1000 )['slug'] );
	}

	public function test_badge_shape() {
		$badge = BadgeService::highest( $this->tiers(), 10 );
		$this->assertArrayHasKey( 'slug', $badge );
		$this->assertArrayHasKey( 'label', $badge );
		$this->assertArrayHasKey( 'icon', $badge );
	}

	public function test_next_tier_is_the_immediate_one_above() {
		$next = BadgeService::next_tier( $this->tiers(), 0 );
		$this->assertSame( 'rookie', $next['slug'] );
		$this->assertSame( 1, $next['remaining'] );

		$next = BadgeService::next_tier( $this->tiers(), 5 );
		$this->assertSame( 'star', $next['slug'] );
		$this->assertSame( 5, $next['remaining'] );
	}

	public function test_next_tier_is_null_at_the_top() {
		$this->assertNull( BadgeService::next_tier( $this->tiers(), 25 ) );
		$this->assertNull( BadgeService::next_tier( $this->tiers(), 99 ) );
	}
}
