<?php
/**
 * Front-end Authentication — styled login page + Google OAuth sign-in
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google OAuth credentials: wp-config constants win, admin options as fallback.
 */
function tehilim_google_client_id() {
	if ( defined( 'TEHILIM_GOOGLE_CLIENT_ID' ) && TEHILIM_GOOGLE_CLIENT_ID ) {
		return TEHILIM_GOOGLE_CLIENT_ID;
	}
	return trim( (string) get_option( 'tehilim_google_client_id', '' ) );
}

function tehilim_google_client_secret() {
	if ( defined( 'TEHILIM_GOOGLE_CLIENT_SECRET' ) && TEHILIM_GOOGLE_CLIENT_SECRET ) {
		return TEHILIM_GOOGLE_CLIENT_SECRET;
	}
	return trim( (string) get_option( 'tehilim_google_client_secret', '' ) );
}

function tehilim_google_enabled() {
	return tehilim_google_client_id() && tehilim_google_client_secret();
}

/**
 * URL of the themed login page (falls back to wp-login.php until the page exists).
 */
function tehilim_login_page_url( $redirect = '' ) {
	$page = get_page_by_path( 'login' );
	if ( ! $page ) {
		$url = site_url( 'wp-login.php', 'login' );
	} else {
		$url = get_permalink( $page );
	}
	if ( $redirect ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
	}
	return $url;
}

/**
 * URL of the personal area page.
 */
function tehilim_account_page_url() {
	$page = get_page_by_path( 'account' );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

/**
 * JS-readable login-state cookie: cached pages are the same HTML for
 * everyone, so the header swaps its buttons client-side based on this.
 */
function tehilim_set_login_cookie() {
	setcookie( 'tehilim_li', '1', time() + 14 * DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
}
add_action( 'wp_login', 'tehilim_set_login_cookie', 5 );

function tehilim_clear_login_cookie() {
	setcookie( 'tehilim_li', '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
}
add_action( 'wp_logout', 'tehilim_clear_login_cookie' );

/**
 * Keep the cookie in sync (covers Google sign-in, expired cookies, etc.)
 */
function tehilim_sync_login_cookie() {
	if ( headers_sent() ) {
		return;
	}
	$has_cookie = isset( $_COOKIE['tehilim_li'] ) && '1' === $_COOKIE['tehilim_li'];
	if ( is_user_logged_in() && ! $has_cookie ) {
		tehilim_set_login_cookie();
	} elseif ( ! is_user_logged_in() && $has_cookie ) {
		tehilim_clear_login_cookie();
	}
}
add_action( 'init', 'tehilim_sync_login_cookie', 1 );

/**
 * Cache-safe logout endpoint: wp_logout_url() nonces baked into cached
 * pages belong to the anonymous variant and fail, so the header uses this.
 */
function tehilim_handle_logout() {
	if ( is_user_logged_in() ) {
		wp_logout();
	}
	wp_safe_redirect( home_url( '/' ) );
	exit;
}
add_action( 'admin_post_tehilim_logout', 'tehilim_handle_logout' );
add_action( 'admin_post_nopriv_tehilim_logout', 'tehilim_handle_logout' );

/**
 * After login, send visitors back to the page they came from — never to
 * the WordPress dashboard. Admins/editors keep normal behavior.
 */
function tehilim_login_redirect_filter( $redirect_to, $requested, $user ) {
	if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
		return $redirect_to;
	}

	if ( user_can( $user, 'edit_posts' ) ) {
		return $redirect_to; // authors/editors/admins: default behavior
	}

	// Prefer the explicitly requested front-end destination
	if ( $requested && false === strpos( $requested, 'wp-admin' ) ) {
		return $requested;
	}

	// No destination, or one pointing into wp-admin → personal area
	if ( ! $redirect_to || false !== strpos( $redirect_to, 'wp-admin' ) ) {
		return tehilim_account_page_url();
	}

	return $redirect_to;
}
add_filter( 'login_redirect', 'tehilim_login_redirect_filter', 99, 3 );

/**
 * Regular members never belong in wp-admin: any flow (or plugin) that lands
 * them on the dashboard bounces to the personal area instead.
 */
function tehilim_block_admin_for_members() {
	if ( wp_doing_ajax() || ! is_user_logged_in() || current_user_can( 'edit_posts' ) ) {
		return;
	}
	wp_safe_redirect( tehilim_account_page_url() );
	exit;
}
add_action( 'admin_init', 'tehilim_block_admin_for_members' );

/**
 * Point wp_login_url() at the themed page so every login link lands there.
 */
function tehilim_filter_login_url( $login_url, $redirect ) {
	$page = get_page_by_path( 'login' );
	if ( ! $page ) {
		return $login_url;
	}
	$url = get_permalink( $page );
	if ( $redirect ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
	}
	return $url;
}
add_filter( 'login_url', 'tehilim_filter_login_url', 10, 2 );

/**
 * Failed / empty logins that came from our page return to it with a flag.
 */
function tehilim_login_failed_redirect() {
	$referer = wp_get_referer();
	if ( $referer && false !== strpos( $referer, '/login' ) && false === strpos( $referer, 'wp-admin' ) ) {
		wp_safe_redirect( add_query_arg( 'login', 'failed', tehilim_login_page_url() ) );
		exit;
	}
}
add_action( 'wp_login_failed', 'tehilim_login_failed_redirect' );

/**
 * Auto-create the front-end pages the theme's flows depend on.
 */
function tehilim_ensure_theme_pages() {
	$pages = array(
		'create'  => 'יצירת קמפיין',
		'login'   => 'התחברות',
		'account' => 'האזור האישי',
		'about'   => 'אודות',
		'terms'   => 'תקנון האתר',
		'privacy' => 'מדיניות פרטיות',
	);

	foreach ( $pages as $slug => $title ) {
		if ( ! get_page_by_path( $slug ) ) {
			wp_insert_post( array(
				'post_type'   => 'page',
				'post_name'   => $slug,
				'post_title'  => $title,
				'post_status' => 'publish',
			) );
		}
	}
}
add_action( 'after_switch_theme', 'tehilim_ensure_theme_pages', 5 );

/**
 * Heal after in-place theme updates: uploading a new ZIP over the active
 * theme never fires after_switch_theme, so required pages and rewrite rules
 * would be missing. Runs once per theme version.
 */
function tehilim_maybe_upgrade() {
	if ( get_option( 'tehilim_setup_version' ) === TEHILIM_VERSION ) {
		return;
	}
	tehilim_ensure_theme_pages();
	flush_rewrite_rules();
	update_option( 'tehilim_setup_version', TEHILIM_VERSION );
}
add_action( 'init', 'tehilim_maybe_upgrade', 99 );

/* ========================================================================
 * Google OAuth (server-side authorization-code flow, no SDK)
 * Redirect URI to register in Google Console:
 *   {site}/wp-admin/admin-post.php?action=tehilim_google_callback
 * ======================================================================== */

function tehilim_google_redirect_uri() {
	return admin_url( 'admin-post.php?action=tehilim_google_callback' );
}

/**
 * Step 1: send the visitor to Google's consent screen.
 */
function tehilim_google_login_start() {
	if ( ! tehilim_google_enabled() ) {
		wp_safe_redirect( add_query_arg( 'login', 'google_off', tehilim_login_page_url() ) );
		exit;
	}

	$redirect_to = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), home_url( '/' ) ) : home_url( '/' );

	// CSRF state: random token stored server-side with the destination
	$state = wp_generate_password( 24, false );
	set_transient( 'tehilim_gauth_' . $state, $redirect_to, 10 * MINUTE_IN_SECONDS );

	$auth_url = add_query_arg( array(
		'client_id'     => rawurlencode( tehilim_google_client_id() ),
		'redirect_uri'  => rawurlencode( tehilim_google_redirect_uri() ),
		'response_type' => 'code',
		'scope'         => rawurlencode( 'openid email profile' ),
		'state'         => $state,
		'prompt'        => 'select_account',
	), 'https://accounts.google.com/o/oauth2/v2/auth' );

	wp_redirect( $auth_url ); // external — deliberately not wp_safe_redirect
	exit;
}
add_action( 'admin_post_nopriv_tehilim_google_login', 'tehilim_google_login_start' );
add_action( 'admin_post_tehilim_google_login', 'tehilim_google_login_start' );

/**
 * Step 2: Google redirects back with ?code — exchange, verify, sign in.
 */
function tehilim_google_login_callback() {
	$fail = function( $flag ) {
		wp_safe_redirect( add_query_arg( 'login', $flag, tehilim_login_page_url() ) );
		exit;
	};

	if ( ! tehilim_google_enabled() || empty( $_GET['code'] ) || empty( $_GET['state'] ) ) {
		$fail( 'google_failed' );
	}

	$state       = sanitize_text_field( wp_unslash( $_GET['state'] ) );
	$redirect_to = get_transient( 'tehilim_gauth_' . $state );
	if ( false === $redirect_to ) {
		$fail( 'google_failed' ); // unknown/expired state = possible CSRF
	}
	delete_transient( 'tehilim_gauth_' . $state );

	// Exchange the one-time code for tokens (server-to-server)
	$response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 15,
		'body'    => array(
			'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ),
			'client_id'     => tehilim_google_client_id(),
			'client_secret' => tehilim_google_client_secret(),
			'redirect_uri'  => tehilim_google_redirect_uri(),
			'grant_type'    => 'authorization_code',
		),
	) );

	if ( is_wp_error( $response ) ) {
		$fail( 'google_failed' );
	}

	$tokens = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $tokens['id_token'] ) ) {
		$fail( 'google_failed' );
	}

	// Validate the ID token against Google and check it is ours
	$verify = wp_remote_get( add_query_arg( 'id_token', rawurlencode( $tokens['id_token'] ), 'https://oauth2.googleapis.com/tokeninfo' ), array( 'timeout' => 15 ) );
	if ( is_wp_error( $verify ) || 200 !== wp_remote_retrieve_response_code( $verify ) ) {
		$fail( 'google_failed' );
	}

	$claims = json_decode( wp_remote_retrieve_body( $verify ), true );

	$aud_ok   = isset( $claims['aud'] ) && $claims['aud'] === tehilim_google_client_id();
	$email_ok = ! empty( $claims['email'] ) && is_email( $claims['email'] ) && ( ! isset( $claims['email_verified'] ) || 'true' === $claims['email_verified'] || true === $claims['email_verified'] );
	if ( ! $aud_ok || ! $email_ok ) {
		$fail( 'google_failed' );
	}

	$email = sanitize_email( $claims['email'] );
	$user  = get_user_by( 'email', $email );

	if ( ! $user ) {
		// First sign-in: create a subscriber account from the Google profile
		$base_login = sanitize_user( current( explode( '@', $email ) ), true );
		$login      = $base_login ?: 'user';
		$suffix     = 1;
		while ( username_exists( $login ) ) {
			$login = $base_login . $suffix;
			$suffix++;
		}

		$user_id = wp_insert_user( array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password( 24 ),
			'display_name' => ! empty( $claims['name'] ) ? sanitize_text_field( $claims['name'] ) : $login,
			'first_name'   => ! empty( $claims['given_name'] ) ? sanitize_text_field( $claims['given_name'] ) : '',
			'last_name'    => ! empty( $claims['family_name'] ) ? sanitize_text_field( $claims['family_name'] ) : '',
			'role'         => 'subscriber',
		) );

		if ( is_wp_error( $user_id ) ) {
			$fail( 'google_failed' );
		}

		update_user_meta( $user_id, 'tehilim_google_sub', sanitize_text_field( $claims['sub'] ?? '' ) );
		$user = get_user_by( 'id', $user_id );
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );

	wp_safe_redirect( $redirect_to ?: home_url( '/' ) );
	exit;
}
add_action( 'admin_post_nopriv_tehilim_google_callback', 'tehilim_google_login_callback' );
add_action( 'admin_post_tehilim_google_callback', 'tehilim_google_login_callback' );
