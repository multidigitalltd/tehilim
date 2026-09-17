<?php
/**
 * Prayers ("תפילות") section — SEO-focused content hub.
 *
 * Registers a `prayer` custom post type and a hierarchical `prayer_cat`
 * taxonomy, seeds keyword-rich categories and a starter set of
 * public-domain liturgical prayers with original introductory copy.
 * Site owners add more prayers via the WordPress editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the prayer CPT.
 */
function tehilim_register_prayer_cpt() {
	register_post_type( 'prayer', array(
		'labels'          => array(
			'name'          => 'תפילות',
			'singular_name' => 'תפילה',
			'add_new_item'  => 'הוספת תפילה',
			'edit_item'     => 'עריכת תפילה',
			'search_items'  => 'חיפוש תפילות',
		),
		'public'          => true,
		'has_archive'     => 'prayers',
		'show_in_rest'    => true,
		'menu_icon'       => 'dashicons-book-alt',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
		'rewrite'         => array( 'slug' => 'tefila', 'with_front' => false ),
		'capability_type' => 'post',
	) );
}
add_action( 'init', 'tehilim_register_prayer_cpt' );

/**
 * Register the prayer category taxonomy.
 */
function tehilim_register_prayer_taxonomy() {
	register_taxonomy( 'prayer_cat', 'prayer', array(
		'labels'       => array(
			'name'          => 'קטגוריות תפילה',
			'singular_name' => 'קטגוריה',
		),
		'public'       => true,
		'hierarchical' => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'tefilot', 'with_front' => false ),
	) );
}
add_action( 'init', 'tehilim_register_prayer_taxonomy' );

/**
 * Seed the prayer categories (idempotent). Descriptions double as
 * keyword-rich intro copy on the category archives.
 */
function tehilim_seed_prayer_categories() {
	$cats = array(
		'tefilot-parnasa' => array( 'תפילות לפרנסה', 'אוסף תפילות ופרקי תהילים לפרנסה טובה, לפרנסה בשפע וברווח ובכבוד. פרקים ותפילות מעומק הלב לפרנסה קלה, להצלחה בעסק ובעבודה ולסייעתא דשמיא בכל מעשי ידיכם.' ),
		'tefilot-refua'   => array( 'תפילות לרפואה', 'תפילות ופרקי תהילים לרפואה שלמה לחולה, לרפואת הגוף והנפש ולהחלמה מהירה, בתוך שאר חולי ישראל.' ),
		'tefilot-zivug'   => array( 'תפילות לזיווג', 'תפילות ופרקי תהילים למציאת זיווג הגון במהרה, לרווקים ולרווקות ולזיווג משמים בזמן הנכון.' ),
		'tefilot-shalom-bait' => array( 'תפילות לשלום בית', 'תפילות ופרקי תהילים לשלום בית, לאהבה ואחווה בין בני הזוג ולבניית בית נאמן בישראל מתוך שלווה.' ),
		'tefilot-yeladim'  => array( 'תפילות לילדים', 'תפילות ופרקי תהילים להצלחת הילדים, לחינוך טוב, ליראת שמים ולנחת. תפילת השל״ה ותפילות הורים לזרע של קיימא.' ),
		'tefilot-hodaya'   => array( 'תפילות הודיה', 'מזמורי הודיה ושבח לה׳ — פרקי תהילים להודות על הטוב, על הנס ועל החסד, מתוך שמחה והכרת הטוב.' ),
		'tefilot-shmira'   => array( 'תפילות לשמירה והגנה', 'תפילות ופרקי תהילים לשמירה מכל רע, להגנה מפגע ומעין הרע ולביטחון בה׳ בכל עת.' ),
		'tefilot-tzara'    => array( 'תפילות לעת צרה', 'תפילות ופרקי תהילים לעת צרה ומצוקה — לישועה, לרחמים ולמענה מהיר מן השמים.' ),
		'tefilot-hatzlacha' => array( 'תפילות להצלחה', 'תפילות ופרקי תהילים להצלחה בכל דרך — במבחן, במשפט, בעבודה ובכל מעשי ידיכם.' ),
		'tefilot-yoledet'  => array( 'תפילות להריון וללידה', 'תפילות ופרקי תהילים להריון בריא, ללידה קלה ולזרע של קיימא, ותפילות ליולדת.' ),
		'segulot'          => array( 'סגולות', 'סגולות נבחרות מרבותינו — פרקי תהילים ותפילות המסוגלים לפרנסה, לרפואה, לשמירה מעין הרע, לזיווג ולהצלחה.' ),
		'brachot'          => array( 'ברכות ותפילות יום־יום', 'ברכות ותפילות לכל יום: ברכת כהנים, קריאת שמע שעל המיטה, תפילת הדרך וברכות לחיים.' ),
		'tefilot-klaliyot' => array( 'תפילות לעם ישראל', 'תפילות ופרקי תהילים לכלל ישראל — לשלום העם, לשלום חיילי צה״ל, לגאולה שלמה ולאחדות.' ),
	);

	foreach ( $cats as $slug => $data ) {
		if ( ! term_exists( $slug, 'prayer_cat' ) ) {
			wp_insert_term( $data[0], 'prayer_cat', array( 'slug' => $slug, 'description' => $data[1] ) );
		}
	}
}
add_action( 'init', 'tehilim_seed_prayer_categories', 20 );

/**
 * Seed a starter set of public-domain liturgical prayers, each with an
 * original introduction. Runs once (guarded by an option).
 */
function tehilim_seed_prayers() {
	if ( get_option( 'tehilim_prayers_seeded' ) ) {
		return;
	}

	$prayers = array(
		array(
			'title'   => 'תפילת הדרך',
			'cat'     => 'brachot',
			'excerpt' => 'תפילת הדרך המלאה לאמירה לפני יציאה לדרך — לנסיעה בטוחה ולשמירה מכל פגע.',
			'intro'   => 'תפילת הדרך נאמרת עם היציאה לדרך, לאחר שיצאו מגבול העיר, ומבקשים בה על נסיעה טובה ובטוחה ועל שמירה מכל צרה. נהוג לאומרה בלשון רבים.',
			'body'    => "יְהִי רָצוֹן מִלְּפָנֶיךָ ה׳ אֱלֹהֵינוּ וֵאלֹהֵי אֲבוֹתֵינוּ, שֶׁתּוֹלִיכֵנוּ לְשָׁלוֹם וְתַצְעִידֵנוּ לְשָׁלוֹם וְתַדְרִיכֵנוּ לְשָׁלוֹם, וְתַגִּיעֵנוּ לִמְחוֹז חֶפְצֵנוּ לְחַיִּים וּלְשִׂמְחָה וּלְשָׁלוֹם. וְתַצִּילֵנוּ מִכַּף כָּל אוֹיֵב וְאוֹרֵב וְלִסְטִים וְחַיּוֹת רָעוֹת בַּדֶּרֶךְ, וּמִכָּל מִינֵי פֻּרְעָנֻיּוֹת הַמִּתְרַגְּשׁוֹת לָבוֹא לָעוֹלָם. וְתִשְׁלַח בְּרָכָה בְּכָל מַעֲשֵׂה יָדֵינוּ, וְתִתְּנֵנִי לְחֵן וּלְחֶסֶד וּלְרַחֲמִים בְּעֵינֶיךָ וּבְעֵינֵי כָל רוֹאֵינוּ. בָּרוּךְ אַתָּה ה׳, שׁוֹמֵעַ תְּפִלָּה.",
		),
		array(
			'title'   => 'ברכת כהנים',
			'cat'     => 'brachot',
			'excerpt' => 'ברכת כהנים מן התורה — ברכה לשמירה, לחן ולשלום.',
			'intro'   => 'ברכת כהנים שבתורה (במדבר ו) היא מן הברכות הקדומות והנעלות, ונהוג לברך בה את הילדים בליל שבת ובכל עת.',
			'body'    => "יְבָרֶכְךָ ה׳ וְיִשְׁמְרֶךָ.\nיָאֵר ה׳ פָּנָיו אֵלֶיךָ וִיחֻנֶּךָּ.\nיִשָּׂא ה׳ פָּנָיו אֵלֶיךָ וְיָשֵׂם לְךָ שָׁלוֹם.",
		),
		array(
			'title'   => 'אנא בכח',
			'cat'     => 'tefilot-klaliyot',
			'excerpt' => 'תפילת "אנא בכח" — תפילה קדומה המסוגלת לישועה ולפתיחת שערי רחמים.',
			'intro'   => 'תפילת "אנא בכח" מיוחסת לתנא רבי נחוניא בן הקנה. זו תפילה עתיקה ונשגבה שנהוג לאומרה בכוונה גדולה כבקשה לישועה, להצלחה ולסיוע משמים.',
			'body'    => "אָנָּא בְּכֹחַ גְּדֻלַּת יְמִינְךָ תַּתִּיר צְרוּרָה.\nקַבֵּל רִנַּת עַמְּךָ שַׂגְּבֵנוּ טַהֲרֵנוּ נוֹרָא.\nנָא גִבּוֹר דּוֹרְשֵׁי יִחוּדְךָ כְּבָבַת שָׁמְרֵם.\nבָּרְכֵם טַהֲרֵם רַחֲמֵי צִדְקָתְךָ תָּמִיד גָּמְלֵם.\nחֲסִין קָדוֹשׁ בְּרֹב טוּבְךָ נַהֵל עֲדָתֶךָ.\nיָחִיד גֵּאֶה לְעַמְּךָ פְּנֵה זוֹכְרֵי קְדֻשָּׁתֶךָ.\nשַׁוְעָתֵנוּ קַבֵּל וּשְׁמַע צַעֲקָתֵנוּ יוֹדֵעַ תַּעֲלוּמוֹת.",
		),
		array(
			'title'   => 'תפילה לפרנסה',
			'cat'     => 'tefilot-parnasa',
			'excerpt' => 'תפילה קצרה מעומק הלב לפרנסה טובה, בשפע וברווח ובכבוד.',
			'intro'   => 'רבים נושאים תפילה לפרנסה טובה ומצויה. אפשר לאומרה בכל עת, ובמיוחד בעת אמירת "פרשת המן" ובימי רצון. הנוסח הבא הוא בקשת רחמים כללית לפרנסה.',
			'body'    => "רִבּוֹנוֹ שֶׁל עוֹלָם, אַתָּה הַזָּן וּמְפַרְנֵס לַכֹּל. פְּתַח נָא אֶת יָדְךָ הַמְּלֵאָה וְהָרְחָבָה וְהַשְׁפַּע עָלַי וְעַל בְּנֵי בֵיתִי פַּרְנָסָה טוֹבָה וּמְצוּיָה, בְּרֶוַח וְלֹא בְּצִמְצוּם, בְּהֶתֵּר וְלֹא בְּאִסּוּר, בְּכָבוֹד וְלֹא בְּבִזָּיוֹן. וְתֵן בְּלִבִּי בִּטָּחוֹן שָׁלֵם בְּךָ, שֶׁאַתָּה זָן וּמְפַרְנֵס בְּרַחֲמִים. אָמֵן.",
		),
		array(
			'title'   => 'תפילה לרפואה שלמה',
			'cat'     => 'tefilot-refua',
			'excerpt' => 'תפילה לרפואת החולה — לרפואת הגוף והנפש בתוך שאר חולי ישראל.',
			'intro'   => 'תפילה לרפואה שלמה נאמרת על החולה ומזכירים בה את שמו ושם אמו. אפשר להוסיף לפניה פרקי תהילים לרפואה.',
			'body'    => "מִי שֶׁבֵּרַךְ אֲבוֹתֵינוּ אַבְרָהָם יִצְחָק וְיַעֲקֹב, מֹשֶׁה וְאַהֲרֹן, דָּוִד וּשְׁלֹמֹה, הוּא יְבָרֵךְ וִירַפֵּא אֶת הַחוֹלֶה. הַקָּדוֹשׁ בָּרוּךְ הוּא יִמָּלֵא רַחֲמִים עָלָיו לְהַחֲלִימוֹ וּלְרַפְּאֹתוֹ, וְיִשְׁלַח לוֹ מְהֵרָה רְפוּאָה שְׁלֵמָה מִן הַשָּׁמַיִם, רְפוּאַת הַנֶּפֶשׁ וּרְפוּאַת הַגּוּף, בְּתוֹךְ שְׁאָר חוֹלֵי יִשְׂרָאֵל. אָמֵן.",
		),
		array(
			'title'   => 'תפילה לזיווג הגון',
			'cat'     => 'tefilot-zivug',
			'excerpt' => 'תפילה למציאת זיווג הגון במהרה ובזמן הנכון.',
			'intro'   => 'תפילה למציאת הזיווג ההגון נאמרת מתוך אמונה ובטחון שהכל בידי שמים. אפשר לאומרה בכל עת ובמיוחד בעת רצון.',
			'body'    => "רִבּוֹנוֹ שֶׁל עוֹלָם, יוֹשֵׁב וּמְזַוֵּג זִוּוּגִים. זַכֵּנִי נָא לִמְצֹא אֶת זִוּוּגִי הָהָגוּן לִי מְהֵרָה, בְּעִתּוֹ וּבִזְמַנּוֹ, וְנִבְנֶה יַחַד בַּיִת נֶאֱמָן בְּיִשְׂרָאֵל עַל יְסוֹדוֹת הַתּוֹרָה וְהַיִּרְאָה, מִתּוֹךְ אַהֲבָה וְשָׁלוֹם וְשַׁלְוָה. אָמֵן.",
		),
	);

	foreach ( $prayers as $p ) {
		$content = '';
		if ( ! empty( $p['intro'] ) ) {
			$content .= '<p class="prayer-intro-text">' . $p['intro'] . "</p>\n\n";
		}
		$content .= '<div class="prayer-body">' . nl2br( $p['body'] ) . '</div>';

		$post_id = wp_insert_post( array(
			'post_type'    => 'prayer',
			'post_title'   => $p['title'],
			'post_status'  => 'publish',
			'post_content' => $content,
			'post_excerpt' => $p['excerpt'],
		) );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			$term = get_term_by( 'slug', $p['cat'], 'prayer_cat' );
			if ( $term ) {
				wp_set_object_terms( $post_id, $term->term_id, 'prayer_cat' );
			}
			update_post_meta( $post_id, '_tehilim_seeded_prayer', 1 );
		}
	}

	update_option( 'tehilim_prayers_seeded', 1 );
}
add_action( 'init', 'tehilim_seed_prayers', 25 );

/**
 * Load the bundled public-domain Psalms text (150 chapters of verses).
 */
function tehilim_load_psalms_data() {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}
	$file = get_template_directory() . '/assets/data/tehilim.json';
	$data = array();
	if ( file_exists( $file ) ) {
		$json = json_decode( file_get_contents( $file ), true );
		if ( is_array( $json ) ) {
			$data = $json;
		}
	}
	return $data;
}

/**
 * Build HTML for one Psalms chapter (verses with Hebrew-numeral markers).
 */
function tehilim_render_psalms_chapter_html( $chapter ) {
	$data = tehilim_load_psalms_data();
	$idx  = intval( $chapter ) - 1;
	if ( ! isset( $data[ $idx ] ) || ! is_array( $data[ $idx ] ) ) {
		return '';
	}
	$out = '<div class="prayer-body">';
	foreach ( $data[ $idx ] as $i => $verse ) {
		$num  = function_exists( 'tehilim_hebrew_numeral' ) ? tehilim_hebrew_numeral( $i + 1 ) : ( $i + 1 );
		$num  = str_replace( array( '׳', '״' ), '', $num );
		$out .= '<div class="chapter-verse"><span class="chapter-verse-num">' . esc_html( $num ) . '</span>' . esc_html( $verse ) . '</div>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * Expanded seeding: one prayer entry per traditional chapter, per cause,
 * using the bundled public-domain Psalms text. Runs once.
 */
function tehilim_seed_prayers_v2() {
	if ( get_option( 'tehilim_prayers_seed_v2' ) ) {
		return;
	}

	// Traditional (widely-published) chapter associations per cause. Framed
	// as "customary" — the verse text itself is public domain.
	$map = array(
		'tefilot-parnasa'     => array( 'label' => 'לפרנסה', 'chapters' => array( 23, 24, 34, 62, 67, 104, 112, 121, 128, 144, 145 ) ),
		'tefilot-refua'       => array( 'label' => 'לרפואה', 'chapters' => array( 6, 20, 22, 23, 30, 38, 41, 88, 91, 102, 103, 142 ) ),
		'tefilot-zivug'       => array( 'label' => 'לזיווג', 'chapters' => array( 32, 38, 68, 70, 72, 121, 124 ) ),
		'tefilot-shalom-bait' => array( 'label' => 'לשלום בית', 'chapters' => array( 45, 46, 121, 127, 128, 130 ) ),
		'tefilot-yeladim'     => array( 'label' => 'להצלחת הילדים', 'chapters' => array( 20, 121, 126, 127, 128, 144 ) ),
		'tefilot-hodaya'      => array( 'label' => 'להודיה', 'chapters' => array( 30, 92, 100, 103, 107, 116, 136, 145, 148, 150 ) ),
		'tefilot-shmira'      => array( 'label' => 'לשמירה', 'chapters' => array( 3, 13, 20, 91, 121, 124, 130 ) ),
		'tefilot-tzara'       => array( 'label' => 'לעת צרה', 'chapters' => array( 20, 22, 69, 86, 102, 130, 142 ) ),
		'tefilot-hatzlacha'   => array( 'label' => 'להצלחה', 'chapters' => array( 1, 20, 57, 86, 90, 112, 121 ) ),
		'tefilot-yoledet'     => array( 'label' => 'להריון וללידה', 'chapters' => array( 20, 100, 112, 126, 128 ) ),
		'tefilot-klaliyot'    => array( 'label' => 'לעם ישראל', 'chapters' => array( 20, 83, 121, 122, 125, 130, 142, 144 ) ),
	);

	foreach ( $map as $cat_slug => $info ) {
		$term = get_term_by( 'slug', $cat_slug, 'prayer_cat' );
		if ( ! $term ) {
			continue;
		}
		foreach ( $info['chapters'] as $ch ) {
			$gem   = function_exists( 'tehilim_hebrew_numeral' ) ? tehilim_hebrew_numeral( $ch ) : $ch;
			$title = sprintf( 'תהילים פרק %s · %s', $gem, $info['label'] );

			// Skip if an identical title already exists
			if ( get_page_by_title( $title, OBJECT, 'prayer' ) ) {
				continue;
			}

			$verses = tehilim_render_psalms_chapter_html( $ch );
			if ( '' === $verses ) {
				continue;
			}

			$intro = sprintf(
				'נהוג לומר את פרק תהילים %s כתפילה %s. אמירת המזמור בכוונה מעוררת רחמי שמים; מוסיפים לאחריה תפילה אישית מעומק הלב.',
				$gem,
				$info['label']
			);
			$content = '<p class="prayer-intro-text">' . esc_html( $intro ) . "</p>\n" . $verses;

			$post_id = wp_insert_post( array(
				'post_type'    => 'prayer',
				'post_title'   => $title,
				'post_status'  => 'publish',
				'post_content' => $content,
				'post_excerpt' => sprintf( 'פרק תהילים %s — מזמור לאמירה %s, עם הקדמה קצרה.', $gem, $info['label'] ),
			) );

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				wp_set_object_terms( $post_id, $term->term_id, 'prayer_cat' );
				update_post_meta( $post_id, '_tehilim_seeded_prayer', 1 );
				update_post_meta( $post_id, 'psalms_chapter', $ch );
			}
		}
	}

	update_option( 'tehilim_prayers_seed_v2', 1 );
	// New content — bump the rewrite flush so category counts refresh
	delete_option( 'tehilim_prayers_rewrites' );
}
add_action( 'init', 'tehilim_seed_prayers_v2', 26 );

/**
 * Flush rewrites once after this module is added (new /prayers, /tefila slugs).
 */
function tehilim_prayers_maybe_flush() {
	if ( get_option( 'tehilim_prayers_rewrites' ) !== '1' ) {
		tehilim_register_prayer_cpt();
		tehilim_register_prayer_taxonomy();
		flush_rewrite_rules();
		update_option( 'tehilim_prayers_rewrites', '1' );
	}
}
add_action( 'init', 'tehilim_prayers_maybe_flush', 99 );
