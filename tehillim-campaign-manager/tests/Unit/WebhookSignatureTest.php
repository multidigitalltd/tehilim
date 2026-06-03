<?php
/**
 * Tests for webhook HMAC signing.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TCM\Services\WebhookService;

/**
 * @covers \TCM\Services\WebhookService::sign
 */
final class WebhookSignatureTest extends TestCase {

    public function test_signature_is_deterministic() {
        $a = WebhookService::sign('payload', 'secret');
        $b = WebhookService::sign('payload', 'secret');
        $this->assertSame($a, $b);
    }

    public function test_signature_matches_reference_hmac() {
        $this->assertSame(
            hash_hmac('sha256', '123.{"a":1}', 's3cr3t'),
            WebhookService::sign('123.{"a":1}', 's3cr3t')
        );
    }

    public function test_different_secret_changes_signature() {
        $this->assertNotSame(
            WebhookService::sign('payload', 'secret-a'),
            WebhookService::sign('payload', 'secret-b')
        );
    }
}
