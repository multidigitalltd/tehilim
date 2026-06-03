<?php
/**
 * Tests for the Hebrew helper.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TCM\Support\Hebrew;

/**
 * @covers \TCM\Support\Hebrew
 */
final class HebrewTest extends TestCase {

    /**
     * @dataProvider chapterProvider
     *
     * @param int    $number   Chapter number.
     * @param string $expected Expected Hebrew label.
     */
    public function test_chapter_label($number, $expected) {
        $this->assertSame($expected, Hebrew::chapter_label($number));
    }

    /**
     * @return array<int,array{0:int,1:string}>
     */
    public function chapterProvider() {
        return array(
            array(1, 'א'),
            array(10, 'י'),
            array(15, 'טו'),
            array(16, 'טז'),
            array(90, 'צ'),
            array(99, 'צט'),
            array(100, 'ק'),
            array(110, 'קי'),
            array(111, 'קיא'),
            array(115, 'קטו'),
            array(116, 'קטז'),
            array(119, 'קיט'),
            array(150, 'קנ'),
        );
    }

    public function test_out_of_range_returns_number() {
        $this->assertSame('0', Hebrew::chapter_label(0));
        $this->assertSame('500', Hebrew::chapter_label(500));
    }
}
