<?php
/**
 * Tests for token verification.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TCM\Support\Tokens;

/**
 * @covers \TCM\Support\Tokens::verify
 */
final class TokensTest extends TestCase {

    public function test_matching_tokens_verify() {
        $this->assertTrue(Tokens::verify('abc123', 'abc123'));
    }

    public function test_mismatched_tokens_fail() {
        $this->assertFalse(Tokens::verify('abc123', 'abc124'));
    }

    public function test_empty_known_token_fails() {
        $this->assertFalse(Tokens::verify('', 'anything'));
    }

    public function test_non_string_input_fails() {
        // @phpstan-ignore-next-line — intentionally passing wrong type.
        $this->assertFalse(Tokens::verify('abc', null));
    }
}
