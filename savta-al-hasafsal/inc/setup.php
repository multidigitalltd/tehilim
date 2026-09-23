<?php
/**
 * Theme setup: supports, menus, and the core tweaks the site relies on.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declares theme features and the menu location.
 *
 * @return void
 */
function savta_setup(): void {
	load_theme_textdomain( 'savta-al-hasafsal', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 800,
			'width'       => 800,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'תפריט ראשי', 'savta-al-hasafsal' ),
		)
	);
}
add_action( 'after_setup_theme', 'savta_setup' );

/**
 * Content width for embeds inside page content.
 *
 * @return void
 */
function savta_content_width(): void {
	$GLOBALS['content_width'] = 860;
}
add_action( 'after_setup_theme', 'savta_content_width', 0 );

/**
 * Drops core assets the site never uses (emoji polyfill, generator, RSD).
 *
 * @return void
 */
function savta_trim_core_assets(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );

	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'savta_trim_core_assets' );

/**
 * Adds a stable body class for the theme and the homepage layout.
 *
 * @param array $classes Body classes.
 * @return array
 */
function savta_body_class( array $classes ): array {
	$classes[] = 'sv';

	if ( savta_is_home_layout() ) {
		$classes[] = 'sv-home';
	}

	return $classes;
}
add_filter( 'body_class', 'savta_body_class' );

/**
 * Gives the primary menu links the theme's own class.
 *
 * @param array    $atts Link attributes.
 * @param WP_Post  $item Menu item.
 * @param stdClass $args Menu arguments.
 * @return array
 */
function savta_nav_link_atts( $atts, $item, $args ): array {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$atts['class'] = 'sv-nav__link';
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'savta_nav_link_atts', 10, 3 );

/**
 * Renders the small logo in the sticky nav.
 *
 * A logo picked in the Customizer wins; otherwise the bundled mark.
 *
 * @return void
 */
function savta_the_nav_logo(): void {
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$name    = get_bloginfo( 'name', 'display' );

	printf( '<a class="sv-nav__logo" href="%s" rel="home">', esc_url( home_url( '/' ) ) );

	if ( $logo_id > 0 ) {
		echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes attributes.
			$logo_id,
			'medium',
			false,
			array(
				'class'    => 'sv-nav__logo-img',
				'alt'      => $name,
				'loading'  => 'eager',
				'decoding' => 'async',
			)
		);
	} else {
		printf(
			'<img class="sv-nav__logo-img" src="%s" width="84" height="84" alt="%s" loading="eager" decoding="async" />',
			esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ),
			esc_attr( $name )
		);
	}

	echo '</a>';
}
