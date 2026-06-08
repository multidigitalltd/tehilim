<?php
/**
 * Uninstall routine.
 *
 * Runs only when the user deletes the plugin from wp-admin. Removes ALL of the
 * plugin's custom tables, options, post meta and transients, and clears the
 * scheduled cron. Campaign/prayer/ad posts (the CPTs) are intentionally LEFT in
 * place, since they are user content that may be wanted even after uninstalling.
 *
 * @package Tehillim_Campaign_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop every custom table created by Schema::install().
$tcm_tables = array(
	$wpdb->prefix . 'tcm_assignments',
	$wpdb->prefix . 'tcm_ambassadors',
	$wpdb->prefix . 'tcm_referrals',
	$wpdb->prefix . 'tcm_logs',
	$wpdb->prefix . 'tcm_subscribers',
	$wpdb->prefix . 'tcm_ad_stats',
);
foreach ( $tcm_tables as $tcm_table ) {
	// Table name is built from $wpdb->prefix and a constant - safe to interpolate.
	$wpdb->query( "DROP TABLE IF EXISTS `{$tcm_table}`" ); // phpcs:ignore WordPress.DB
}

// Delete options.
delete_option( 'tcm_options' );
delete_option( 'tcm_db_version' );
delete_option( 'tcm_chapters' );
delete_option( 'tcm_pending_messages' );

// Remove legacy per-(campaign|round|token) "full book completed" flags.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'tcm_full_book_done_%'" ); // phpcs:ignore WordPress.DB

// Remove the plugin's transients (stats cache + rate-limit counters).
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_tcm\_%' OR option_name LIKE '\_transient\_timeout\_tcm\_%'" ); // phpcs:ignore WordPress.DB

// Remove the plugin's post meta from any retained posts.
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\_tcm\_%'" ); // phpcs:ignore WordPress.DB

// Clear scheduled cron.
wp_clear_scheduled_hook( 'tcm_cron_tasks' );
