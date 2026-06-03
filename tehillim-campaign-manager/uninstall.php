<?php
/**
 * Uninstall routine.
 *
 * Runs only when the user deletes the plugin from wp-admin. Removes the
 * plugin's options, scheduled events and custom tables. Campaign posts
 * (the `tcm_campaign` CPT) are intentionally LEFT in place, since they are
 * user content and may be wanted even after uninstalling.
 *
 * @package Tehillim_Campaign_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$tcm_tables = array(
	$wpdb->prefix . 'tcm_assignments',
	$wpdb->prefix . 'tcm_ambassadors',
	$wpdb->prefix . 'tcm_referrals',
	$wpdb->prefix . 'tcm_logs',
);
foreach ( $tcm_tables as $tcm_table ) {
	// Table name is built from $wpdb->prefix and a constant — safe to interpolate.
	$wpdb->query( "DROP TABLE IF EXISTS `{$tcm_table}`" ); // phpcs:ignore WordPress.DB
}

// Delete options.
delete_option( 'tcm_options' );
delete_option( 'tcm_db_version' );
delete_option( 'tcm_chapters' );
delete_option( 'tcm_pending_messages' );

// Remove per-(campaign|round|token) "full book completed" flags.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'tcm_full_book_done_%'" ); // phpcs:ignore WordPress.DB

// Clear scheduled cron.
wp_clear_scheduled_hook( 'tcm_cron_tasks' );
