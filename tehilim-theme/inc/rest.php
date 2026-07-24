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
	// NOTE: no 'args' schema here on purpose — nullable JSON fields (e.g.
	// ambassador_id: null) trip WP's arg validation with rest_invalid_param.
	// tehilim_handle_recitation() fully validates and sanitizes every input.
	register_rest_route( 'tehilim/v1', '/recitations', array(
		'methods'             => 'POST',
		'callback'            => 'tehilim_handle_recitation',
		'permission_callback' => function() {
			return true; // Public campaigns allow unauthenticated recitations; rate-limiting + CAPTCHA enforce security
		},
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

	register_rest_route( 'tehilim/v1', '/campaigns/(?P<id>\d+)/update', array(
		'methods'             => 'POST',
		'callback'            => 'tehilim_handle_campaign_update',
		'permission_callback' => 'is_user_logged_in',
	) );

	register_rest_route( 'tehilim/v1', '/site-stats', array(
		'methods'             => 'GET',
		'callback'            => 'tehilim_get_site_stats_endpoint',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'tehilim/v1', '/ambassadors/join', array(
		'methods'             => 'POST',
		'callback'            => 'tehilim_handle_ambassador_join',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'tehilim/v1', '/ambassadors/(?P<id>\d+)/moderate', array(
		'methods'             => 'POST',
		'callback'            => 'tehilim_handle_ambassador_moderate',
		'permission_callback' => 'is_user_logged_in',
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
	// No rate limit here by design: saying many chapters in a row is the whole
	// point. Turnstile (when configured) still guards against bots.

	$params = $request->get_json_params();

	$campaign_id = isset( $params['campaign_id'] ) ? absint( $params['campaign_id'] ) : 0;
	$chapter_number = isset( $params['chapter_number'] ) ? absint( $params['chapter_number'] ) : 0;
	$ambassador_id = isset( $params['ambassador_id'] ) ? absint( $params['ambassador_id'] ) : null;
	$reciter_name = isset( $params['reciter_name'] ) ? mb_substr( sanitize_text_field( $params['reciter_name'] ), 0, 100 ) : '';
	$visitor_key  = isset( $params['visitor_key'] ) ? substr( preg_replace( '/[^a-zA-Z0-9]/', '', (string) $params['visitor_key'] ), 0, 64 ) : '';
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
	$row   = array(
		'campaign_id'    => $campaign_id,
		'ambassador_id'  => $ambassador_id,
		'chapter_number' => $chapter_number,
		'reciter_name'   => $reciter_name,
		'visitor_key'    => $visitor_key,
	);
	$formats = array( '%d', '%d', '%d', '%s', '%s' );

	$inserted = $wpdb->insert( $table, $row, $formats );

	if ( false === $inserted ) {
		// Table may be missing or carry a legacy schema — heal and retry once
		tehilim_create_recitations_table( true );
		$wpdb->last_error = '';
		$inserted = $wpdb->insert( $table, $row, $formats );
	}

	if ( false === $inserted ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Tehilim: Database error during recitation insert: ' . $wpdb->last_error );
		}
		return new WP_Error( 'db_error', 'Failed to record recitation', array( 'status' => 500 ) );
	}

	// Clear campaign caches to ensure fresh stats
	tehilim_clear_campaign_caches( $campaign_id );

	// Fresh stats after the insert — lets the client update the UI instantly
	$stats = tehilim_build_stats_payload( $campaign_id, intval( $ambassador_id ) );

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
function tehilim_build_stats_payload( $campaign_id, $ambassador_id = 0 ) {
	global $wpdb;

	$progress = tehilim_get_campaign_progress( $campaign_id );

	// Count APPROVED ambassadors (cached helper) — not only those with recitations
	$total_ambassadors = count( tehilim_get_top_ambassadors( $campaign_id, 100 ) );

	$in_book   = intval( $progress['chapters_done'] );
	$available = isset( $progress['available'] ) && $progress['available']
		? array_map( 'intval', $progress['available'] )
		: range( 1, TEHILIM_CHAPTERS_PER_BOOK );

	$payload = array(
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

	// Ambassador-scoped tiles/ring for personal pages
	if ( $ambassador_id > 0 && function_exists( 'tehilim_get_ambassador_stats' ) ) {
		$amb_stats          = tehilim_get_ambassador_stats( $campaign_id, $ambassador_id );
		$amb_stats['books'] = intdiv( intval( $amb_stats['chapters'] ), TEHILIM_CHAPTERS_PER_BOOK );
		$payload['ambassador'] = $amb_stats;
	}

	return $payload;
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

	// Never cache: the client polls this for live progress, and any HTTP-level
	// caching makes counters appear frozen after a recitation
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );

	return tehilim_build_stats_payload( $campaign_id, absint( $request->get_param( 'ambassador_id' ) ) );
}

/**
 * Site-wide totals for the homepage counters (baseline + live activity)
 */
function tehilim_get_site_stats_endpoint() {
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	return tehilim_get_site_stats();
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
	// Campaign creation requires a logged-in user (enforced server-side;
	// the create page also gates the form behind login).
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'login_required', 'Login required to create a campaign', array( 'status' => 401 ) );
	}

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

	// New campaigns await site-admin approval before going live
	$campaign_id = wp_insert_post( array(
		'post_type'   => 'campaign',
		'post_title'  => $dedication_name,
		'post_status' => 'pending',
		'post_author' => get_current_user_id(),
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

	if ( ! empty( $params['dedication_text'] ) ) {
		update_post_meta( $campaign_id, 'dedication_text', mb_substr( sanitize_text_field( $params['dedication_text'] ), 0, 200 ) );
	}

	// Optional campaign image (data URL). Invalid images are ignored silently —
	// the campaign still succeeds and falls back to the praise-verses hero.
	if ( ! empty( $params['image_data'] ) && is_string( $params['image_data'] ) ) {
		tehilim_attach_image_from_data_url( $campaign_id, $params['image_data'] );
	}

	// Notify the site admin that a campaign awaits approval
	$admin_email = get_option( 'admin_email' );
	if ( $admin_email && is_email( $admin_email ) ) {
		wp_mail(
			$admin_email,
			sprintf( 'קבוצת תהילים חדשה ממתין לאישור: "%s"', $dedication_name ),
			sprintf(
				"קבוצת תהילים חדשה נוצר באתר וממתין לאישורך.\n\nשם ההקדשה: %s\nמארגן: %s\nיעד: %d ספרים\n\nלאישור ופרסום:\n%s\n\nלכל קבוצות התהילים הממתינים:\n%s",
				$dedication_name,
				$organizer_name,
				$goal_books,
				admin_url( 'post.php?post=' . $campaign_id . '&action=edit' ),
				admin_url( 'edit.php?post_status=pending&post_type=campaign' )
			)
		);
	}

	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );

	return array(
		'success'     => true,
		'pending'     => true,
		'campaign_id' => $campaign_id,
		'account_url' => esc_url_raw( function_exists( 'tehilim_account_page_url' ) ? tehilim_account_page_url() : home_url( '/' ) ),
	);
}

/**
 * Handle campaign update endpoint (owner-only, from the personal area)
 */
function tehilim_handle_campaign_update( WP_REST_Request $request ) {
	$campaign_id = absint( $request->get_param( 'id' ) );
	$campaign    = get_post( $campaign_id );

	if ( ! $campaign || 'campaign' !== $campaign->post_type ) {
		return new WP_Error( 'not_found', 'Campaign not found', array( 'status' => 404 ) );
	}

	// Only the campaign owner (or an editor/admin) may manage it
	$is_owner = intval( $campaign->post_author ) === get_current_user_id();
	if ( ! $is_owner && ! current_user_can( 'edit_post', $campaign_id ) ) {
		return new WP_Error( 'forbidden', 'You cannot manage this campaign', array( 'status' => 403 ) );
	}

	$params  = $request->get_json_params();
	$updates = array( 'ID' => $campaign_id );

	if ( isset( $params['dedication_name'] ) ) {
		$title = sanitize_text_field( $params['dedication_name'] );
		if ( mb_strlen( $title ) < 2 || mb_strlen( $title ) > 100 ) {
			return new WP_Error( 'invalid_length', 'Name must be between 2 and 100 characters', array( 'status' => 400 ) );
		}
		$updates['post_title'] = $title;
	}

	if ( isset( $params['description'] ) ) {
		$updates['post_content'] = wp_kses_post( mb_substr( (string) $params['description'], 0, 2000 ) );
	}

	if ( count( $updates ) > 1 ) {
		$result = wp_update_post( $updates, true );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'update_failed', 'Failed to update campaign', array( 'status' => 500 ) );
		}
	}

	if ( isset( $params['goal_books'] ) ) {
		$goal = max( 1, min( 100, absint( $params['goal_books'] ) ) );
		update_post_meta( $campaign_id, 'goal_books', $goal );
	}

	if ( isset( $params['dedication_text'] ) ) {
		update_post_meta( $campaign_id, 'dedication_text', mb_substr( sanitize_text_field( $params['dedication_text'] ), 0, 200 ) );
	}

	if ( ! empty( $params['occasion'] ) ) {
		$occasion = sanitize_text_field( $params['occasion'] );
		$term     = is_numeric( $occasion )
			? get_term( absint( $occasion ), 'occasion' )
			: get_term_by( 'slug', $occasion, 'occasion' );
		if ( $term && ! is_wp_error( $term ) ) {
			wp_set_object_terms( $campaign_id, $term->term_id, 'occasion' );
		}
	}

	// Image management: replace, or remove (falls back to the verses hero)
	if ( ! empty( $params['remove_image'] ) ) {
		delete_post_thumbnail( $campaign_id );
	} elseif ( ! empty( $params['image_data'] ) && is_string( $params['image_data'] ) ) {
		tehilim_attach_image_from_data_url( $campaign_id, $params['image_data'] );
	}

	tehilim_clear_campaign_caches( $campaign_id );

	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );

	return array(
		'success'      => true,
		'campaign_id'  => $campaign_id,
		'campaign_url' => esc_url_raw( get_permalink( $campaign_id ) ),
	);
}

/**
 * Decode a base64 data URL and attach it as the campaign's featured image.
 * Strictly validates mime type and size. Returns attachment ID or false.
 */
function tehilim_attach_image_from_data_url( $campaign_id, $data_url ) {
	if ( ! preg_match( '#^data:image/(jpeg|png|webp);base64,#', $data_url, $m ) ) {
		return false;
	}

	$ext     = ( 'jpeg' === $m[1] ) ? 'jpg' : $m[1];
	$b64     = substr( $data_url, strpos( $data_url, ',' ) + 1 );
	$decoded = base64_decode( $b64, true );

	if ( false === $decoded || strlen( $decoded ) > 3 * 1024 * 1024 || strlen( $decoded ) < 64 ) {
		return false;
	}

	// Confirm the bytes really are an image of the claimed type
	$finfo = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : false;
	if ( $finfo ) {
		$real = finfo_buffer( $finfo, $decoded );
		finfo_close( $finfo );
		if ( 'image/' . $m[1] !== $real ) {
			return false;
		}
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$filename = 'campaign-' . $campaign_id . '-' . wp_generate_password( 6, false ) . '.' . $ext;
	$upload   = wp_upload_bits( $filename, null, $decoded );

	if ( ! empty( $upload['error'] ) ) {
		return false;
	}

	// Re-validate the written file through WordPress's own checker
	$check = wp_check_filetype_and_ext( $upload['file'], $upload['file'] );
	if ( empty( $check['type'] ) || 0 !== strpos( $check['type'], 'image/' ) ) {
		@unlink( $upload['file'] );
		return false;
	}

	$attachment_id = wp_insert_attachment( array(
		'post_mime_type' => $check['type'],
		'post_title'     => get_the_title( $campaign_id ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	), $upload['file'], $campaign_id );

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		@unlink( $upload['file'] );
		return false;
	}

	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	set_post_thumbnail( $campaign_id, $attachment_id );

	return $attachment_id;
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

	// One open request per email per campaign
	$existing = get_posts( array(
		'post_type'      => 'ambassador',
		'post_status'    => array( 'pending', 'publish' ),
		'posts_per_page' => 1,
		'meta_query'     => array(
			array( 'key' => 'campaign_id', 'value' => $campaign_id ),
			array( 'key' => 'email', 'value' => $email ),
		),
	) );
	if ( $existing ) {
		return new WP_Error( 'already_requested', 'A request for this email already exists', array( 'status' => 409 ) );
	}

	// The request awaits the campaign owner's approval
	$ambassador_id = wp_insert_post( array(
		'post_type'   => 'ambassador',
		'post_title'  => $name,
		'post_status' => 'pending',
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

	// Ambassador's personal goal (whole books they commit to recruit)
	$amb_goal = isset( $params['goal_books'] ) ? max( 1, min( 100, absint( $params['goal_books'] ) ) ) : 1;
	update_post_meta( $ambassador_id, 'goal_books', $amb_goal );

	// Assign a stable avatar color from the design palette
	$palette = array( '#C05A3A', '#D9A441', '#8A6B4A', '#B08968' );
	update_post_meta( $ambassador_id, 'avatar_color', $palette[ $ambassador_id % 4 ] );

	// Notify the campaign organizer that a request awaits approval
	$organizer_email = tehilim_campaign_owner_email( $campaign_id );
	if ( $organizer_email ) {
		$account_url = function_exists( 'tehilim_account_page_url' ) ? tehilim_account_page_url() : admin_url();
		wp_mail(
			$organizer_email,
			sprintf( 'בקשת שגריר חדשה בקבוצה "%s"', $campaign->post_title ),
			sprintf(
				"%s (%s) מבקש/ת להצטרף כשגריר/ה לקבוצה \"%s\" עם יעד אישי של %d ספרים.\n\nלאישור או דחייה של הבקשה היכנסו לאזור האישי:\n%s",
				$name,
				$email,
				$campaign->post_title,
				$amb_goal,
				$account_url
			)
		);
	}

	$response = array(
		'success' => true,
		'pending' => true,
	);

	// No caching for write operations
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );

	return $response;
}

/**
 * Best email for the campaign owner: organizer_email meta, else author email.
 */
function tehilim_campaign_owner_email( $campaign_id ) {
	$email = get_post_meta( $campaign_id, 'organizer_email', true );
	if ( $email && is_email( $email ) ) {
		return $email;
	}
	$author = get_userdata( intval( get_post_field( 'post_author', $campaign_id ) ) );
	return ( $author && is_email( $author->user_email ) ) ? $author->user_email : '';
}

/**
 * Approve / reject an ambassador request (campaign owner only).
 */
function tehilim_handle_ambassador_moderate( WP_REST_Request $request ) {
	$ambassador_id = absint( $request->get_param( 'id' ) );
	$ambassador    = get_post( $ambassador_id );

	if ( ! $ambassador || 'ambassador' !== $ambassador->post_type ) {
		return new WP_Error( 'not_found', 'Ambassador not found', array( 'status' => 404 ) );
	}

	$campaign_id = intval( get_post_meta( $ambassador_id, 'campaign_id', true ) );
	$campaign    = $campaign_id ? get_post( $campaign_id ) : null;
	if ( ! $campaign || 'campaign' !== $campaign->post_type ) {
		return new WP_Error( 'not_found', 'Campaign not found', array( 'status' => 404 ) );
	}

	$is_owner = intval( $campaign->post_author ) === get_current_user_id();
	if ( ! $is_owner && ! current_user_can( 'edit_post', $campaign_id ) ) {
		return new WP_Error( 'forbidden', 'You cannot manage this campaign', array( 'status' => 403 ) );
	}

	$params = $request->get_json_params();
	$action = isset( $params['action'] ) ? sanitize_key( $params['action'] ) : '';

	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );

	if ( 'reject' === $action ) {
		wp_trash_post( $ambassador_id );
		tehilim_clear_campaign_caches( $campaign_id );
		return array( 'success' => true, 'status' => 'rejected' );
	}

	if ( 'approve' !== $action ) {
		return new WP_Error( 'invalid_params', 'Unknown action', array( 'status' => 400 ) );
	}

	$updated = wp_update_post( array(
		'ID'          => $ambassador_id,
		'post_status' => 'publish',
	), true );

	if ( is_wp_error( $updated ) ) {
		return new WP_Error( 'update_failed', 'Failed to approve ambassador', array( 'status' => 500 ) );
	}

	tehilim_clear_campaign_caches( $campaign_id );

	// Personal referral URL (slug is finalized on publish)
	$campaign_slug   = get_post_field( 'post_name', $campaign_id );
	$ambassador_slug = get_post_field( 'post_name', $ambassador_id );
	$personal_url    = home_url( '/c/' . $campaign_slug . '/' . $ambassador_slug );

	// Email the ambassador: their personal page + a ready-to-share link
	$amb_email = get_post_meta( $ambassador_id, 'email', true );
	if ( $amb_email && is_email( $amb_email ) ) {
		$share_text = sprintf( 'הצטרפו אליי לאמירת תהילים בקבוצה "%s": %s', $campaign->post_title, $personal_url );
		wp_mail(
			$amb_email,
			sprintf( 'אושרתם כשגריר/ה בקבוצה "%s"!', $campaign->post_title ),
			sprintf(
				"מזל טוב! מנהל הקבוצה אישר את הצטרפותכם כשגריר/ה.\n\nהעמוד האישי שלכם:\n%s\n\nקישור מוכן לשיתוף (העתיקו ושלחו לחברים):\n%s\n\nכל פרק שייאמר דרך הקישור שלכם נזקף לזכותכם בלוח השגרירים.",
				$personal_url,
				$share_text
			)
		);
	}

	return array(
		'success'      => true,
		'status'       => 'approved',
		'personal_url' => esc_url_raw( $personal_url ),
	);
}
