<?php
/**
 * Psalms Unite WordPress theme bootstrap.
 *
 * @package Psalms_Unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PSALMS_UNITE_THEME_VERSION', '1.5.0' );

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$font_preset = get_theme_mod( 'psalms_unite_font_preset', 'heebo' );
		$font_urls   = array(
			'heebo'     => 'https://fonts.googleapis.com/css2?family=Frank+Ruhl+Libre:wght@500;700;900&family=Heebo:wght@400;500;700;800&family=Fraunces:wght@500;700;900&family=Inter:wght@400;500;600;700&display=swap',
			'assistant' => 'https://fonts.googleapis.com/css2?family=Assistant:wght@400;500;700;800&family=Frank+Ruhl+Libre:wght@500;700;900&display=swap',
			'rubik'     => 'https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700;800&family=Frank+Ruhl+Libre:wght@500;700;900&display=swap',
		);
		$fonts_url   = $font_urls[ $font_preset ] ?? $font_urls['heebo'];

		if ( 'system' !== $font_preset && 'custom' !== $font_preset ) {
			wp_enqueue_style( 'psalms-unite-fonts', $fonts_url, array(), null );
		}

		$manifest_path = get_template_directory() . '/assets/app/manifest.json';
		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
			if ( is_array( $manifest ) && ! empty( $manifest['css'] ) && is_array( $manifest['css'] ) ) {
				foreach ( $manifest['css'] as $css_file ) {
					wp_enqueue_style(
						'psalms-unite-app-' . md5( $css_file ),
						get_template_directory_uri() . '/assets/app/' . $css_file,
						array(),
						PSALMS_UNITE_THEME_VERSION
					);
				}
			}
		}

		// The TCM shortcodes render in the body, so their assets must be queued before wp_head prints.
		if ( class_exists( '\TCM\Frontend\Assets' ) ) {
			\TCM\Frontend\Assets::ensure();
		}

		wp_enqueue_style( 'psalms-unite-theme', get_stylesheet_uri(), array(), PSALMS_UNITE_THEME_VERSION );
		wp_add_inline_style( 'psalms-unite-theme', psalms_unite_font_css() );
	}
);

add_action(
	'customize_register',
	static function ( $wp_customize ) {
		$wp_customize->add_section(
			'psalms_unite_typography',
			array(
				'title'    => __( 'Psalms Unite Typography', 'psalms-unite' ),
				'priority' => 35,
			)
		);

		$wp_customize->add_setting(
			'psalms_unite_font_preset',
			array(
				'default'           => 'heebo',
				'sanitize_callback' => 'sanitize_key',
			)
		);

		$wp_customize->add_control(
			'psalms_unite_font_preset',
			array(
				'type'    => 'select',
				'section' => 'psalms_unite_typography',
				'label'   => __( 'Site font', 'psalms-unite' ),
				'choices' => array(
					'heebo'     => 'Heebo + Frank Ruhl Libre',
					'assistant' => 'Assistant + Frank Ruhl Libre',
					'rubik'     => 'Rubik + Frank Ruhl Libre',
					'system'    => 'System font',
					'custom'    => 'Custom font URL',
				),
			)
		);

		$wp_customize->add_setting(
			'psalms_unite_custom_font_name',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			'psalms_unite_custom_font_name',
			array(
				'type'        => 'text',
				'section'     => 'psalms_unite_typography',
				'label'       => __( 'Custom font name', 'psalms-unite' ),
				'description' => __( 'Example: My Hebrew Font', 'psalms-unite' ),
			)
		);

		$wp_customize->add_setting(
			'psalms_unite_custom_font_url',
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);

		$wp_customize->add_control(
			'psalms_unite_custom_font_url',
			array(
				'type'        => 'url',
				'section'     => 'psalms_unite_typography',
				'label'       => __( 'Custom font file URL', 'psalms-unite' ),
				'description' => __( 'Use a licensed woff2/woff/ttf/otf file from Media Library.', 'psalms-unite' ),
			)
		);
	}
);

add_action(
	'after_switch_theme',
	static function () {
		$pages = array(
			'home'      => array( 'title' => 'קמפייני תהילים', 'front' => true ),
			'about'     => array( 'title' => 'אודות' ),
			'campaigns' => array( 'title' => 'קמפיינים' ),
			'create'    => array( 'title' => 'יצירת קמפיין' ),
			'dashboard' => array( 'title' => 'אזור אישי' ),
			'auth'      => array( 'title' => 'התחברות' ),
		);

		foreach ( $pages as $slug => $page ) {
			$existing = get_page_by_path( $slug );
			if ( $existing instanceof WP_Post ) {
				$page_id = (int) $existing->ID;
			} else {
				$page_id = wp_insert_post(
					array(
						'post_title'   => $page['title'],
						'post_name'    => $slug,
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'post_content' => '',
					)
				);
			}

			if ( ! empty( $page['front'] ) && $page_id && ! is_wp_error( $page_id ) ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', (int) $page_id );
			}
		}
	}
);

function psalms_unite_shortcode_slot( string $slot, string $fallback = '' ): string {
	$shortcodes = array(
		'campaigns'       => array( 'tehillim_campaigns' ),
		'campaign_detail' => array( 'tehillim_campaign' ),
		'create'          => array( 'tehillim_create_campaign_form' ),
		'my_campaigns'    => array( 'tehillim_my_campaigns' ),
		'my_activity'     => array( 'tehillim_my_activity' ),
		'dashboard'       => array( 'tehillim_my_campaigns', 'tehillim_my_activity', 'tehillim_ambassador_dashboard' ),
		'ambassadors'     => array( 'tehillim_ambassador_dashboard' ),
		'subscribe'       => array( 'tehillim_subscribe' ),
		'stats'           => array( 'tehillim_global_stats', 'tehillim_stats' ),
	);

	$shortcodes = apply_filters( 'psalms_unite_shortcode_map', $shortcodes );
	$candidates = $shortcodes[ $slot ] ?? array();

	foreach ( $candidates as $shortcode ) {
		if ( shortcode_exists( $shortcode ) ) {
			return do_shortcode( '[' . $shortcode . ']' );
		}
	}

	return $fallback;
}

function psalms_unite_shortcode( string $shortcode, array $atts = array(), string $fallback = '' ): string {
	if ( ! shortcode_exists( $shortcode ) ) {
		return $fallback;
	}

	$parts = array();
	foreach ( $atts as $key => $value ) {
		$parts[] = sanitize_key( (string) $key ) . '="' . esc_attr( (string) $value ) . '"';
	}

	return do_shortcode( '[' . $shortcode . ( $parts ? ' ' . implode( ' ', $parts ) : '' ) . ']' );
}

function psalms_unite_font_css(): string {
	$preset = get_theme_mod( 'psalms_unite_font_preset', 'heebo' );
	$body   = '"Heebo", "Inter", ui-sans-serif, system-ui, sans-serif';
	$head   = '"Frank Ruhl Libre", "Fraunces", ui-serif, Georgia, serif';
	$face   = '';

	if ( 'assistant' === $preset ) {
		$body = '"Assistant", "Heebo", ui-sans-serif, system-ui, sans-serif';
	} elseif ( 'rubik' === $preset ) {
		$body = '"Rubik", "Heebo", ui-sans-serif, system-ui, sans-serif';
	} elseif ( 'system' === $preset ) {
		$body = 'Arial, ui-sans-serif, system-ui, sans-serif';
		$head = $body;
	} elseif ( 'custom' === $preset ) {
		$name = trim( (string) get_theme_mod( 'psalms_unite_custom_font_name', '' ) );
		$url  = trim( (string) get_theme_mod( 'psalms_unite_custom_font_url', '' ) );
		$name = (string) preg_replace( '/[;{}<>"]/', '', $name );
		if ( '' !== $name && preg_match( '#^https?://#i', $url ) ) {
			$ext    = strtolower( (string) pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
			$format = array(
				'woff2' => 'woff2',
				'woff'  => 'woff',
				'ttf'   => 'truetype',
				'otf'   => 'opentype',
			)[ $ext ] ?? 'woff2';
			$face   = '@font-face{font-family:"' . esc_html( $name ) . '";src:url("' . esc_url( $url ) . '") format("' . esc_attr( $format ) . '");font-weight:400 900;font-display:swap;}';
			$body   = '"' . $name . '", "Heebo", ui-sans-serif, system-ui, sans-serif';
			$head   = $body;
		}
	}

	$vars  = ':root{--font-sans:' . $body . ';--font-display:' . $head . ';--tcm-body-font:' . $body . ';--tcm-display-font:' . $head . ';}';
	$rules = 'body,.font-sans,.tcm-wrap{font-family:var(--font-sans)!important;}h1,h2,h3,h4,.font-display,.tcm-title,.tcm-card h2,.tcm-card h3{font-family:var(--font-display)!important;}';

	return $face . $vars . $rules;
}
