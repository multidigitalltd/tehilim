<?php
/**
 * Simple per-key rate limiter.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transient-backed fixed-window rate limiter, keyed by action + client IP.
 *
 * Per docs/ENGINEERING-STANDARDS.md: rate limiting goes in front of any public
 * or paid endpoint before exposure.
 */
final class RateLimiter {

	/**
	 * Record a hit and report whether the limit was exceeded.
	 *
	 * @param string $action Logical action name (e.g. "join").
	 * @param int    $max    Allowed hits per window.
	 * @param int    $window Window length in seconds.
	 * @return bool True when the caller is OVER the limit (should be blocked).
	 */
	public static function exceeded( $action, $max = 10, $window = 60 ) {
		$key   = 'tcm_rl_' . md5( $action . '|' . self::client_ip() );
		$count = (int) get_transient( $key );

		if ( $count >= (int) $max ) {
			return true;
		}

		set_transient( $key, $count + 1, (int) $window );
		return false;
	}

	/**
	 * Best-effort client IP.
	 *
	 * @return string
	 */
	private static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		return sanitize_text_field( $ip );
	}
}
