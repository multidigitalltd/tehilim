<?php
/**
 * REST API Endpoints
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST routes
 */
function tehilim_register_rest_routes() {
	register_rest_route( 'tehilim/v1', '/recitations', array(
		'methods'             => 'POST',
		'callback'            => 'tehilim_handle_recitation',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'tehilim/v1', '/campaigns/(?P<id>\d+)/stats', array(
		'methods'             => 'GET',
		'callback'            => 'tehilim_get_campaign_stats',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'tehilim/v1', '/ambassadors/join', array(
		'methods'             => 'POST',
		'callback'            => 'tehilim_handle_ambassador_join',
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'tehilim_register_rest_routes' );

/**
 * Rate-limiting helper
 */
function tehilim_check_rate_limit( $endpoint, $limit = 10, $window = 3600 ) {
	if ( is_user_logged_in() ) {
		return true;
	}

	$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
	$ip_hash  = md5( $ip );
	$key      = 'tehilim_rl_' . $endpoint . '_' . $ip_hash;
	$count    = get_transient( $key );

	if ( false === $count ) {
		set_transient( $key, 1, $window );
		return true;
	}

	if ( $count >= $limit ) {
		return false;
	}

	set_transient( $key, $count + 1, $window );
	return true;
}

/**
 * Handle recitation endpoint
 */
function tehilim_handle_recitation( WP_REST_Request $request ) {
	if ( ! tehilim_check_rate_limit( 'recitations', 10, 3600 ) ) {
		return new WP_Error( 'rate_limit', 'Too many requests', array( 'status' => 429 ) );
	}

	$params = $request->get_json_params();

	$campaign_id = isset( $params['campaign_id'] ) ? absint( $params['campaign_id'] ) : 0;
	$chapter_number = isset( $params['chapter_number'] ) ? absint( $params['chapter_number'] ) : 0;
	$ambassador_id = isset( $params['ambassador_id'] ) ? absint( $params['ambassador_id'] ) : null;
	$reciter_name = isset( $params['reciter_name'] ) ? sanitize_text_field( $params['reciter_name'] ) : '';

	if ( ! $campaign_id || ! $chapter_number || $chapter_number < 1 || $chapter_number > 150 ) {
		return new WP_Error( 'invalid_params', 'Invalid campaign_id or chapter_number', array( 'status' => 400 ) );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'tehilim_recitations';

	$wpdb->insert( $table, array(
		'campaign_id'   => $campaign_id,
		'ambassador_id' => $ambassador_id,
		'chapter_number' => $chapter_number,
		'reciter_name'  => $reciter_name,
	), array( '%d', '%d', '%d', '%s' ) );

	if ( $wpdb->last_error ) {
		return new WP_Error( 'db_error', 'Failed to record recitation', array( 'status' => 500 ) );
	}

	$next_chapter = $chapter_number === 150 ? 1 : $chapter_number + 1;

	return array(
		'success'        => true,
		'chapter_number' => $next_chapter,
	);
}

/**
 * Get campaign stats
 */
function tehilim_get_campaign_stats( WP_REST_Request $request ) {
	global $wpdb;

	$campaign_id = absint( $request->get_param( 'id' ) );

	if ( ! get_post( $campaign_id ) ) {
		return new WP_Error( 'not_found', 'Campaign not found', array( 'status' => 404 ) );
	}

	$table = $wpdb->prefix . 'tehilim_recitations';

	$total_chapters = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT chapter_number) FROM `%i` WHERE campaign_id = %d",
		$table,
		$campaign_id
	) );

	$total_recitations = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM `%i` WHERE campaign_id = %d",
		$table,
		$campaign_id
	) );

	$total_ambassadors = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT ambassador_id) FROM `%i` WHERE campaign_id = %d AND ambassador_id IS NOT NULL",
		$table,
		$campaign_id
	) );

	$books_done = intdiv( $total_chapters, 150 );
	$chapters_done = $total_chapters % 150;

	return array(
		'books_done'     => $books_done,
		'chapters_done'  => $chapters_done,
		'participants'   => $total_recitations,
		'ambassadors'    => $total_ambassadors,
	);
}

/**
 * Handle ambassador join endpoint
 */
function tehilim_handle_ambassador_join( WP_REST_Request $request ) {
	if ( ! tehilim_check_rate_limit( 'ambassador_join', 1, 3600 ) ) {
		return new WP_Error( 'rate_limit', 'Too many requests', array( 'status' => 429 ) );
	}

	$params = $request->get_json_params();

	$campaign_id = isset( $params['campaign_id'] ) ? absint( $params['campaign_id'] ) : 0;
	$name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
	$email = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';

	if ( ! $campaign_id || ! $name || ! $email ) {
		return new WP_Error( 'invalid_params', 'Missing required fields', array( 'status' => 400 ) );
	}

	if ( ! get_post( $campaign_id ) ) {
		return new WP_Error( 'not_found', 'Campaign not found', array( 'status' => 404 ) );
	}

	$ambassador_id = wp_insert_post( array(
		'post_type'   => 'ambassador',
		'post_title'  => $name,
		'post_status' => 'publish',
	) );

	if ( is_wp_error( $ambassador_id ) ) {
		return new WP_Error( 'create_failed', 'Failed to create ambassador', array( 'status' => 500 ) );
	}

	update_post_meta( $ambassador_id, 'campaign_id', $campaign_id );
	update_post_meta( $ambassador_id, 'email', $email );

	$personal_url = home_url( '/c/' . get_post_field( 'post_name', $campaign_id ) . '/' . get_post_field( 'post_name', $ambassador_id ) );

	return array(
		'ambassador_id' => $ambassador_id,
		'personal_url'  => $personal_url,
	);
}
