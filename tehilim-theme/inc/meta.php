<?php
/**
 * Meta Fields & Postmeta Management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clear campaign-related caches
 */
function tehilim_clear_campaign_caches( $campaign_id ) {
	delete_transient( 'tehilim_progress_' . $campaign_id );
	delete_transient( 'tehilim_participants_' . $campaign_id );
	for ( $i = 1; $i <= 100; $i++ ) {
		delete_transient( 'tehilim_ambassadors_' . $campaign_id . '_' . $i );
	}
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
	$cache_key = 'tehilim_progress_' . $campaign_id;
	$cached = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;

	$table = $wpdb->prefix . 'tehilim_recitations';
	$goal_books = intval( get_post_meta( $campaign_id, 'goal_books', true ) ?: 1 );

	// Per-chapter recitation counts. A book is complete only when EVERY one of
	// the 150 chapters has been said; the current cycle offers the chapters
	// that were not yet said in it.
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT chapter_number, COUNT(*) AS cnt FROM `%i` WHERE campaign_id = %d GROUP BY chapter_number",
		$table,
		$campaign_id
	) );

	$counts = array_fill( 1, TEHILIM_CHAPTERS_PER_BOOK, 0 );
	$total  = 0;
	foreach ( $rows as $row ) {
		$ch = intval( $row->chapter_number );
		if ( $ch >= 1 && $ch <= TEHILIM_CHAPTERS_PER_BOOK ) {
			$counts[ $ch ] = intval( $row->cnt );
			$total        += intval( $row->cnt );
		}
	}

	$books_done = min( $counts ); // full cycles completed
	$available  = array();        // chapters not yet said in the current cycle
	foreach ( $counts as $ch => $cnt ) {
		if ( $cnt === $books_done ) {
			$available[] = $ch;
		}
	}

	$chapters_in_book = TEHILIM_CHAPTERS_PER_BOOK - count( $available );
	$credited         = $books_done * TEHILIM_CHAPTERS_PER_BOOK + $chapters_in_book;
	$progress_percent = min( 100, intdiv( $credited * 100, max( 1, $goal_books ) * TEHILIM_CHAPTERS_PER_BOOK ) );

	$result = array(
		'books_done'       => $books_done,
		'chapters_done'    => $chapters_in_book,
		'total_chapters'   => $total,
		'goal_books'       => $goal_books,
		'progress_percent' => $progress_percent,
		'available'        => $available,
	);

	set_transient( $cache_key, $result, 300 );

	return $result;
}

/**
 * Chapters not yet recited in the current communal book cycle.
 */
function tehilim_get_available_chapters( $campaign_id ) {
	$progress = tehilim_get_campaign_progress( $campaign_id );
	return isset( $progress['available'] ) && $progress['available']
		? $progress['available']
		: range( 1, TEHILIM_CHAPTERS_PER_BOOK );
}

/**
 * Get top ambassadors for campaign
 */
function tehilim_get_top_ambassadors( $campaign_id, $limit = 3 ) {
	$cache_key = 'tehilim_ambassadors_' . $campaign_id . '_' . $limit;
	$cached = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

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

	set_transient( $cache_key, $ambassadors, 300 );

	return $ambassadors;
}

/**
 * Get the next suggested chapter for a campaign:
 * the first chapter that was not yet said in the current book cycle.
 */
function tehilim_get_next_chapter( $campaign_id ) {
	$available = tehilim_get_available_chapters( $campaign_id );
	return $available ? intval( $available[0] ) : 1;
}

/**
 * Get participant count (distinct named reciters) for a campaign
 */
function tehilim_get_campaign_participants( $campaign_id ) {
	$cache_key = 'tehilim_participants_' . $campaign_id;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return intval( $cached );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'tehilim_recitations';

	$count = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT reciter_name) FROM `%i` WHERE campaign_id = %d AND reciter_name IS NOT NULL AND reciter_name <> ''",
		$table,
		$campaign_id
	) );

	set_transient( $cache_key, $count, 300 );

	return $count;
}

/**
 * Get recent recitations for campaign
 */
function tehilim_get_recent_recitations( $campaign_id, $limit = 10 ) {
	global $wpdb;

	$table = $wpdb->prefix . 'tehilim_recitations';

	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, campaign_id, ambassador_id, chapter_number, reciter_name, created_at FROM `%i`
		 WHERE campaign_id = %d
		 ORDER BY created_at DESC
		 LIMIT %d",
		$table,
		$campaign_id,
		$limit
	) );

	return $results ?: array();
}
