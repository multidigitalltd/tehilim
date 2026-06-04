<?php
/**
 * Cloudflare Turnstile verification.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side verification of a Cloudflare Turnstile token.
 *
 * When no keys are configured the gate is disabled (returns true) so the plugin
 * works out of the box; once configured, a missing/invalid token is rejected.
 */
final class Turnstile {

	const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	/**
	 * Whether keys are configured.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$options = get_option( 'tcm_options', array() );
		return ! empty( $options['turnstile_site_key'] ) && ! empty( $options['turnstile_secret_key'] );
	}

	/**
	 * Verify a submitted token.
	 *
	 * @param mixed $token The cf-turnstile-response value.
	 * @return bool
	 */
	public static function verify( $token ) {
		if ( ! self::is_enabled() ) {
			return true;
		}
		$token = is_string( $token ) ? trim( $token ) : '';
		if ( '' === $token ) {
			return false;
		}

		$options  = get_option( 'tcm_options', array() );
		$response = wp_remote_post(
			self::VERIFY_URL,
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => $options['turnstile_secret_key'],
					'response' => $token,
					'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::log( Logger::WARN, 'turnstile_request_failed', array( 'error' => $response->get_error_message() ) );
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return ! empty( $data['success'] );
	}
}
