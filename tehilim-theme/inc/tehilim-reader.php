<?php
/**
 * Full Tehilim reader — SEO pages for the whole Book of Psalms.
 *
 * Serves /tehilim/ (index of all 150 chapters) and /tehilim/{n}
 * (a page per chapter) rendered from the bundled public-domain text.
 * Virtual pages (no DB posts) — high-intent "תהילים פרק כ״ג" search targets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite rules for the reader.
 */
function tehilim_reader_rewrites() {
	add_rewrite_rule( '^tehilim/?$', 'index.php?tehilim_reader=index', 'top' );
	add_rewrite_rule( '^tehilim-yomi/?$', 'index.php?tehilim_reader=yomi', 'top' );
	add_rewrite_rule( '^tehilim-lefi-shem/?$', 'index.php?tehilim_reader=name', 'top' );
	add_rewrite_rule( '^tehilim/([0-9]{1,3})/?$', 'index.php?tehilim_reader=chapter&tehilim_chapter=$matches[1]', 'top' );
}
add_action( 'init', 'tehilim_reader_rewrites' );

/**
 * Register query vars.
 */
function tehilim_reader_query_vars( $vars ) {
	$vars[] = 'tehilim_reader';
	$vars[] = 'tehilim_chapter';
	return $vars;
}
add_filter( 'query_vars', 'tehilim_reader_query_vars' );

/**
 * Flush rewrites once so /tehilim/ and friends resolve.
 */
function tehilim_reader_maybe_flush() {
	if ( get_option( 'tehilim_reader_rewrites' ) !== '2' ) {
		tehilim_reader_rewrites();
		flush_rewrite_rules();
		update_option( 'tehilim_reader_rewrites', '2' );
	}
}
add_action( 'init', 'tehilim_reader_maybe_flush', 99 );

/**
 * Current reader view: '' (none), 'index', or 'chapter'. For 'chapter',
 * also validates the chapter number (1–150) or triggers a 404.
 */
function tehilim_reader_view() {
	$view = get_query_var( 'tehilim_reader' );
	if ( in_array( $view, array( 'index', 'yomi', 'name' ), true ) ) {
		return $view;
	}
	if ( 'chapter' === $view ) {
		$n = intval( get_query_var( 'tehilim_chapter' ) );
		return ( $n >= 1 && $n <= 150 ) ? 'chapter' : '404';
	}
	return '';
}

/**
 * Route reader URLs to the theme templates.
 */
function tehilim_reader_template( $template ) {
	$view = tehilim_reader_view();

	if ( '404' === $view ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		return get_query_template( '404' );
	}
	$map = array(
		'index'   => '/tehilim-index.php',
		'chapter' => '/tehilim-chapter.php',
		'yomi'    => '/tehilim-yomi.php',
		'name'    => '/tehilim-name.php',
	);
	if ( isset( $map[ $view ] ) ) {
		$f = get_template_directory() . $map[ $view ];
		if ( file_exists( $f ) ) {
			return $f;
		}
	}
	return $template;
}
add_filter( 'template_include', 'tehilim_reader_template' );

/**
 * Reader pages are public content — never page-cache-block them, but do set
 * a correct <title>.
 */
function tehilim_reader_title( $parts ) {
	$view = tehilim_reader_view();
	if ( 'index' === $view ) {
		$parts['title'] = 'ספר תהילים המלא — כל 150 הפרקים';
	} elseif ( 'chapter' === $view ) {
		$n              = intval( get_query_var( 'tehilim_chapter' ) );
		$gem            = function_exists( 'tehilim_hebrew_numeral' ) ? tehilim_hebrew_numeral( $n ) : $n;
		$parts['title'] = 'תהילים פרק ' . $gem;
	} elseif ( 'yomi' === $view ) {
		$parts['title'] = 'תהילים יומי — הפרקים לכל יום בחודש';
	} elseif ( 'name' === $view ) {
		$parts['title'] = 'תהילים לפי שם — פרקי קרע שטן והשם';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'tehilim_reader_title' );

/**
 * Monthly Tehilim division ("תהילים לימי החודש"). Chapter-level; the long
 * chapter קי״ט spans days 25–26 (labeled), matching the printed custom.
 * Returns day number => array( from_chapter, to_chapter, note ).
 */
function tehilim_reader_monthly_division() {
	return array(
		1  => array( 1, 9, '' ),
		2  => array( 10, 17, '' ),
		3  => array( 18, 22, '' ),
		4  => array( 23, 28, '' ),
		5  => array( 29, 34, '' ),
		6  => array( 35, 38, '' ),
		7  => array( 39, 43, '' ),
		8  => array( 44, 48, '' ),
		9  => array( 49, 54, '' ),
		10 => array( 55, 59, '' ),
		11 => array( 60, 65, '' ),
		12 => array( 66, 68, '' ),
		13 => array( 69, 71, '' ),
		14 => array( 72, 76, '' ),
		15 => array( 77, 78, '' ),
		16 => array( 79, 82, '' ),
		17 => array( 83, 87, '' ),
		18 => array( 88, 89, '' ),
		19 => array( 90, 96, '' ),
		20 => array( 97, 103, '' ),
		21 => array( 104, 105, '' ),
		22 => array( 106, 107, '' ),
		23 => array( 108, 112, '' ),
		24 => array( 113, 118, '' ),
		25 => array( 119, 119, 'עד פסוק צ״ו' ),
		26 => array( 120, 134, 'קי״ט מפסוק צ״ז ואילך, וכן פרקים ק״כ–קל״ד' ),
		27 => array( 135, 139, '' ),
		28 => array( 140, 144, '' ),
		29 => array( 145, 150, '' ),
	);
}

/**
 * Today's Hebrew day of the month (1–30) when the PHP calendar extension is
 * available; otherwise null (the page then shows only the full table).
 */
function tehilim_reader_hebrew_day() {
	if ( ! function_exists( 'jdtojewish' ) || ! function_exists( 'gregoriantojd' ) ) {
		return null;
	}
	$ts  = current_time( 'timestamp' );
	$jd  = gregoriantojd( (int) gmdate( 'n', $ts ), (int) gmdate( 'j', $ts ), (int) gmdate( 'Y', $ts ) );
	$heb = jdtojewish( $jd ); // "month/day/year"
	$parts = explode( '/', $heb );
	return isset( $parts[1] ) ? intval( $parts[1] ) : null;
}

/**
 * Chapters traditionally associated with common needs — surfaced on the
 * index for internal linking and search intent.
 */
function tehilim_reader_topics() {
	return array(
		'לרפואה'   => array( 6, 20, 23, 30, 41, 103 ),
		'לפרנסה'   => array( 23, 24, 34, 67, 104, 145 ),
		'לזיווג'   => array( 32, 70, 72, 121 ),
		'לשמירה'   => array( 3, 91, 121 ),
		'להודיה'   => array( 100, 103, 136, 150 ),
		'לעת צרה'  => array( 20, 102, 130, 142 ),
	);
}
