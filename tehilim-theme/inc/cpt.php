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
const TEHILIM_RATE_LIMIT_AMBASSADOR = 5;

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
		'show_in_menu'       => 'tehilim-settings',
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
		'show_in_menu'    => 'tehilim-settings',
		// The referral route (/c/{campaign}/{ambassador}) uses an 'ambassador'
		// query var; the CPT's own query var must not hijack it in WP_Query.
		'query_var'       => false,
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
	$referral = get_template_directory() . '/template-ambassador.php';
	if ( ! file_exists( $referral ) ) {
		return $template;
	}

	// Direct ambassador permalink → personal page (derive vars from the post).
	// Must run BEFORE the referral check: on singular views WP may populate
	// 'name' with the ambassador slug, which is not a campaign slug.
	if ( is_singular( 'ambassador' ) ) {
		$ambassador  = get_queried_object();
		$campaign_id = intval( get_post_meta( $ambassador->ID, 'campaign_id', true ) );
		if ( $campaign_id ) {
			set_query_var( 'ambassador', $ambassador->post_name );
			set_query_var( 'name', get_post_field( 'post_name', $campaign_id ) );
			return $referral;
		}
		return $template;
	}

	// Referral link /c/{campaign}/{ambassador}: both query vars present
	if ( get_query_var( 'ambassador' ) && get_query_var( 'name' ) ) {
		return $referral;
	}

	return $template;
}
add_filter( 'template_include', 'tehilim_template_router' );

/**
 * Live pages must never be page-cached: campaign + ambassador pages show
 * real-time counters, and cached HTML makes them look frozen at old values.
 * Covers LiteSpeed, WP Super Cache, W3TC and standard HTTP caches.
 */
function tehilim_no_cache_live_pages() {
	$is_live = is_singular( 'campaign' ) || is_singular( 'ambassador' ) || get_query_var( 'ambassador' );
	if ( ! $is_live || headers_sent() ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	nocache_headers();

	if ( function_exists( 'do_action' ) ) {
		do_action( 'litespeed_control_set_nocache', 'tehilim live page' );
	}
}
add_action( 'template_redirect', 'tehilim_no_cache_live_pages', 1 );

/**
 * When an admin approves (publishes) a pending campaign, email its creator
 * that the campaign is live. Sent once per campaign.
 */
function tehilim_notify_campaign_approved( $new_status, $old_status, $post ) {
	if ( 'campaign' !== $post->post_type || 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}

	if ( get_post_meta( $post->ID, '_tehilim_approved_mail_sent', true ) ) {
		return;
	}

	// Demo/seeded content never mails anyone
	if ( get_post_meta( $post->ID, '_tehilim_demo', true ) ) {
		return;
	}

	$owner_email = function_exists( 'tehilim_campaign_owner_email' ) ? tehilim_campaign_owner_email( $post->ID ) : '';
	if ( ! $owner_email ) {
		$author      = get_userdata( intval( $post->post_author ) );
		$owner_email = ( $author && is_email( $author->user_email ) ) ? $author->user_email : '';
	}

	if ( $owner_email ) {
		wp_mail(
			$owner_email,
			sprintf( 'הקמפיין "%s" אושר ועלה לאוויר!', $post->post_title ),
			sprintf(
				"בשורה טובה — מנהל האתר אישר את הקמפיין שלכם והוא כבר באוויר!\n\nעמוד הקמפיין (העתיקו ושתפו עם כולם):\n%s\n\nניהול הקמפיין באזור האישי:\n%s\n\nשיהיה בהצלחה — שהתפילות יתקבלו!",
				get_permalink( $post->ID ),
				function_exists( 'tehilim_account_page_url' ) ? tehilim_account_page_url() : home_url( '/' )
			)
		);
	}

	update_post_meta( $post->ID, '_tehilim_approved_mail_sent', 1 );
}
add_action( 'transition_post_status', 'tehilim_notify_campaign_approved', 10, 3 );

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
 * Create (and heal) the recitations table.
 *
 * Deliberately avoids dbDelta + FOREIGN KEY: dbDelta does not support FK
 * clauses and can silently fail to create the table on many hosts. Plain
 * indexes are sufficient here.
 */
function tehilim_create_recitations_table( $force = false ) {
	global $wpdb;

	if ( ! $force && get_transient( 'tehilim_table_ok' ) ) {
		return;
	}

	$table   = $wpdb->prefix . 'tehilim_recitations';
	$charset = $wpdb->get_charset_collate();

	$wpdb->query( "CREATE TABLE IF NOT EXISTS `{$table}` (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		campaign_id BIGINT UNSIGNED NOT NULL,
		ambassador_id BIGINT UNSIGNED NULL,
		chapter_number INT UNSIGNED NOT NULL,
		reciter_name VARCHAR(255) NULL,
		visitor_key VARCHAR(64) NULL,
		created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		KEY campaign_id (campaign_id),
		KEY ambassador_id (ambassador_id)
	) {$charset}" );

	// Heal legacy tables (created by the old plugin) that miss newer columns
	$existing = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );
	if ( $existing ) {
		$needed = array(
			'ambassador_id'  => "ALTER TABLE `{$table}` ADD COLUMN ambassador_id BIGINT UNSIGNED NULL, ADD KEY ambassador_id (ambassador_id)",
			'chapter_number' => "ALTER TABLE `{$table}` ADD COLUMN chapter_number INT UNSIGNED NOT NULL DEFAULT 1",
			'reciter_name'   => "ALTER TABLE `{$table}` ADD COLUMN reciter_name VARCHAR(255) NULL",
			'visitor_key'    => "ALTER TABLE `{$table}` ADD COLUMN visitor_key VARCHAR(64) NULL",
			'created_at'     => "ALTER TABLE `{$table}` ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
		);
		foreach ( $needed as $col => $sql ) {
			if ( ! in_array( $col, $existing, true ) ) {
				$wpdb->query( $sql );
			}
		}
		set_transient( 'tehilim_table_ok', 1, DAY_IN_SECONDS );
	}
}
add_action( 'init', 'tehilim_create_recitations_table' );
