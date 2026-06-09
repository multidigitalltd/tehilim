<?php
/**
 * Cloudflare Turnstile on the front-end login form.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Support\Turnstile;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies a Turnstile token when the custom front-end login form is submitted
 * (marked with a hidden tcm_front_login flag). wp-admin logins are untouched, so
 * a misconfigured key can never lock administrators out. No-op unless Turnstile
 * is configured.
 */
final class LoginGuard implements Registerable {

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_filter( 'authenticate', array( $this, 'verify' ), 30, 1 );
	}

	/**
	 * Block authentication when the front-end form's Turnstile token is invalid.
	 *
	 * @param \WP_User|\WP_Error|null $user Current authentication result.
	 * @return \WP_User|\WP_Error|null
	 */
	public function verify( $user ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- login form; protection is Turnstile, not a nonce.
		if ( empty( $_POST['tcm_front_login'] ) ) {
			return $user;
		}
		$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! Turnstile::verify( $token ) ) {
			return new WP_Error( 'tcm_turnstile', __( 'Security verification failed. Please try again.', 'tehillim-campaign-manager' ) );
		}
		return $user;
	}
}
