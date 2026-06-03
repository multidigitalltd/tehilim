<?php
/**
 * Token helpers.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generation and constant-time verification of per-assignment access tokens.
 */
final class Tokens {

    const LENGTH = 32;

    /**
     * Generate a URL-safe random token.
     *
     * @return string
     */
    public static function generate() {
        return wp_generate_password(self::LENGTH, false, false);
    }

    /**
     * Constant-time comparison (avoids timing oracles).
     *
     * @param string $known    The stored token.
     * @param string $provided The token from the request.
     * @return bool
     */
    public static function verify($known, $provided) {
        if (!is_string($known) || !is_string($provided) || '' === $known) {
            return false;
        }
        return hash_equals($known, $provided);
    }
}
