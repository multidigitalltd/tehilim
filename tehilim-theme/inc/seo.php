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

	// Virtual Tehilim reader pages
	if ( function_exists( 'tehilim_reader_view' ) ) {
		$rv = tehilim_reader_view();
		if ( 'index' === $rv ) {
			return 'ספר תהילים המלא והמנוקד — כל 150 הפרקים לקריאה באינטרנט, פרק אחר פרק, לרפואה, לפרנסה, לזיווג ולזכות הרבים.';
		}
		if ( 'chapter' === $rv ) {
			$n   = intval( get_query_var( 'tehilim_chapter' ) );
			$gem = function_exists( 'tehilim_hebrew_numeral' ) ? tehilim_hebrew_numeral( $n ) : $n;
			return sprintf( 'תהילים פרק %s — הנוסח המלא והמנוקד של המזמור לקריאה ולאמירה. פרק %s מתוך ספר תהילים.', $gem, $gem );
		}
		if ( 'yomi' === $rv ) {
			return 'תהילים יומי — חלוקת ספר תהילים לימי החודש. הפרקים לאמירה בכל יום, כדי להשלים את כל ספר תהילים מדי חודש.';
		}
		if ( 'name' === $rv ) {
			return 'תהילים לפי שם — הזינו שם וקבלו את פסוקי פרק קי״ט לפי אותיות השם, עם אותיות "קרע שטן". מנהג לאמירת תהילים לרפואה ולישועה.';
		}
	}

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
		$nodes[] = tehilim_seo_faq_node();
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
	} elseif ( function_exists( 'tehilim_reader_view' ) ) {
		$rv = tehilim_reader_view();
		if ( 'chapter' === $rv ) {
			$n     = intval( get_query_var( 'tehilim_chapter' ) );
			$gem   = function_exists( 'tehilim_hebrew_numeral' ) ? tehilim_hebrew_numeral( $n ) : $n;
			$nodes[] = tehilim_seo_breadcrumb_node( array(
				array( 'ספר תהילים', $home . 'tehilim/' ),
				array( 'פרק ' . $gem, $home . 'tehilim/' . $n . '/' ),
			) );
		} elseif ( 'index' === $rv ) {
			$nodes[] = tehilim_seo_breadcrumb_node( array(
				array( 'ספר תהילים', $home . 'tehilim/' ),
			) );
		} elseif ( 'yomi' === $rv ) {
			$nodes[] = tehilim_seo_breadcrumb_node( array(
				array( 'ספר תהילים', $home . 'tehilim/' ),
				array( 'תהילים יומי', $home . 'tehilim-yomi/' ),
			) );
		} elseif ( 'name' === $rv ) {
			$nodes[] = tehilim_seo_breadcrumb_node( array(
				array( 'ספר תהילים', $home . 'tehilim/' ),
				array( 'תהילים לפי שם', $home . 'tehilim-lefi-shem/' ),
			) );
		}
	}

	if ( ! $nodes ) {
		return;
	}

	foreach ( $nodes as $node ) {
		if ( ! $node ) {
			continue;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'tehilim_seo_json_ld', 2 );

/**
 * Build a BreadcrumbList JSON-LD node from a list of [ name, url ] pairs.
 * The site home is prepended automatically as the first crumb.
 */
function tehilim_seo_breadcrumb_node( $crumbs ) {
	$items = array();
	$pos   = 1;
	$items[] = array(
		'@type'    => 'ListItem',
		'position' => $pos++,
		'name'     => 'דף הבית',
		'item'     => home_url( '/' ),
	);
	foreach ( $crumbs as $crumb ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => $crumb[0],
			'item'     => $crumb[1],
		);
	}
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/**
 * FAQPage JSON-LD for the home page — high-intent questions people search on
 * Google around saying Tehilim. Answers are original, factual and concise.
 */
function tehilim_seo_faq_node() {
	$faqs = array(
		array(
			'q' => 'כמה פרקים יש בספר תהילים?',
			'a' => 'בספר תהילים 150 פרקים (מזמורים). נהוג לחלק את כולם לימי החודש או לימות השבוע, כדי לסיים את כל הספר באופן קבוע.',
		),
		array(
			'q' => 'מהו תהילים יומי?',
			'a' => 'תהילים יומי הוא חלוקת ספר תהילים לימי החודש, כך שאמירת הפרקים של כל יום משלימה את כל הספר מדי חודש. ניתן לראות את חלוקת הפרקים לכל יום בעמוד "תהילים יומי".',
		),
		array(
			'q' => 'מהו תהילים לפי שם ומהן אותיות "קרע שטן"?',
			'a' => 'מנהג עתיק לאמירת פסוקי פרק קי״ט בתהילים לפי אותיות שמו של אדם, ובסופם אותיות "קרע שטן". נהוג לאמרו לרפואה, לישועה ולזכות אדם מסוים.',
		),
		array(
			'q' => 'אילו פרקי תהילים אומרים לרפואה?',
			'a' => 'נהוג לומר לרפואה בין השאר את הפרקים ו׳, כ׳, כ״ג, ל׳, מ״א וק״ג, וכן פרק קי״ט לפי אותיות שם החולה. אפשר גם להצטרף לקבוצת תהילים ולומר פרקים יחד לזכות החולה.',
		),
		array(
			'q' => 'איך פותחים קבוצת תהילים משותפת?',
			'a' => 'באתר ניתן לפתוח קבוצת תהילים בחינם וללא הרשמה: בוחרים את המטרה (רפואה, ישועה, זיווג, פרנסה או לעילוי נשמה), מזמינים משתתפים ושגרירים, וכל פרק שנאמר נזקף לזכות הקבוצה.',
		),
	);

	$entities = array();
	foreach ( $faqs as $f ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $f['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $f['a'],
			),
		);
	}

	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
}
