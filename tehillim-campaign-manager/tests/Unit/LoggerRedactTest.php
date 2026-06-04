<?php
/**
 * Tests for log redaction of sensitive keys.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TCM\Support\Logger;

/**
 * @covers \TCM\Support\Logger::redact
 */
final class LoggerRedactTest extends TestCase {

	public function test_sensitive_keys_are_redacted() {
		$out = Logger::redact(
			array(
				'campaign_id'       => 5,
				'participant_email' => 'a@b.com',
				'token'             => 'secret-token',
				'phone'             => '050',
			)
		);
		$this->assertSame( 5, $out['campaign_id'] );
		$this->assertSame( '[redacted]', $out['participant_email'] );
		$this->assertSame( '[redacted]', $out['token'] );
		$this->assertSame( '[redacted]', $out['phone'] );
	}

	public function test_redaction_is_recursive() {
		$out = Logger::redact(
			array(
				'outer' => array( 'email' => 'a@b.com', 'ok' => 'keep' ),
			)
		);
		$this->assertSame( '[redacted]', $out['outer']['email'] );
		$this->assertSame( 'keep', $out['outer']['ok'] );
	}
}
