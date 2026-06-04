<?php
/**
 * Structured logger.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Support;

use TCM\Database\LogsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Structured logging with severity levels (DEBUG..FATAL).
 *
 * Per docs/ENGINEERING-STANDARDS.md (error handling):
 *  - LOG everything needed at 3am, but NEVER passwords, tokens or PII.
 *  - Fires the `tcm_log` action so an external sink (Sentry/equivalent) can
 *    subscribe and alert on ERROR and above.
 */
final class Logger {

	const DEBUG = 'debug';
	const INFO  = 'info';
	const WARN  = 'warn';
	const ERROR = 'error';
	const FATAL = 'fatal';

	/**
	 * Keys whose values must be redacted before they reach a log sink.
	 *
	 * @var string[]
	 */
	private static $sensitive = array(
		'password',
		'pass',
		'token',
		'secret',
		'cf-turnstile-response',
		'participant_email',
		'participant_phone',
		'email',
		'phone',
	);

	/**
	 * Log an event.
	 *
	 * @param string              $level   One of the level constants.
	 * @param string              $event   Short machine event key.
	 * @param array<string,mixed> $context Structured context (auto-redacted).
	 * @return void
	 */
	public static function log( $level, $event, array $context = array() ) {
		$safe = self::redact( $context );

		/**
		 * Allow an external sink (e.g. Sentry) to receive the event.
		 *
		 * @param string $level
		 * @param string $event
		 * @param array  $safe
		 */
		do_action( 'tcm_log', $level, $event, $safe );

		if ( self::is_alertable( $level ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[TCM][%s] %s %s', strtoupper( $level ), $event, wp_json_encode( $safe ) ) );
		}

		if ( in_array( $level, array( self::WARN, self::ERROR, self::FATAL ), true ) ) {
			( new LogsRepository() )->record( $event, $safe );
		}
	}

	/**
	 * Whether this level should trigger an alert / be written to PHP error log.
	 *
	 * @param string $level Level.
	 * @return bool
	 */
	private static function is_alertable( $level ) {
		return in_array( $level, array( self::ERROR, self::FATAL ), true );
	}

	/**
	 * Redact sensitive keys (recursively). Public so the DB log path can enforce
	 * the same redaction and there is no uncensored write route.
	 *
	 * @param array<string,mixed> $context Context.
	 * @return array<string,mixed>
	 */
	public static function redact( array $context ) {
		foreach ( $context as $key => $value ) {
			if ( in_array( strtolower( (string) $key ), self::$sensitive, true ) ) {
				$context[ $key ] = '[redacted]';
			} elseif ( is_array( $value ) ) {
				$context[ $key ] = self::redact( $value );
			}
		}
		return $context;
	}
}
