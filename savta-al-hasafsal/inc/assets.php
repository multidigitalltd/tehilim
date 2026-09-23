<?php
/**
 * Front-end asset loading.
 *
 * Only what a page needs ships with it: the homepage stylesheet and behaviours
 * on the homepage layout, the inner-page stylesheet elsewhere, the
 * accessibility toolbar only when it is switched on.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

/**
 * The asset actually served for a source path: the minified build, or the
 * readable source when SCRIPT_DEBUG is on.
 *
 * @param string $path Path relative to the theme root.
 * @return string
 */
function savta_asset_path( string $path ): string {
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		return $path;
	}

	$extension = '.' . pathinfo( $path, PATHINFO_EXTENSION );
	$minified  = substr( $path, 0, -strlen( $extension ) ) . '.min' . $extension;

	return is_readable( get_theme_file_path( $minified ) ) ? $minified : $path;
}

/**
 * URL of a theme asset, preferring the minified build.
 *
 * @param string $path Path relative to the theme root.
 * @return string
 */
function savta_asset_uri( string $path ): string {
	return get_theme_file_uri( savta_asset_path( $path ) );
}

/**
 * Cache-busting version: theme version plus the file's own modification time,
 * so an edited asset is refetched and an untouched one stays cached.
 *
 * @param string $path Path relative to the theme root.
 * @return string
 */
function savta_asset_version( string $path ): string {
	$file     = get_theme_file_path( savta_asset_path( $path ) );
	$modified = is_readable( $file ) ? (int) filemtime( $file ) : 0;

	return $modified > 0 ? SAVTA_VERSION . '.' . $modified : SAVTA_VERSION;
}

/**
 * Registers and enqueues the assets for the current request.
 *
 * @return void
 */
function savta_enqueue_assets(): void {
	wp_enqueue_style( 'sv-base', savta_asset_uri( 'assets/css/base.css' ), array(), savta_asset_version( 'assets/css/base.css' ) );

	if ( savta_is_home_layout() ) {
		wp_enqueue_style( 'sv-home', savta_asset_uri( 'assets/css/home.css' ), array( 'sv-base' ), savta_asset_version( 'assets/css/home.css' ) );
	} else {
		wp_enqueue_style( 'sv-page', savta_asset_uri( 'assets/css/page.css' ), array( 'sv-base' ), savta_asset_version( 'assets/css/page.css' ) );
	}

	wp_enqueue_script( 'sv-main', savta_asset_uri( 'assets/js/main.js' ), array(), savta_asset_version( 'assets/js/main.js' ), true );
	wp_script_add_data( 'sv-main', 'strategy', 'defer' );

	if ( savta_is_home_layout() ) {
		wp_add_inline_script( 'sv-main', 'window.svConfig=' . wp_json_encode( savta_form_config() ) . ';', 'before' );
	}

	if ( savta_option( 'savta_a11y_enable' ) ) {
		wp_enqueue_style( 'sv-a11y', savta_asset_uri( 'assets/css/a11y.css' ), array( 'sv-base' ), savta_asset_version( 'assets/css/a11y.css' ) );
		wp_enqueue_script( 'sv-a11y', savta_asset_uri( 'assets/js/a11y.js' ), array(), savta_asset_version( 'assets/js/a11y.js' ), true );
		wp_script_add_data( 'sv-a11y', 'strategy', 'defer' );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'savta_enqueue_assets' );

/**
 * Preloads the two weights that render above the fold (700 nav/CTA, 900 H1).
 *
 * @return void
 */
function savta_preload_fonts(): void {
	foreach ( array( 'asimon-bold.woff2', 'asimon-black.woff2' ) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/' . $font ) )
		);
	}
}
add_action( 'wp_head', 'savta_preload_fonts', 2 );

/**
 * Drops the block stylesheets on pages whose content has no blocks.
 *
 * @return void
 */
function savta_dequeue_block_styles(): void {
	if ( is_singular() && has_blocks( get_queried_object_id() ) && ! savta_is_home_layout() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'savta_dequeue_block_styles', 100 );
