<?php
/**
 * Meta Fields & Postmeta Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register meta fields for campaign CPT
 */
function tehilim_register_campaign_meta() {
	register_post_meta( 'campaign', 'goal_books', array(
		'type'           => 'integer',
		'single'         => true,
		'show_in_rest'   => true,
		'auth_callback'  => '__return_true',
	) );

	register_post_meta( 'campaign', 'organizer_name', array(
		'type'           => 'string',
		'single'         => true,
		'show_in_rest'   => true,
		'auth_callback'  => '__return_true',
	) );

	register_post_meta( 'campaign', 'organizer_email', array(
		'type'           => 'string',
		'single'         => true,
		'show_in_rest'   => true,
		'auth_callback'  => '__return_true',
	) );
}
add_action( 'init', 'tehilim_register_campaign_meta' );

/**
 * Register meta fields for ambassador CPT
 */
function tehilim_register_ambassador_meta() {
	register_post_meta( 'ambassador', 'campaign_id', array(
		'type'           => 'integer',
		'single'         => true,
		'show_in_rest'   => true,
		'auth_callback'  => '__return_true',
	) );

	register_post_meta( 'ambassador', 'email', array(
		'type'           => 'string',
		'single'         => true,
		'show_in_rest'   => true,
		'auth_callback'  => '__return_true',
	) );

	register_post_meta( 'ambassador', 'avatar_color', array(
		'type'           => 'string',
		'single'         => true,
		'show_in_rest'   => true,
		'auth_callback'  => '__return_true',
	) );
}
add_action( 'init', 'tehilim_register_ambassador_meta' );

/**
 * Get campaign progress
 */
function tehilim_get_campaign_progress( $campaign_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'tehilim_recitations';
	$goal_books = intval( get_post_meta( $campaign_id, 'goal_books', true ) ?: 1 );

	$chapters_done = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT chapter_number) FROM `%i` WHERE campaign_id = %d",
		$table,
		$campaign_id
	) );

	$chapters_done = intval( $chapters_done ?: 0 );
	$books_done = intdiv( $chapters_done, 150 );
	$chapters_in_book = $chapters_done % 150;

	$progress_percent = min( 100, intdiv( $chapters_done * 100, $goal_books * 150 ) );

	return array(
		'books_done'       => $books_done,
		'chapters_done'    => $chapters_in_book,
		'total_chapters'   => $chapters_done,
		'goal_books'       => $goal_books,
		'progress_percent' => $progress_percent,
	);
}

/**
 * Get top ambassadors for campaign
 */
function tehilim_get_top_ambassadors( $campaign_id, $limit = 3 ) {
	global $wpdb;

	$table = $wpdb->prefix . 'tehilim_recitations';

	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT ambassador_id, COUNT(*) as count FROM `%i`
		 WHERE campaign_id = %d AND ambassador_id IS NOT NULL
		 GROUP BY ambassador_id
		 ORDER BY count DESC
		 LIMIT %d",
		$table,
		$campaign_id,
		$limit
	) );

	$ambassadors = array();
	foreach ( $results as $row ) {
		$ambassador = get_post( $row->ambassador_id );
		if ( $ambassador ) {
			$ambassadors[] = array(
				'id'    => $ambassador->ID,
				'name'  => $ambassador->post_title,
				'count' => intval( $row->count ),
			);
		}
	}

	return $ambassadors;
}

/**
 * Get recent recitations for campaign
 */
function tehilim_get_recent_recitations( $campaign_id, $limit = 10 ) {
	global $wpdb;

	$table = $wpdb->prefix . 'tehilim_recitations';

	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM `%i`
		 WHERE campaign_id = %d
		 ORDER BY created_at DESC
		 LIMIT %d",
		$table,
		$campaign_id,
		$limit
	) );

	return $results ?: array();
}
