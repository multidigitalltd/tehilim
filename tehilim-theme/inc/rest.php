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
		'permission_callback' => function() {
			return true; // Public campaigns allow unauthenticated recitations; rate-limiting + CAPTCHA enforce security
		},
		'args'                => array(
			'campaign_id'            => array(
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => function( $value ) {
					return is_numeric( $value ) && $value > 0;
				},
			),
			'chapter_number'         => array(
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => function( $value ) {
					return is_numeric( $value ) && $value >= 1 && $value <= TEHILIM_CHAPTERS_PER_BOOK;
				},
			),
			'ambassador_id'          => array(
				'required'          => false,
				'type'              => 'integer',
				'validate_callback' => function( $value ) {
					return empty( $value ) || ( is_numeric( $value ) && $value > 0 );
				},
			),
			'reciter_name'           => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'cf_turnstile_response'  => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	) );

	register_rest_route( 'tehilim/v1', '/campaigns/(?P<id>\d+)/stats', array(
		'methods'             => 'GET',
		'callback'            => 'tehilim_get_campaign_stats',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'tehilim/v1', '/campaigns/(?P<id>\d+)/next-chapter', array(
		'methods'             => 'GET',
		'callback'            => 'tehilim_get_next_chapter_endpoint',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'tehilim/v1', '/campaigns', array(
		'methods'             => 'POST',
		'callback'            => 'tehilim_handle_campaign_create',
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
function tehilim_check_rate_limit( $endpoint, $limit = TEHILIM_RATE_LIMIT_RECITATIONS, $window = TEHILIM_RATE_LIMIT_WINDOW ) {
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
 * Verify Turnstile token (if enabled)
 */
function tehilim_verify_turnstile( $token ) {
	if ( ! defined( 'TURNSTILE_SITE_KEY' ) || ! defined( 'TURNSTILE_SECRET_KEY' ) ) {
		return true; // Turnstile disabled
	}

	if ( empty( $token ) ) {
		return false;
	}

	$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
		'body' => array(
			'secret'   => TURNSTILE_SECRET_KEY,
			'response' => sanitize_text_field( $token ),
		),
	) );

	if ( is_wp_error( $response ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Tehilim: CAPTCHA verification error: ' . $response->get_error_message() );
		}
		return false;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	return isset( $body['success'] ) && $body['success'];
}

/**
 * Handle recitation endpoint
 */
function tehilim_handle_recitation( WP_REST_Request $request ) {
	if ( ! tehilim_check_rate_limit( 'recitations', TEHILIM_RATE_LIMIT_RECITATIONS, TEHILIM_RATE_LIMIT_WINDOW ) ) {
		return new WP_Error( 'rate_limit', 'Too many requests', array( 'status' => 429 ) );
	}

	$params = $request->get_json_params();

	$campaign_id = isset( $params['campaign_id'] ) ? absint( $params['campaign_id'] ) : 0;
	$chapter_number = isset( $params['chapter_number'] ) ? absint( $params['chapter_number'] ) : 0;
	$ambassador_id = isset( $params['ambassador_id'] ) ? absint( $params['ambassador_id'] ) : null;
	$reciter_name = isset( $params['reciter_name'] ) ? mb_substr( sanitize_text_field( $params['reciter_name'] ), 0, 100 ) : '';
	$turnstile_response = isset( $params['cf_turnstile_response'] ) ? sanitize_text_field( $params['cf_turnstile_response'] ) : '';

	if ( ! $campaign_id || ! $chapter_number || $chapter_number < 1 || $chapter_number > TEHILIM_CHAPTERS_PER_BOOK ) {
		return new WP_Error( 'invalid_params', 'Invalid campaign_id or chapter_number', array( 'status' => 400 ) );
	}

	// Verify Turnstile if configured
	if ( ! tehilim_verify_turnstile( $turnstile_response ) ) {
		return new WP_Error( 'turnstile_failed', 'CAPTCHA verification failed', array( 'status' => 403 ) );
	}

	// Verify campaign exists and is published
	$campaign = get_post( $campaign_id );
	if ( ! $campaign || 'campaign' !== $campaign->post_type || 'publish' !== $campaign->post_status ) {
		return new WP_Error( 'campaign_not_found', 'Campaign not found', array( 'status' => 404 ) );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'tehilim_recitations';

	$wpdb->insert( $table, array(
		'campaign_id'    => $campaign_id,
		'ambassador_id'  => $ambassador_id,
		'chapter_number' => $chapter_number,
		'reciter_name'   => $reciter_name,
	), array( '%d', '%d', '%d', '%s' ) );

	if ( $wpdb->last_error ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Tehilim: Database error during recitation insert: ' . $wpdb->last_error );
		}
		return new WP_Error( 'db_error', 'Failed to record recitation', array( 'status' => 500 ) );
	}

	// Clear campaign caches to ensure fresh stats
	tehilim_clear_campaign_caches( $campaign_id );

	// Fresh stats after the insert — lets the client update the UI instantly
	$stats = tehilim_build_stats_payload( $campaign_id );

	$response = array(
		'success'        => true,
		'chapter_number' => $stats['next_chapter'],
		'stats'          => $stats,
	);

	// No caching for write operations; add security headers
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );

	return $response;
}

/**
 * Build the full stats payload for a campaign (shared by stats + recitation responses)
 */
function tehilim_build_stats_payload( $campaign_id ) {
	global $wpdb;

	$table    = $wpdb->prefix . 'tehilim_recitations';
	$progress = tehilim_get_campaign_progress( $campaign_id );

	$total_ambassadors = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT ambassador_id) FROM `%i` WHERE campaign_id = %d AND ambassador_id IS NOT NULL",
		$table,
		$campaign_id
	) );

	$in_book   = intval( $progress['chapters_done'] );
	$available = isset( $progress['available'] ) && $progress['available']
		? array_map( 'intval', $progress['available'] )
		: range( 1, TEHILIM_CHAPTERS_PER_BOOK );

	return array(
		'books_done'       => intval( $progress['books_done'] ),
		'chapters_done'    => $in_book,
		'total_chapters'   => intval( $progress['total_chapters'] ),
		'goal_books'       => intval( $progress['goal_books'] ),
		'progress_percent' => intval( $progress['progress_percent'] ),
		'participants'     => tehilim_get_campaign_participants( $campaign_id ),
		'ambassadors'      => $total_ambassadors,
		'current_book'     => intval( $progress['books_done'] ) + 1,
		'in_book'          => $in_book,
		'remaining_in_book' => count( $available ),
		'next_chapter'     => $available[0],
		'available'        => $available,
	);
}

/**
 * Get campaign stats
 */
function tehilim_get_campaign_stats( WP_REST_Request $request ) {
	$campaign_id = absint( $request->get_param( 'id' ) );

	$campaign = get_post( $campaign_id );
	if ( ! $campaign || 'campaign' !== $campaign->post_type ) {
		return new WP_Error( 'not_found', 'Campaign not found', array( 'status' => 404 ) );
	}

	// Short cache for stats (30 seconds) since data updates frequently
	header( 'Cache-Control: public, max-age=30' );

	return tehilim_build_stats_payload( $campaign_id );
}

/**
 * Get next suggested chapter for a campaign
 */
function tehilim_get_next_chapter_endpoint( WP_REST_Request $request ) {
	$campaign_id = absint( $request->get_param( 'id' ) );

	$campaign = get_post( $campaign_id );
	if ( ! $campaign || 'campaign' !== $campaign->post_type ) {
		return new WP_Error( 'not_found', 'Campaign not found', array( 'status' => 404 ) );
	}

	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );

	$stats = tehilim_build_stats_payload( $campaign_id );

	return array(
		'chapter_number' => $stats['next_chapter'],
		'stats'          => $stats,
	);
}

/**
 * Handle campaign creation endpoint
 */
function tehilim_handle_campaign_create( WP_REST_Request $request ) {
	if ( ! tehilim_check_rate_limit( 'campaign_create', 3, TEHILIM_RATE_LIMIT_WINDOW ) ) {
		return new WP_Error( 'rate_limit', 'Too many requests', array( 'status' => 429 ) );
	}

	$params = $request->get_json_params();

	$occasion        = isset( $params['occasion'] ) ? sanitize_text_field( $params['occasion'] ) : '';
	$dedication_name = isset( $params['dedication_name'] ) ? sanitize_text_field( $params['dedication_name'] ) : '';
	$organizer_name  = isset( $params['organizer_name'] ) ? sanitize_text_field( $params['organizer_name'] ) : '';
	$goal_books      = isset( $params['goal_books'] ) ? absint( $params['goal_books'] ) : 0;
	$turnstile       = isset( $params['cf_turnstile_response'] ) ? sanitize_text_field( $params['cf_turnstile_response'] ) : '';

	if ( ! $occasion || ! $dedication_name || ! $organizer_name ) {
		return new WP_Error( 'invalid_params', 'Missing required fields', array( 'status' => 400 ) );
	}

	if ( mb_strlen( $dedication_name ) < 2 || mb_strlen( $dedication_name ) > 100 || mb_strlen( $organizer_name ) < 2 || mb_strlen( $organizer_name ) > 100 ) {
		return new WP_Error( 'invalid_length', 'Names must be between 2 and 100 characters', array( 'status' => 400 ) );
	}

	$goal_books = max( 1, min( 100, $goal_books ?: 1 ) );

	if ( ! tehilim_verify_turnstile( $turnstile ) ) {
		return new WP_Error( 'turnstile_failed', 'CAPTCHA verification failed', array( 'status' => 403 ) );
	}

	// Resolve occasion term (accepts term_id or slug)
	$term = is_numeric( $occasion )
		? get_term( absint( $occasion ), 'occasion' )
		: get_term_by( 'slug', $occasion, 'occasion' );

	if ( ! $term || is_wp_error( $term ) ) {
		return new WP_Error( 'invalid_occasion', 'Occasion not found', array( 'status' => 400 ) );
	}

	$campaign_id = wp_insert_post( array(
		'post_type'   => 'campaign',
		'post_title'  => $dedication_name,
		'post_status' => 'publish',
	), true );

	if ( is_wp_error( $campaign_id ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Tehilim: Campaign creation error: ' . $campaign_id->get_error_message() );
		}
		return new WP_Error( 'create_failed', 'Failed to create campaign', array( 'status' => 500 ) );
	}

	wp_set_object_terms( $campaign_id, $term->term_id, 'occasion' );
	update_post_meta( $campaign_id, 'goal_books', $goal_books );
	update_post_meta( $campaign_id, 'organizer_name', $organizer_name );

	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );

	return array(
		'success'      => true,
		'campaign_id'  => $campaign_id,
		'campaign_url' => esc_url_raw( get_permalink( $campaign_id ) ),
	);
}

/**
 * Handle ambassador join endpoint
 */
function tehilim_handle_ambassador_join( WP_REST_Request $request ) {
	if ( ! tehilim_check_rate_limit( 'ambassador_join', TEHILIM_RATE_LIMIT_AMBASSADOR, TEHILIM_RATE_LIMIT_WINDOW ) ) {
		return new WP_Error( 'rate_limit', 'Too many requests', array( 'status' => 429 ) );
	}

	$params = $request->get_json_params();

	$campaign_id = isset( $params['campaign_id'] ) ? absint( $params['campaign_id'] ) : 0;
	$name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
	$email = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';

	// Validate input
	if ( ! $campaign_id || ! $name || ! $email ) {
		return new WP_Error( 'invalid_params', 'Missing required fields', array( 'status' => 400 ) );
	}

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email', 'Invalid email address', array( 'status' => 400 ) );
	}

	if ( strlen( $name ) < 2 || strlen( $name ) > 100 ) {
		return new WP_Error( 'invalid_name', 'Name must be between 2 and 100 characters', array( 'status' => 400 ) );
	}

	// Verify campaign exists
	$campaign = get_post( $campaign_id );
	if ( ! $campaign || 'campaign' !== $campaign->post_type ) {
		return new WP_Error( 'not_found', 'Campaign not found', array( 'status' => 404 ) );
	}

	$ambassador_id = wp_insert_post( array(
		'post_type'   => 'ambassador',
		'post_title'  => $name,
		'post_status' => 'publish',
		'post_parent' => $campaign_id,
	) );

	if ( is_wp_error( $ambassador_id ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Tehilim: Ambassador creation error: ' . $ambassador_id->get_error_message() );
		}
		return new WP_Error( 'create_failed', 'Failed to create ambassador', array( 'status' => 500 ) );
	}

	update_post_meta( $ambassador_id, 'campaign_id', $campaign_id );
	update_post_meta( $ambassador_id, 'email', $email );

	// Assign a stable avatar color from the design palette
	$palette = array( '#C05A3A', '#D9A441', '#8A6B4A', '#B08968' );
	update_post_meta( $ambassador_id, 'avatar_color', $palette[ $ambassador_id % 4 ] );

	// Clear campaign caches to include new ambassador
	tehilim_clear_campaign_caches( $campaign_id );

	// Notify the campaign organizer (best-effort; never blocks the response)
	$organizer_email = get_post_meta( $campaign_id, 'organizer_email', true );
	if ( $organizer_email && is_email( $organizer_email ) ) {
		wp_mail(
			$organizer_email,
			sprintf( 'שגריר/ה חדש/ה בקמפיין "%s"', $campaign->post_title ),
			sprintf( "%s הצטרף/ה כשגריר/ה לקמפיין שלך.\n\nלצפייה בקמפיין: %s", $name, get_permalink( $campaign_id ) )
		);
	}

	$campaign_slug = get_post_field( 'post_name', $campaign_id );
	$ambassador_slug = get_post_field( 'post_name', $ambassador_id );

	if ( ! $campaign_slug || ! $ambassador_slug ) {
		return new WP_Error( 'slug_error', 'Failed to generate URLs', array( 'status' => 500 ) );
	}

	$personal_url = home_url( '/c/' . $campaign_slug . '/' . $ambassador_slug );

	$response = array(
		'success'       => true,
		'ambassador_id' => $ambassador_id,
		'personal_url'  => esc_url( $personal_url ),
	);

	// No caching for write operations
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );

	return $response;
}
