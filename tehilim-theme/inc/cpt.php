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
		'label'              => 'Campaigns',
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
		'label'           => 'Ambassadors',
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
		'label'         => 'Occasions',
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
		'refua'   => 'Healing (Refua)',
		'iluy'    => 'Soul Elevation (Iluy)',
		'zivug'   => 'Finding a Partner (Zivug)',
		'parnasa' => 'Livelihood (Parnasa)',
		'zchut'   => 'Merit (Zchut)',
		'event'   => 'Event',
	);

	foreach ( $occasions as $slug => $name ) {
		if ( ! term_exists( $slug, 'occasion' ) ) {
			wp_insert_term( $name, 'occasion', array( 'slug' => $slug ) );
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
