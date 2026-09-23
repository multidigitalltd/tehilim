<?php
/**
 * Theme bootstrap.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme version. Also part of the asset cache key.
 */
const SAVTA_VERSION = '1.0.1';

require_once get_theme_file_path( 'inc/fields.php' );
require_once get_theme_file_path( 'inc/helpers.php' );
require_once get_theme_file_path( 'inc/setup.php' );
require_once get_theme_file_path( 'inc/class-savta-nav-walker.php' );
require_once get_theme_file_path( 'inc/customizer.php' );
require_once get_theme_file_path( 'inc/assets.php' );
require_once get_theme_file_path( 'inc/accessibility.php' );
require_once get_theme_file_path( 'inc/form.php' );
require_once get_theme_file_path( 'inc/seo.php' );
require_once get_theme_file_path( 'inc/seed.php' );

if ( is_admin() ) {
	require_once get_theme_file_path( 'inc/meta-boxes.php' );
	require_once get_theme_file_path( 'inc/dashboard.php' );
}
