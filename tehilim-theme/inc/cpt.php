<?php
/**
 * Custom Post Types & Taxonomy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TEHILIM_CHAPTERS_PER_BOOK = 150;
const TEHILIM_RATE_LIMIT_WINDOW = 3600;
const TEHILIM_RATE_LIMIT_RECITATIONS = 10;
const TEHILIM_RATE_LIMIT_AMBASSADOR = 1;

/**
 * Register Campaign CPT
 */
function tehilim_register_campaign_cpt() {
	register_post_type( 'campaign', array(
		'label'              => 'קמפיינים',
		'public'             => true,
		'has_archive'        => true,
		'show_in_rest'       => true,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'rewrite'            => array( 'slug' => 'campaigns', 'with_front' => false ),
		'menu_icon'          => 'dashicons-book',
		'capability_type'    => 'post',
	) );
}
add_action( 'init', 'tehilim_register_campaign_cpt' );

/**
 * Register Ambassador CPT
 */
function tehilim_register_ambassador_cpt() {
	register_post_type( 'ambassador', array(
		'label'           => 'שגרירים',
		'public'          => true,
		'show_in_rest'    => true,
		'supports'        => array( 'title', 'custom-fields' ),
		'rewrite'         => array( 'slug' => 'ambassadors', 'with_front' => false ),
		'menu_icon'       => 'dashicons-people',
		'capability_type' => 'post',
	) );
}
add_action( 'init', 'tehilim_register_ambassador_cpt' );

/**
 * Register Occasion Taxonomy
 */
function tehilim_register_occasion_taxonomy() {
	register_taxonomy( 'occasion', 'campaign', array(
		'label'         => 'סיבות',
		'public'        => true,
		'show_in_rest'  => true,
		'hierarchical'  => false,
		'rewrite'       => array( 'slug' => 'occasion', 'with_front' => false ),
	) );
}
add_action( 'init', 'tehilim_register_occasion_taxonomy' );

/**
 * Insert default occasions
 */
function tehilim_insert_default_occasions() {
	$occasions = array(
		'refua'   => 'רפואה',
		'iluy'    => 'עליית הנשמה',
		'zivug'   => 'זיווג',
		'parnasa' => 'פרנסה',
		'zchut'   => 'זכות',
		'event'   => 'אירוע',
	);

	foreach ( $occasions as $slug => $name ) {
		$existing = term_exists( $slug, 'occasion' );

		if ( ! $existing ) {
			wp_insert_term( $name, 'occasion', array( 'slug' => $slug ) );
			continue;
		}

		// Legacy installs may carry English names (e.g. "Healing (Refua)") —
		// normalize them to the Hebrew names once.
		$term_id = is_array( $existing ) ? intval( $existing['term_id'] ) : intval( $existing );
		$term    = get_term( $term_id, 'occasion' );
		if ( $term && ! is_wp_error( $term ) && $term->name !== $name ) {
			wp_update_term( $term_id, 'occasion', array( 'name' => $name ) );
		}
	}
}
add_action( 'init', 'tehilim_insert_default_occasions', 20 );

/**
 * Custom Rewrite Rule for /c/{campaign}/{ambassador}
 */
function tehilim_add_rewrite_rules() {
	add_rewrite_rule(
		'^c/([^/]+)/([^/]+)/?$',
		'index.php?post_type=campaign&name=$matches[1]&ambassador=$matches[2]',
		'top'
	);
}
add_action( 'init', 'tehilim_add_rewrite_rules' );

/**
 * Register the ambassador query var (without this, WP drops it from the rewrite)
 */
function tehilim_register_query_vars( $vars ) {
	$vars[] = 'ambassador';
	return $vars;
}
add_filter( 'query_vars', 'tehilim_register_query_vars' );

/**
 * Route referral URLs (/c/{campaign}/{ambassador}) and ambassador permalinks
 * to the ambassador template.
 */
function tehilim_template_router( $template ) {
	// Referral link: campaign + ambassador query vars present
	if ( get_query_var( 'ambassador' ) && get_query_var( 'name' ) ) {
		$referral = get_template_directory() . '/template-ambassador.php';
		if ( file_exists( $referral ) ) {
			return $referral;
		}
	}

	// Direct ambassador permalink → same personal page (derive vars from the post)
	if ( is_singular( 'ambassador' ) ) {
		$referral = get_template_directory() . '/template-ambassador.php';
		if ( file_exists( $referral ) ) {
			$ambassador  = get_queried_object();
			$campaign_id = intval( get_post_meta( $ambassador->ID, 'campaign_id', true ) );
			if ( $campaign_id ) {
				set_query_var( 'ambassador', $ambassador->post_name );
				set_query_var( 'name', get_post_field( 'post_name', $campaign_id ) );
				return $referral;
			}
		}
	}

	return $template;
}
add_filter( 'template_include', 'tehilim_template_router' );

/**
 * Flush rewrite rules on theme activation (so /c/... works immediately)
 */
function tehilim_flush_rewrites_on_activation() {
	tehilim_register_campaign_cpt();
	tehilim_register_ambassador_cpt();
	tehilim_register_occasion_taxonomy();
	tehilim_add_rewrite_rules();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'tehilim_flush_rewrites_on_activation' );

/**
 * Create recitations table on theme activation
 */
function tehilim_create_recitations_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'tehilim_recitations';

	if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
		$sql = $wpdb->prepare(
			"CREATE TABLE `%i` (
				id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				campaign_id BIGINT UNSIGNED NOT NULL,
				ambassador_id BIGINT UNSIGNED,
				chapter_number INT UNSIGNED NOT NULL,
				reciter_name VARCHAR(255),
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				FOREIGN KEY (campaign_id) REFERENCES `%i`(ID) ON DELETE CASCADE,
				INDEX (campaign_id),
				INDEX (ambassador_id)
			)",
			$table_name,
			$wpdb->posts
		);

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
add_action( 'init', 'tehilim_create_recitations_table' );
