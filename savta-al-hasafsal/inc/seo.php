<?php
/**
 * Social meta tags and structured data. Stands down when an SEO plugin is active.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a dedicated SEO plugin is handling meta output.
 *
 * @return bool
 */
function savta_seo_plugin_active(): bool {
	foreach ( array( 'WPSEO_VERSION', 'RANK_MATH_VERSION', 'AIOSEO_VERSION', 'SEOPRESS_VERSION', 'THE_SEO_FRAMEWORK_VERSION' ) as $marker ) {
		if ( defined( $marker ) ) {
			return true;
		}
	}

	/**
	 * Filters whether the theme should skip its own SEO output.
	 *
	 * @param bool $skip Whether to skip.
	 */
	return (bool) apply_filters( 'savta_skip_seo_output', false );
}

/**
 * A plain-text description for the current view.
 *
 * @return string
 */
function savta_meta_description(): string {
	if ( savta_is_home_layout() ) {
		$description = (string) savta_get( 'hero_info' );
	} elseif ( is_singular() ) {
		$post        = get_queried_object();
		$description = $post instanceof WP_Post ? get_the_excerpt( $post ) : '';
	} else {
		$description = (string) get_bloginfo( 'description', 'display' );
	}

	return mb_substr( wp_strip_all_tags( $description, true ), 0, 200 );
}

/**
 * Prints the description, Open Graph and Twitter tags.
 *
 * @return void
 */
function savta_social_meta(): void {
	if ( savta_seo_plugin_active() ) {
		return;
	}

	$description = savta_meta_description();
	$title       = is_front_page() ? get_bloginfo( 'name', 'display' ) : wp_get_document_title();
	$url         = is_singular() ? (string) get_permalink() : home_url( '/' );
	$image       = '';

	if ( is_singular() && has_post_thumbnail() ) {
		$image = (string) get_the_post_thumbnail_url( null, 'large' );
	} elseif ( savta_is_home_layout() ) {
		$image = savta_image_url( savta_get( 'hero_logo' ), 'large' );
	}

	printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );

	$tags = array(
		'og:site_name'   => get_bloginfo( 'name', 'display' ),
		'og:title'       => $title,
		'og:description' => $description,
		'og:type'        => is_singular() && ! is_front_page() ? 'article' : 'website',
		'og:url'         => $url,
		'og:locale'      => get_locale(),
		'og:image'       => $image,
	);

	foreach ( $tags as $property => $content ) {
		if ( '' === $content ) {
			continue;
		}
		printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $property ), esc_attr( $content ) );
	}

	printf( '<meta name="twitter:card" content="%s" />' . "\n", esc_attr( '' !== $image ? 'summary_large_image' : 'summary' ) );
}
add_action( 'wp_head', 'savta_social_meta', 5 );

/**
 * Prints the JSON-LD graph: the organisation, the site and the FAQ.
 *
 * @return void
 */
function savta_structured_data(): void {
	if ( savta_seo_plugin_active() ) {
		return;
	}

	$home    = home_url( '/' );
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id > 0 ? (string) wp_get_attachment_image_url( $logo_id, 'full' ) : get_theme_file_uri( 'assets/img/logo.png' );

	$graph = array(
		array(
			'@type'       => 'NGO',
			'@id'         => $home . '#organization',
			'name'        => get_bloginfo( 'name', 'display' ),
			'url'         => $home,
			'description' => savta_meta_description(),
			'areaServed'  => 'בני ברק',
			'logo'        => array(
				'@type' => 'ImageObject',
				'url'   => $logo,
			),
		),
		array(
			'@type'      => 'WebSite',
			'@id'        => $home . '#website',
			'url'        => $home,
			'name'       => get_bloginfo( 'name', 'display' ),
			'publisher'  => array( '@id' => $home . '#organization' ),
			'inLanguage' => get_bloginfo( 'language' ),
		),
	);

	if ( savta_is_home_layout() && savta_section_enabled( 'faq_enable' ) ) {
		$questions = array();

		foreach ( savta_rows( 'faq_items' ) as $item ) {
			if ( '' === trim( $item['question'] ) ) {
				continue;
			}

			$questions[] = array(
				'@type'          => 'Question',
				'name'           => $item['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $item['answer'],
				),
			);
		}

		if ( array() !== $questions ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => $home . '#faq',
				'mainEntity' => $questions,
			);
		}
	}

	$payload = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);

	if ( false === $payload ) {
		return;
	}

	printf( '<script type="application/ld+json">%s</script>' . "\n", $payload ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output inside a JSON-LD block.
}
add_action( 'wp_head', 'savta_structured_data', 6 );
