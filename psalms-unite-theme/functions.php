<?php
/**
 * Psalms Unite WordPress theme bootstrap.
 *
 * @package Psalms_Unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PSALMS_UNITE_THEME_VERSION', '1.11.0' );

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
			'home'      => array(
				'title' => 'קמפייני תהילים',
				'front' => true,
			),
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

/**
 * Editable site texts (Customizer). Every default mirrors the original copy;
 * users override any string live via Appearance -> Customize -> Psalms Unite.
 */
function psalms_unite_text_config() {
	return array(
		'psalms_hero'         => array(
			'title'  => 'תכנים - באנר ראשי (דף הבית)',
			'fields' => array(
				'hero_title'         => array(
					'default' => 'מאחדים אנשים סביב יעד תהילים משותף',
					'type'    => 'textarea',
				),
				'hero_sub'           => array(
					'default' => 'צרו קמפיין, הזמינו משתתפים, חלקו פרקים ועקבו אחרי ההתקדמות בזמן אמת.',
					'type'    => 'textarea',
				),
				'hero_cta_primary'   => array(
					'default' => 'יצירת קמפיין',
					'type'    => 'text',
				),
				'hero_cta_secondary' => array(
					'default' => 'צפייה בקמפיינים',
					'type'    => 'text',
				),
			),
		),
		'psalms_how'          => array(
			'title'  => 'תכנים - איך זה עובד',
			'fields' => array(
				'how_title'    => array(
					'default' => 'איך זה עובד',
					'type'    => 'text',
				),
				'how_sub'      => array(
					'default' => 'שלושה צעדים פשוטים לקריאה משותפת.',
					'type'    => 'text',
				),
				'how_s1_title' => array(
					'default' => 'פותחים קמפיין',
					'type'    => 'text',
				),
				'how_s1_text'  => array(
					'default' => 'מגדירים מטרה, הקדשה ותיאור קצר.',
					'type'    => 'textarea',
				),
				'how_s2_title' => array(
					'default' => 'משתפים קישור',
					'type'    => 'text',
				),
				'how_s2_text'  => array(
					'default' => 'מזמינים משפחה, חברים ושגרירים.',
					'type'    => 'textarea',
				),
				'how_s3_title' => array(
					'default' => 'עוקבים יחד',
					'type'    => 'text',
				),
				'how_s3_text'  => array(
					'default' => 'רואים התקדמות והשלמות בצורה ברורה.',
					'type'    => 'textarea',
				),
			),
		),
		'psalms_recent'       => array(
			'title'  => 'תכנים - קמפיינים אחרונים',
			'fields' => array(
				'recent_title'   => array(
					'default' => 'קמפיינים אחרונים',
					'type'    => 'text',
				),
				'recent_sub'     => array(
					'default' => 'נמשך מתוך התוסף כאשר הוא מחובר.',
					'type'    => 'text',
				),
				'recent_viewall' => array(
					'default' => 'לכל הקמפיינים',
					'type'    => 'text',
				),
			),
		),
		'psalms_features'     => array(
			'title'  => 'תכנים - יתרונות',
			'fields' => array(
				'features_title' => array(
					'default' => 'כל מה שצריך לקמפיין מנצח',
					'type'    => 'text',
				),
				'feat_1_title'   => array(
					'default' => 'יצירת קמפיין במגוון מטרות',
					'type'    => 'text',
				),
				'feat_1_text'    => array(
					'default' => 'רפואה, ישועה, זיווג, הצלחה, לעילוי נשמה ועוד.',
					'type'    => 'textarea',
				),
				'feat_2_title'   => array(
					'default' => 'מערכת שגרירים',
					'type'    => 'text',
				),
				'feat_2_text'    => array(
					'default' => 'כל שגריר עם לינק ייעודי ולוח התקדמות אישי.',
					'type'    => 'textarea',
				),
				'feat_3_title'   => array(
					'default' => 'מעקב אישי',
					'type'    => 'text',
				),
				'feat_3_text'    => array(
					'default' => 'כל משתתף רואה את התרומה שלו ליעד.',
					'type'    => 'textarea',
				),
				'feat_4_title'   => array(
					'default' => 'התקדמות בזמן אמת',
					'type'    => 'text',
				),
				'feat_4_text'    => array(
					'default' => 'מד גדול, ספירת פרקים וספרים מתעדכנת מיד.',
					'type'    => 'textarea',
				),
				'feat_5_title'   => array(
					'default' => 'שיתוף בוואטסאפ',
					'type'    => 'text',
				),
				'feat_5_text'    => array(
					'default' => 'כפתור אחד והקמפיין מגיע לכל קבוצה.',
					'type'    => 'textarea',
				),
				'feat_6_title'   => array(
					'default' => 'השתתפות קבוצתית',
					'type'    => 'text',
				),
				'feat_6_text'    => array(
					'default' => 'משפחות, כיתות, קהילות - כולם תורמים יחד.',
					'type'    => 'textarea',
				),
				'feat_7_title'   => array(
					'default' => 'תגי הישג',
					'type'    => 'text',
				),
				'feat_7_text'    => array(
					'default' => 'ספר ראשון, 10 ספרים, שגריר מצטיין ועוד.',
					'type'    => 'textarea',
				),
				'feat_8_title'   => array(
					'default' => 'סטטיסטיקות חיות',
					'type'    => 'text',
				),
				'feat_8_text'    => array(
					'default' => 'מי תרם, מתי, וכמה - הכל שקוף.',
					'type'    => 'textarea',
				),
			),
		),
		'psalms_testimonials' => array(
			'title'  => 'תכנים - המלצות',
			'fields' => array(
				'tst_title'  => array(
					'default' => 'סיפורים מקהילות שהתאחדו סביב תהילים',
					'type'    => 'text',
				),
				'tst_1_name' => array(
					'default' => 'משפחת לוי',
					'type'    => 'text',
				),
				'tst_1_role' => array(
					'default' => 'תל אביב',
					'type'    => 'text',
				),
				'tst_1_text' => array(
					'default' => 'תוך שבוע השלמנו 100 ספרי תהילים לרפואת אבא. לא האמנו כמה אנשים הצטרפו.',
					'type'    => 'textarea',
				),
				'tst_2_name' => array(
					'default' => 'ביה״כ ׳אור החיים׳',
					'type'    => 'text',
				),
				'tst_2_role' => array(
					'default' => 'ירושלים',
					'type'    => 'text',
				),
				'tst_2_text' => array(
					'default' => 'הקמפיין איחד את הקהילה כמו שלא ראינו שנים. הילדים והנכדים השתתפו ביחד.',
					'type'    => 'textarea',
				),
				'tst_3_name' => array(
					'default' => 'שרה מ.',
					'type'    => 'text',
				),
				'tst_3_role' => array(
					'default' => 'בני ברק',
					'type'    => 'text',
				),
				'tst_3_text' => array(
					'default' => 'פתחתי קמפיין לישועה והקהל הגיב מעבר לכל דמיון. כלי פשוט שמשנה הכל.',
					'type'    => 'textarea',
				),
				'tst_4_name' => array(
					'default' => 'משפחת כהן',
					'type'    => 'text',
				),
				'tst_4_role' => array(
					'default' => 'פתח תקווה',
					'type'    => 'text',
				),
				'tst_4_text' => array(
					'default' => 'ביום אחד גייסנו 80 משתתפים. הצפיה במד מתמלא היתה מרגשת עד דמעות.',
					'type'    => 'textarea',
				),
				'tst_5_name' => array(
					'default' => 'קהילת ׳נר תמיד׳',
					'type'    => 'text',
				),
				'tst_5_role' => array(
					'default' => 'חיפה',
					'type'    => 'text',
				),
				'tst_5_text' => array(
					'default' => 'פתחנו קמפיין לעילוי נשמה והצלחנו לאחד דורות שלמים במשפחה סביב מטרה אחת.',
					'type'    => 'textarea',
				),
				'tst_6_name' => array(
					'default' => 'רחל ב.',
					'type'    => 'text',
				),
				'tst_6_role' => array(
					'default' => 'אשדוד',
					'type'    => 'text',
				),
				'tst_6_text' => array(
					'default' => 'השגרירים הניעו את הקמפיין יותר מכל דבר שדמיינתי. כל אחת הביאה עוד חברות.',
					'type'    => 'textarea',
				),
			),
		),
		'psalms_faq'          => array(
			'title'  => 'תכנים - שאלות נפוצות',
			'fields' => array(
				'faq_eyebrow' => array(
					'default' => 'שאלות ותשובות',
					'type'    => 'text',
				),
				'faq_title'   => array(
					'default' => 'שאלות נפוצות',
					'type'    => 'text',
				),
				'faq_sub'     => array(
					'default' => 'כל מה שצריך לדעת לפני שפותחים קמפיין. עוד שאלה? אנחנו כאן.',
					'type'    => 'textarea',
				),
				'faq_1_q'     => array(
					'default' => 'האם השימוש בפלטפורמה כרוך בתשלום?',
					'type'    => 'text',
				),
				'faq_1_a'     => array(
					'default' => 'לא. השימוש חינמי לחלוטין. בלי הגבלות, בלי כרטיס אשראי.',
					'type'    => 'textarea',
				),
				'faq_2_q'     => array(
					'default' => 'האם זה אתר לקריאת תהילים?',
					'type'    => 'text',
				),
				'faq_2_a'     => array(
					'default' => 'לא. זו פלטפורמת קמפיינים - מסביב למטרה משותפת. את התהילים אומרים בכל ספר או אפליקציה שתבחרו, וכאן מסמנים את הפרקים שאמרתם.',
					'type'    => 'textarea',
				),
				'faq_3_q'     => array(
					'default' => 'איך נספרים הפרקים?',
					'type'    => 'text',
				),
				'faq_3_a'     => array(
					'default' => 'כל משתתף מסמן בלחיצה אחת איזה פרק או ספר השלים. הספירה מתעדכנת מיידית בקמפיין.',
					'type'    => 'textarea',
				),
				'faq_4_q'     => array(
					'default' => 'מהו תפקיד השגריר?',
					'type'    => 'text',
				),
				'faq_4_a'     => array(
					'default' => 'שגריר מקבל לינק אישי, מגייס משתתפים מהקהילה שלו, ומופיע בלוח השגרירים של הקמפיין.',
					'type'    => 'textarea',
				),
				'faq_5_q'     => array(
					'default' => 'האם ניתן לשתף את הקמפיין?',
					'type'    => 'text',
				),
				'faq_5_a'     => array(
					'default' => 'בוודאי - וואטסאפ, פייסבוק, מייל, או העתקת לינק. ככל שמשתפים יותר, מגיעים ליעד מהר יותר.',
					'type'    => 'textarea',
				),
				'faq_6_q'     => array(
					'default' => 'כמה זמן לוקח לפתוח קמפיין?',
					'type'    => 'text',
				),
				'faq_6_a'     => array(
					'default' => 'בערך שתי דקות. שם, מטרה, יעד - והקמפיין באוויר.',
					'type'    => 'textarea',
				),
				'faq_7_q'     => array(
					'default' => 'האם הנתונים שלי מאובטחים?',
					'type'    => 'text',
				),
				'faq_7_a'     => array(
					'default' => 'כן. אנחנו אוספים רק את המינימום הדרוש להפעלת הקמפיין, ולא משתפים אותו עם אף גורם.',
					'type'    => 'textarea',
				),
			),
		),
		'psalms_cta'          => array(
			'title'  => 'תכנים - קריאה לפעולה',
			'fields' => array(
				'cta_title'  => array(
					'default' => 'מוכנים לפתוח קמפיין?',
					'type'    => 'text',
				),
				'cta_sub'    => array(
					'default' => 'צרו קמפיין, הזמינו משתתפים, חלקו פרקים ועקבו אחרי ההתקדמות בזמן אמת.',
					'type'    => 'textarea',
				),
				'cta_button' => array(
					'default' => 'יצירת קמפיין',
					'type'    => 'text',
				),
			),
		),
		'psalms_about'        => array(
			'title'  => 'תכנים - אודות',
			'fields' => array(
				'about_eyebrow'       => array(
					'default' => 'אודות',
					'type'    => 'text',
				),
				'about_title'         => array(
					'default' => 'אודות הפלטפורמה',
					'type'    => 'text',
				),
				'about_sub'           => array(
					'default' => 'כלי קהילתי חינמי לאיחוד אנשים סביב אמירת תהילים.',
					'type'    => 'textarea',
				),
				'about_mission_title' => array(
					'default' => 'המשימה שלנו',
					'type'    => 'text',
				),
				'about_mission_text'  => array(
					'default' => 'האמנו שאמירת תהילים משותפת היא רגע של איחוד יוצא דופן - בין משפחה לחבר, בין קהילה לקהל רחב. בנינו פלטפורמה פשוטה שמאפשרת לכל אחד לפתוח קמפיין תוך דקות, להזמין שגרירים, ולעקוב יחד אחרי ההתקדמות עד היעד.',
					'type'    => 'textarea',
				),
				'about_story_title'   => array(
					'default' => 'הסיפור שלנו',
					'type'    => 'text',
				),
				'about_story_text'    => array(
					'default' => 'הפלטפורמה נולדה מתוך צורך אמיתי - קמפיין לרפואת קרוב משפחה הצריך טבלאות אקסל, הודעות וואטסאפ מבולגנות וספירה ידנית. הבנו שזה צריך להיות פשוט. בנינו את הכלי שחיפשנו, ופתחנו אותו לכולם - חינם.',
					'type'    => 'textarea',
				),
				'about_values_title'  => array(
					'default' => 'מה מנחה אותנו',
					'type'    => 'text',
				),
				'val_1_title'         => array(
					'default' => 'פשטות לפני הכל',
					'type'    => 'text',
				),
				'val_1_text'          => array(
					'default' => 'שתי דקות לפתיחת קמפיין, לחיצה אחת לסימון פרק.',
					'type'    => 'textarea',
				),
				'val_2_title'         => array(
					'default' => 'חינמי תמיד',
					'type'    => 'text',
				),
				'val_2_text'          => array(
					'default' => 'בלי כרטיס אשראי, בלי הגבלות, בלי פרסומות.',
					'type'    => 'textarea',
				),
				'val_3_title'         => array(
					'default' => 'קהילה אמיתית',
					'type'    => 'text',
				),
				'val_3_text'          => array(
					'default' => 'כלים ששמים את המשתתפים והשגרירים במרכז.',
					'type'    => 'textarea',
				),
				'val_4_title'         => array(
					'default' => 'שקיפות מלאה',
					'type'    => 'text',
				),
				'val_4_text'          => array(
					'default' => 'כל פרק, כל ספר, כל תרומה - גלויים לעין כל.',
					'type'    => 'textarea',
				),
				'about_cta_title'     => array(
					'default' => 'מוכנים לפתוח קמפיין?',
					'type'    => 'text',
				),
				'about_cta_sub'       => array(
					'default' => 'שתי דקות והקמפיין שלכם באוויר. בלי עלות, בלי הגבלה.',
					'type'    => 'textarea',
				),
				'about_cta_button'    => array(
					'default' => 'צרו קמפיין',
					'type'    => 'text',
				),
			),
		),
		'psalms_pages'        => array(
			'title'  => 'תכנים - כותרות עמודים',
			'fields' => array(
				'campaigns_title' => array(
					'default' => 'קמפיינים',
					'type'    => 'text',
				),
				'campaigns_sub'   => array(
					'default' => 'כל הקמפיינים הפעילים במקום אחד.',
					'type'    => 'textarea',
				),
				'create_title'    => array(
					'default' => 'יצירת קמפיין',
					'type'    => 'text',
				),
				'create_sub'      => array(
					'default' => 'פתחו קמפיין תהילים בשתי דקות, בחרו מטרה ויעד, והזמינו את הקהילה.',
					'type'    => 'textarea',
				),
				'dashboard_title' => array(
					'default' => 'אזור אישי',
					'type'    => 'text',
				),
				'dashboard_sub'   => array(
					'default' => 'הקמפיינים שלכם, הפעילות וההתקדמות - במקום אחד.',
					'type'    => 'textarea',
				),
				'subscribe_title' => array(
					'default' => 'תהילים יומי',
					'type'    => 'text',
				),
				'subscribe_sub'   => array(
					'default' => 'הרשמה לקבלת פרק יומי ותזכורות חכמות ישירות לוואטסאפ.',
					'type'    => 'textarea',
				),
				'auth_title'      => array(
					'default' => 'התחברות',
					'type'    => 'text',
				),
				'auth_sub'        => array(
					'default' => 'כדי לפתוח ולנהל קמפיין.',
					'type'    => 'text',
				),
			),
		),
		'psalms_footer'       => array(
			'title'  => 'תכנים - מותג ופוטר',
			'fields' => array(
				'brand'                 => array(
					'default' => 'קמפייני תהילים',
					'type'    => 'text',
				),
				'footer_tagline'        => array(
					'default' => 'פלטפורמה חינמית לאיחוד קהילות סביב אמירת תהילים - כי יחד מגיעים רחוק יותר.',
					'type'    => 'textarea',
				),
				'footer_made'           => array(
					'default' => 'נבנה באהבה לעם ישראל',
					'type'    => 'text',
				),
				'footer_rights'         => array(
					'default' => 'כל הזכויות שמורות.',
					'type'    => 'text',
				),
				'footer_free'           => array(
					'default' => 'חינמי לחלוטין',
					'type'    => 'text',
				),
				'footer_product'        => array(
					'default' => 'המוצר',
					'type'    => 'text',
				),
				'footer_company'        => array(
					'default' => 'אודות',
					'type'    => 'text',
				),
				'footer_resources'      => array(
					'default' => 'משאבים',
					'type'    => 'text',
				),
				'footer_link_explore'   => array(
					'default' => 'גלו קמפיינים',
					'type'    => 'text',
				),
				'footer_link_create'    => array(
					'default' => 'פתחו קמפיין',
					'type'    => 'text',
				),
				'footer_link_dashboard' => array(
					'default' => 'האזור שלי',
					'type'    => 'text',
				),
				'footer_link_about'     => array(
					'default' => 'אודות הפלטפורמה',
					'type'    => 'text',
				),
				'footer_link_faq'       => array(
					'default' => 'שאלות נפוצות',
					'type'    => 'text',
				),
			),
		),
	);
}

function psalms_unite_text( $key ) {
	static $flat = null;
	if ( null === $flat ) {
		$flat = array();
		foreach ( psalms_unite_text_config() as $section ) {
			foreach ( $section['fields'] as $k => $f ) {
				$flat[ $k ] = $f['default'];
			}
		}
	}
	$default = isset( $flat[ $key ] ) ? $flat[ $key ] : '';
	$value   = (string) get_theme_mod( 'psalms_txt_' . $key, $default );
	return '' !== trim( $value ) ? $value : $default;
}

add_action(
	'customize_register',
	static function ( $wp_customize ) {
		$wp_customize->add_panel(
			'psalms_unite_texts',
			array(
				'title'    => __( 'Psalms Unite - תכנים', 'psalms-unite' ),
				'priority' => 30,
			)
		);
		foreach ( psalms_unite_text_config() as $section_id => $section ) {
			$wp_customize->add_section(
				$section_id,
				array(
					'title' => $section['title'],
					'panel' => 'psalms_unite_texts',
				)
			);
			foreach ( $section['fields'] as $key => $field ) {
				$setting = 'psalms_txt_' . $key;
				$is_area = 'textarea' === $field['type'];
				$wp_customize->add_setting(
					$setting,
					array(
						'default'           => $field['default'],
						'sanitize_callback' => $is_area ? 'sanitize_textarea_field' : 'sanitize_text_field',
						'transport'         => 'refresh',
					)
				);
				$wp_customize->add_control(
					$setting,
					array(
						'label'   => $key,
						'section' => $section_id,
						'type'    => $is_area ? 'textarea' : 'text',
					)
				);
			}
		}
	}
);
