<?php
/**
 * SEO — meta descriptions, Open Graph / Twitter cards, canonical URLs and
 * JSON-LD structured data. Lightweight; complements WordPress core's XML
 * sitemap. If an SEO plugin (Yoast / Rank Math) is active it should take
 * over — we skip our tags then to avoid duplicates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True when a known SEO plugin is handling meta tags.
 */
function tehilim_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || class_exists( 'All_in_One_SEO_Pack' );
}

/**
 * Build a plain-text description for the current view.
 */
function tehilim_seo_description() {
	$desc = '';

	if ( is_singular( 'campaign' ) ) {
		$id  = get_queried_object_id();
		$ded = get_post_meta( $id, 'dedication_text', true );
		$occ = get_the_terms( $id, 'occasion' );
		$occ = ( $occ && ! is_wp_error( $occ ) ) ? $occ[0]->name : '';
		$desc = sprintf( 'קבוצת תהילים למען %s%s. הצטרפו ואמרו פרקי תהילים יחד — כל פרק נזקף לזכות.', get_the_title( $id ), $occ ? ' · ' . $occ : '' );
		if ( $ded ) {
			$desc = $ded . ' — ' . $desc;
		}
	} elseif ( is_singular( 'prayer' ) ) {
		$desc = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 30 );
	} elseif ( is_tax( 'prayer_cat' ) ) {
		$t    = get_queried_object();
		$desc = ( $t && ! is_wp_error( $t ) && $t->description ) ? $t->description : $t->name;
	} elseif ( is_post_type_archive( 'prayer' ) ) {
		$desc = 'אוסף תפילות מכל הלב: תפילות לפרנסה, לרפואה, לזיווג, לשלום בית ולהצלחת הילדים, לצד ברכות וסגולות מרבותינו.';
	} elseif ( is_post_type_archive( 'campaign' ) ) {
		$desc = 'כל קבוצות התהילים הפעילות. הצטרפו לקהילות שאומרות תהילים יחד לרפואה, לישועה, לזיווג, לפרנסה ולעילוי נשמה.';
	} elseif ( is_front_page() || is_home() ) {
		$desc = 'תהילים — פלטפורמה קהילתית לאמירת תהילים משותפת. פתחו קבוצת תהילים, הזמינו שגרירים ואמרו פרקי תהילים יחד לרפואה, לישועה, לזיווג ולפרנסה. חינם, ללא הרשמה.';
	} elseif ( is_page() ) {
		$excerpt = wp_strip_all_tags( get_the_excerpt() );
		$desc    = $excerpt ? $excerpt : get_bloginfo( 'description' );
	} else {
		$desc = get_bloginfo( 'description' );
	}

	$desc = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) );
	if ( mb_strlen( $desc ) > 300 ) {
		$desc = mb_substr( $desc, 0, 297 ) . '…';
	}
	return $desc;
}

/**
 * Canonical URL for the current view.
 */
function tehilim_seo_canonical() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_post_type_archive() ) {
		return get_post_type_archive_link( get_query_var( 'post_type' ) );
	}
	if ( is_tax() || is_category() || is_tag() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? home_url( '/' ) : $link;
	}
	if ( is_front_page() || is_home() ) {
		return home_url( '/' );
	}
	return '';
}

/**
 * Emit meta description, Open Graph, Twitter card and canonical tags.
 */
function tehilim_seo_meta_tags() {
	if ( tehilim_seo_plugin_active() ) {
		return;
	}

	$desc      = tehilim_seo_description();
	$canonical = tehilim_seo_canonical();
	$site      = get_bloginfo( 'name' );
	$title     = wp_get_document_title();
	$image     = '';

	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
	}
	if ( ! $image ) {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$image = wp_get_attachment_image_url( $custom_logo_id, 'large' );
		}
	}

	echo "\n<!-- Tehilim SEO -->\n";
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $canonical ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
	}

	echo '<meta property="og:site_name" content="' . esc_attr( $site ) . '">' . "\n";
	echo '<meta property="og:locale" content="he_IL">' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular() ? 'article' : 'website' ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $canonical ) {
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
	}
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	} else {
		echo '<meta name="twitter:card" content="summary">' . "\n";
	}
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'tehilim_seo_meta_tags', 1 );

/**
 * JSON-LD structured data: Organization + WebSite (with search) on the home
 * page; Article on single prayers/campaigns.
 */
function tehilim_seo_json_ld() {
	if ( tehilim_seo_plugin_active() ) {
		return;
	}

	$nodes = array();
	$home  = home_url( '/' );
	$name  = get_bloginfo( 'name' );

	if ( is_front_page() || is_home() ) {
		$nodes[] = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => $name,
			'url'      => $home,
		);
		$nodes[] = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => $name,
			'url'             => $home,
			'inLanguage'      => 'he',
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => $home . '?s={search_term_string}',
				'query-input' => 'required name=search_term_string',
			),
		);
	} elseif ( is_singular( array( 'prayer', 'campaign' ) ) ) {
		$id      = get_queried_object_id();
		$nodes[] = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'Article',
			'headline'         => get_the_title( $id ),
			'inLanguage'       => 'he',
			'datePublished'    => get_the_date( 'c', $id ),
			'dateModified'     => get_the_modified_date( 'c', $id ),
			'mainEntityOfPage' => get_permalink( $id ),
			'description'      => tehilim_seo_description(),
			'author'          => array( '@type' => 'Organization', 'name' => $name ),
			'publisher'       => array( '@type' => 'Organization', 'name' => $name ),
		);
	}

	if ( ! $nodes ) {
		return;
	}

	foreach ( $nodes as $node ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'tehilim_seo_json_ld', 2 );
