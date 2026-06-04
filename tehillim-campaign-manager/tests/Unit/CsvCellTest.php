<?php
/**
 * Tests for CSV formula-injection hardening.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TCM\Admin\Exporter;

/**
 * @covers \TCM\Admin\Exporter::csv_cell
 */
final class CsvCellTest extends TestCase {

	/**
	 * @dataProvider dangerousProvider
	 *
	 * @param string $input Input value.
	 */
	public function test_dangerous_values_are_prefixed( $input ) {
		$this->assertSame( "'" . $input, Exporter::csv_cell( $input ) );
	}

	/**
	 * @return array<int,array{0:string}>
	 */
	public function dangerousProvider() {
		return array(
			array( '=1+1' ),
			array( '+1' ),
			array( '-1' ),
			array( '@SUM(A1)' ),
		);
	}

	public function test_safe_values_are_unchanged() {
		$this->assertSame( 'David', Exporter::csv_cell( 'David' ) );
		$this->assertSame( 'a@b.com', Exporter::csv_cell( 'a@b.com' ) );
		$this->assertSame( '', Exporter::csv_cell( '' ) );
	}
}
