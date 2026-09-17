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
		'tefilot-parnasa' => array( 'תפילות לפרנסה', 'אוסף תפילות לפרנסה טובה, לפרנסה בשפע וברווח ובכבוד. תפילות מעומק הלב לפרנסה קלה, להצלחה בעסק ובעבודה ולסייעתא דשמיא בכל מעשי ידיכם.' ),
		'tefilot-refua'   => array( 'תפילות לרפואה', 'תפילות לרפואה שלמה לחולה, תפילה לרפואת הגוף והנפש ולהחלמה מהירה. פרקי תהילים ותפילות לרפואה בתוך שאר חולי ישראל.' ),
		'tefilot-zivug'   => array( 'תפילות לזיווג', 'תפילות למציאת זיווג הגון במהרה, תפילה לזיווג לרווקים ולרווקות ולזיווג משמים בזמן הנכון.' ),
		'tefilot-shalom-bait' => array( 'תפילות לשלום בית', 'תפילות לשלום בית, לאהבה ואחווה בין בני הזוג, ולבניית בית נאמן בישראל מתוך שלווה והרמוניה.' ),
		'tefilot-yeladim'  => array( 'תפילות לילדים', 'תפילות להצלחת הילדים, לחינוך טוב, ליראת שמים ולנחת מהילדים. תפילת השל״ה ותפילות הורים לזרע של קיימא ולבנים צדיקים.' ),
		'segulot'          => array( 'סגולות', 'סגולות נבחרות מרבותינו — סגולה לפרנסה, סגולה לרפואה, סגולה לשמירה מעין הרע, סגולה לזיווג וסגולות להצלחה מתוך מקורות אמינים.' ),
		'brachot'          => array( 'ברכות ותפילות יום־יום', 'ברכות ותפילות לכל יום: ברכת המזון, קריאת שמע שעל המיטה, ברכות הנהנין, תפילת הדרך וברכות לחיים.' ),
		'tefilot-klaliyot' => array( 'תפילות כלליות', 'תפילות לכלל ישראל — תפילה לשלום עם ישראל, לשלום חיילי צה״ל, לגאולה שלמה ולאחדות בעם.' ),
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
