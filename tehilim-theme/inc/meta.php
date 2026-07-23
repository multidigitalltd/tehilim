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

	register_post_meta( 'campaign', 'dedication_text', array(
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
		"SELECT chapter_number, COUNT(*) AS cnt FROM %i WHERE campaign_id = %d GROUP BY chapter_number",
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

	// Any real progress must be visible: round UP, so 13/7500 shows 1%, not 0%
	$progress_percent = $credited > 0
		? max( 1, min( 100, (int) ceil( $credited * 100 / ( max( 1, $goal_books ) * TEHILIM_CHAPTERS_PER_BOOK ) ) ) )
		: 0;

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
 * Curated verses in praise of saying Tehilim — shown in the campaign hero
 * when the campaign has no featured image ("no image" mode).
 *
 * @return array<int, array{text:string, source:string}>
 */
function tehilim_get_praise_verses() {
	$verses = array(
		array( 'text' => 'מִי יְמַלֵּל גְּבוּרוֹת ה׳, יַשְׁמִיעַ כָּל תְּהִלָּתוֹ.', 'source' => 'תהלים ק״ו' ),
		array( 'text' => 'טוֹב לְהֹדוֹת לַה׳, וּלְזַמֵּר לְשִׁמְךָ עֶלְיוֹן.', 'source' => 'תהלים צ״ב' ),
		array( 'text' => 'הַלְלוּיָהּ כִּי טוֹב זַמְּרָה אֱלֹהֵינוּ, כִּי נָעִים נָאוָה תְהִלָּה.', 'source' => 'תהלים קמ״ז' ),
		array( 'text' => 'כֹּל הַנְּשָׁמָה תְּהַלֵּל יָהּ, הַלְלוּיָהּ.', 'source' => 'תהלים ק״נ' ),
		array( 'text' => 'וַאֲנִי תְפִלָּתִי לְךָ ה׳ עֵת רָצוֹן, אֱלֹהִים בְּרׇב חַסְדֶּךָ.', 'source' => 'תהלים ס״ט' ),
		array( 'text' => 'כָּל הָאוֹמֵר תְּהִלָּה לְדָוִד בְּכָל יוֹם שָׁלוֹשׁ פְּעָמִים — מֻבְטָח לוֹ שֶׁהוּא בֶּן הָעוֹלָם הַבָּא.', 'source' => 'ברכות ד׳ ע״ב' ),
	);

	/**
	 * Allow customization of the praise verses shown in the no-image hero.
	 *
	 * @param array $verses List of { text, source } pairs.
	 */
	return apply_filters( 'tehilim_praise_verses', $verses );
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

	// Every APPROVED ambassador appears, including those with no recitations yet
	$posts = get_posts( array(
		'post_type'      => 'ambassador',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'campaign_id',
		'meta_value'     => $campaign_id,
	) );

	$counts = array();
	$rows   = $wpdb->get_results( $wpdb->prepare(
		"SELECT ambassador_id, COUNT(*) as count FROM %i
		 WHERE campaign_id = %d AND ambassador_id IS NOT NULL
		 GROUP BY ambassador_id",
		$table,
		$campaign_id
	) );
	foreach ( $rows as $row ) {
		$counts[ intval( $row->ambassador_id ) ] = intval( $row->count );
	}

	$ambassadors = array();
	foreach ( $posts as $post ) {
		$ambassadors[] = array(
			'id'    => $post->ID,
			'name'  => $post->post_title,
			'count' => isset( $counts[ $post->ID ] ) ? $counts[ $post->ID ] : 0,
		);
	}

	usort( $ambassadors, function( $a, $b ) {
		return $b['count'] - $a['count'];
	} );

	$ambassadors = array_slice( $ambassadors, 0, $limit );

	set_transient( $cache_key, $ambassadors, 300 );

	return $ambassadors;
}

/**
 * Live stats for a single ambassador within a campaign (tiles + ring).
 */
function tehilim_get_ambassador_stats( $campaign_id, $ambassador_id ) {
	global $wpdb;

	$table = $wpdb->prefix . 'tehilim_recitations';

	$chapters = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM %i WHERE campaign_id = %d AND ambassador_id = %d",
		$table,
		$campaign_id,
		$ambassador_id
	) );

	$reciters = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT COALESCE(NULLIF(reciter_name,''), NULLIF(visitor_key,''))) FROM %i WHERE campaign_id = %d AND ambassador_id = %d AND (COALESCE(reciter_name,'') <> '' OR COALESCE(visitor_key,'') <> '')",
		$table,
		$campaign_id,
		$ambassador_id
	) );

	$all  = tehilim_get_top_ambassadors( $campaign_id, 100 );
	$rank = count( $all );
	foreach ( $all as $i => $amb ) {
		if ( intval( $amb['id'] ) === intval( $ambassador_id ) ) {
			$rank = $i + 1;
			break;
		}
	}

	$progress      = tehilim_get_campaign_progress( $campaign_id );
	$goal_chapters = max( 1, intval( $progress['goal_books'] ) * TEHILIM_CHAPTERS_PER_BOOK );

	return array(
		'chapters'          => $chapters,
		'reciters'          => $reciters,
		'rank'              => $rank,
		'total_ambassadors' => max( 1, count( $all ) ),
		'goal_chapters'     => $goal_chapters,
		'ring_percent'      => $chapters > 0 ? max( 1, min( 100, (int) ceil( $chapters / $goal_chapters * 100 ) ) ) : 0,
	);
}

/**
 * Pending ambassador join requests for a campaign (awaiting owner approval)
 */
function tehilim_get_pending_ambassadors( $campaign_id ) {
	$posts = get_posts( array(
		'post_type'      => 'ambassador',
		'post_status'    => 'pending',
		'posts_per_page' => -1,
		'meta_key'       => 'campaign_id',
		'meta_value'     => $campaign_id,
		'orderby'        => 'date',
		'order'          => 'ASC',
	) );

	$pending = array();
	foreach ( $posts as $post ) {
		$pending[] = array(
			'id'    => $post->ID,
			'name'  => $post->post_title,
			'email' => get_post_meta( $post->ID, 'email', true ),
			'date'  => $post->post_date,
		);
	}

	return $pending;
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
		"SELECT COUNT(DISTINCT COALESCE(NULLIF(reciter_name,''), NULLIF(visitor_key,''))) FROM %i WHERE campaign_id = %d AND (COALESCE(reciter_name,'') <> '' OR COALESCE(visitor_key,'') <> '')",
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
		"SELECT id, campaign_id, ambassador_id, chapter_number, reciter_name, created_at FROM %i
		 WHERE campaign_id = %d
		 ORDER BY created_at DESC
		 LIMIT %d",
		$table,
		$campaign_id,
		$limit
	) );

	return $results ?: array();
}

/**
 * Site-wide homepage counters: fixed baseline + live activity on top.
 * Baselines are the numbers the site launched with; every real chapter,
 * completed book, campaign and participant increments them.
 */
function tehilim_get_site_stats() {
	$cached = get_transient( 'tehilim_site_stats' );
	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'tehilim_recitations';

	$baseline = apply_filters( 'tehilim_baseline_stats', array(
		'participants' => 4280,
		'chapters'     => 46800,
		'books'        => 312,
		'campaigns'    => 128,
	) );

	$chapters = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i", $table ) );

	$participants = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT COALESCE(NULLIF(reciter_name,''), NULLIF(visitor_key,''))) FROM %i WHERE (COALESCE(reciter_name,'') <> '' OR COALESCE(visitor_key,'') <> '')",
		$table
	) );

	$campaign_counts = wp_count_posts( 'campaign' );
	$campaigns       = $campaign_counts ? intval( $campaign_counts->publish ) : 0;

	$stats = array(
		'participants' => $baseline['participants'] + $participants,
		'chapters'     => $baseline['chapters'] + $chapters,
		'books'        => $baseline['books'] + intdiv( $chapters, TEHILIM_CHAPTERS_PER_BOOK ),
		'campaigns'    => $baseline['campaigns'] + $campaigns,
	);

	set_transient( 'tehilim_site_stats', $stats, 30 );

	return $stats;
}
