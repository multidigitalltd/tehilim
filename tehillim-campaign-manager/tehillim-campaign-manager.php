<?php
/**
 * Plugin Name: Tehillim Campaign Manager
 * Description: מערכת קמפיינים לחלוקת ספרי תהילים: ארכיון, עמודי קמפיין דינמיים, יעדים, בונוסים, שגרירים, וובהוקים, רשימות תפוצה, פרסום והודעות מותאמות.
 * Version: 3.14.2
 * Author: Multi Digital
 * Text Domain: tehillim-campaign-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 *
 * @package Tehillim_Campaign_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TCM_VERSION', '3.14.2' );
define( 'TCM_PLUGIN_FILE', __FILE__ );
define( 'TCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/*
 * Autoloading.
 * Prefer the Composer autoloader when present; otherwise register a lightweight
 * PSR-4 fallback for the TCM\ namespace so the plugin runs out-of-the-box even
 * without `composer install` (e.g. a plain ZIP install).
 */
if ( file_exists( TCM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once TCM_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( $class ) {
			$prefix = 'TCM\\';
			$len    = strlen( $prefix );
			if ( strncmp( $class, $prefix, $len ) !== 0 ) {
				return;
			}
			$relative = substr( $class, $len );
			$file     = TCM_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

register_activation_hook( __FILE__, array( 'TCM\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TCM\\Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		TCM\Plugin::instance()->boot();
	}
);
