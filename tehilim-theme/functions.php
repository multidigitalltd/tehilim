<?php
/**
 * Tehilim Theme — Functions & Setup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TEHILIM_VERSION = '2.0.0';

/**
 * Theme Setup
 */
function tehilim_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form' ) );
	register_nav_menu( 'primary', 'Primary Navigation' );
}
add_action( 'after_setup_theme', 'tehilim_setup' );

/**
 * Enqueue Styles & Scripts
 */
function tehilim_enqueue_assets() {
	$theme_uri = get_template_directory_uri();
	$version   = TEHILIM_VERSION;

	wp_enqueue_style( 'tehilim-style', $theme_uri . '/style.css', array(), $version );

	wp_enqueue_script( 'tehilim-app', $theme_uri . '/assets/js/app.js', array(), $version, true );
	wp_enqueue_script( 'tehilim-form', $theme_uri . '/assets/js/form.js', array(), $version, true );

	wp_localize_script( 'tehilim-app', 'tehilim', array(
		'api_url'           => rest_url( 'tehilim/v1/' ),
		'nonce'             => wp_create_nonce( 'tehilim_nonce' ),
		'turnstile_site_key' => defined( 'TURNSTILE_SITE_KEY' ) ? TURNSTILE_SITE_KEY : '',
	) );
}
add_action( 'wp_enqueue_scripts', 'tehilim_enqueue_assets' );

/**
 * Includes
 */
require_once get_template_directory() . '/inc/cpt.php';
require_once get_template_directory() . '/inc/meta.php';
require_once get_template_directory() . '/inc/rest.php';

if ( is_admin() ) {
	require_once get_template_directory() . '/inc/admin.php';
}
