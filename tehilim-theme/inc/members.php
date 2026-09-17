<?php
/**
 * Tehilim Members ("חברי תהילים") — people who subscribe to hear about new
 * Tehilim groups and join in saying Psalms.
 *
 * - Private `tmember` CPT stores name + email.
 * - REST POST /members/join adds a subscriber.
 * - When a new campaign is published, members are emailed (in the background).
 * - Admin list + CSV export under the Tehilim menu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the members CPT (private — never public-facing).
 */
function tehilim_register_member_cpt() {
	register_post_type( 'tmember', array(
		'labels'          => array(
			'name'          => 'חברי תהילים',
			'singular_name' => 'חבר תהילים',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => 'tehilim-settings',
		'supports'        => array( 'title', 'custom-fields' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
	) );
}
add_action( 'init', 'tehilim_register_member_cpt' );

/**
 * REST route for joining the members list.
 */
function tehilim_register_member_routes() {
	register_rest_route( 'tehilim/v1', '/members/join', array(
		'methods'             => 'POST',
		'callback'            => 'tehilim_handle_member_join',
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'tehilim_register_member_routes' );

/**
 * Handle a members-list signup.
 */
function tehilim_handle_member_join( WP_REST_Request $request ) {
	if ( function_exists( 'tehilim_check_rate_limit' ) && ! tehilim_check_rate_limit( 'member_join', 5, HOUR_IN_SECONDS ) ) {
		return new WP_Error( 'rate_limit', 'Too many requests', array( 'status' => 429 ) );
	}

	$params = $request->get_json_params();
	$name   = isset( $params['name'] ) ? mb_substr( sanitize_text_field( $params['name'] ), 0, 100 ) : '';
	$email  = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';

	if ( ! $email || ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email', 'Invalid email address', array( 'status' => 400 ) );
	}
	if ( '' === $name ) {
		$name = current( explode( '@', $email ) );
	}

	// De-dupe by email
	$existing = get_posts( array(
		'post_type'      => 'tmember',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_key'       => 'email',
		'meta_value'     => $email,
		'fields'         => 'ids',
	) );

	if ( $existing ) {
		header( 'Cache-Control: no-store' );
		return array( 'success' => true, 'already' => true );
	}

	$member_id = wp_insert_post( array(
		'post_type'   => 'tmember',
		'post_title'  => $name,
		'post_status' => 'publish',
	), true );

	if ( is_wp_error( $member_id ) ) {
		return new WP_Error( 'create_failed', 'Failed to subscribe', array( 'status' => 500 ) );
	}

	update_post_meta( $member_id, 'email', $email );
	update_post_meta( $member_id, 'joined', current_time( 'mysql', true ) );

	// Welcome email
	wp_mail(
		$email,
		'ברוכים הבאים לחברי תהילים!',
		"תודה שהצטרפתם לחברי תהילים.\n\nמעכשיו תקבלו עדכון בכל פעם שנפתחת קבוצת תהילים חדשה, ותוכלו להצטרף ולומר פרקי תהילים יחד עם הקהילה.\n\nיחד — כי לכל פרק יש כוח."
	);

	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );

	return array( 'success' => true );
}

/**
 * Broadcast a newly published campaign to all members (in the background).
 */
function tehilim_broadcast_campaign_on_publish( $new_status, $old_status, $post ) {
	if ( 'campaign' !== $post->post_type || 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}
	if ( get_post_meta( $post->ID, '_tehilim_demo', true ) ) {
		return; // demo content never emails members
	}
	if ( get_post_meta( $post->ID, '_tehilim_broadcast_done', true ) ) {
		return;
	}

	// Send shortly after, off the request, so publishing stays snappy
	wp_schedule_single_event( time() + 30, 'tehilim_send_campaign_broadcast', array( $post->ID ) );
}
add_action( 'transition_post_status', 'tehilim_broadcast_campaign_on_publish', 10, 3 );

/**
 * The background broadcast worker.
 */
function tehilim_send_campaign_broadcast( $campaign_id ) {
	$campaign = get_post( $campaign_id );
	if ( ! $campaign || 'campaign' !== $campaign->post_type || 'publish' !== $campaign->post_status ) {
		return;
	}
	if ( get_post_meta( $campaign_id, '_tehilim_broadcast_done', true ) ) {
		return;
	}
	update_post_meta( $campaign_id, '_tehilim_broadcast_done', 1 );

	$members = get_posts( array(
		'post_type'      => 'tmember',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	if ( ! $members ) {
		return;
	}

	$occ = get_the_terms( $campaign_id, 'occasion' );
	$occ = ( $occ && ! is_wp_error( $occ ) ) ? $occ[0]->name : '';
	$url = get_permalink( $campaign_id );

	$subject = sprintf( 'קבוצת תהילים חדשה: %s', $campaign->post_title );
	$body    = sprintf(
		"נפתחה קבוצת תהילים חדשה%s:\n\n%s\n\nהצטרפו ואמרו כמה פרקי תהילים — כל פרק מצטרף למניין הקהילה ונזקף לזכות.\n\nלכניסה ולאמירת תהילים:\n%s\n\nחברי תהילים · יחד לכל פרק יש כוח.",
		$occ ? ' ל' . $occ : '',
		$campaign->post_title,
		$url
	);

	foreach ( $members as $mid ) {
		$email = get_post_meta( $mid, 'email', true );
		if ( $email && is_email( $email ) ) {
			wp_mail( $email, $subject, $body );
		}
	}
}
add_action( 'tehilim_send_campaign_broadcast', 'tehilim_send_campaign_broadcast', 10, 1 );

/**
 * Total member count (cached briefly).
 */
function tehilim_member_count() {
	$counts = wp_count_posts( 'tmember' );
	return $counts ? intval( $counts->publish ) : 0;
}

/**
 * CSV export of the members list (site admins only).
 */
function tehilim_export_members() {
	if ( ! isset( $_GET['tehilim_export_members'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'tehilim_export_members' );

	$members = get_posts( array(
		'post_type'      => 'tmember',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	) );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=tehilim-members.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel
	fputcsv( $out, array( 'שם', 'אימייל', 'תאריך הצטרפות' ) );
	foreach ( $members as $m ) {
		fputcsv( $out, array(
			$m->post_title,
			get_post_meta( $m->ID, 'email', true ),
			get_post_meta( $m->ID, 'joined', true ),
		) );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_init', 'tehilim_export_members' );
