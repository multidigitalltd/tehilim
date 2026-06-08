<?php
/**
 * Tests for the pure analytics helpers.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TCM\Services\AnalyticsService;

/**
 * @covers \TCM\Services\AnalyticsService
 */
final class AnalyticsServiceTest extends TestCase {

	public function test_ctr_is_zero_without_impressions() {
		$this->assertSame( 0.0, AnalyticsService::ctr( 0, 0 ) );
		$this->assertSame( 0.0, AnalyticsService::ctr( 0, 5 ) );
	}

	public function test_ctr_is_a_rounded_percentage() {
		$this->assertSame( 50.0, AnalyticsService::ctr( 100, 50 ) );
		$this->assertSame( 33.3, AnalyticsService::ctr( 3, 1 ) );
		$this->assertSame( 100.0, AnalyticsService::ctr( 7, 7 ) );
	}
}
