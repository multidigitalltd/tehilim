<?php
/**
 * Prayers ("תפילות") section — SEO-focused content hub.
 *
 * Registers a `prayer` custom post type and a hierarchical `prayer_cat`
 * taxonomy and seeds keyword-rich categories. Prayers themselves are
 * entered by the site owner through the "ניהול תפילות" dashboard
 * (inc/prayers-admin.php) — no sample prayers are auto-generated.
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
		'tefilot-parnasa' => array( 'תפילות לפרנסה', 'אוסף תפילות לפרנסה טובה, לפרנסה בשפע וברווח ובכבוד. פרקים ותפילות מעומק הלב לפרנסה קלה, להצלחה בעסק ובעבודה ולסייעתא דשמיא בכל מעשי ידיכם.' ),
		'tefilot-refua'   => array( 'תפילות לרפואה', 'תפילות לרפואה שלמה לחולה, לרפואת הגוף והנפש ולהחלמה מהירה, בתוך שאר חולי ישראל.' ),
		'tefilot-zivug'   => array( 'תפילות לזיווג', 'תפילות למציאת זיווג הגון במהרה, לרווקים ולרווקות ולזיווג משמים בזמן הנכון.' ),
		'tefilot-shalom-bait' => array( 'תפילות לשלום בית', 'תפילות לשלום בית, לאהבה ואחווה בין בני הזוג ולבניית בית נאמן בישראל מתוך שלווה.' ),
		'tefilot-yeladim'  => array( 'תפילות לילדים', 'תפילות להצלחת הילדים, לחינוך טוב, ליראת שמים ולנחת. תפילת השל״ה ותפילות הורים לזרע של קיימא.' ),
		'tefilot-hodaya'   => array( 'תפילות הודיה', 'תפילות ומזמורי הודיה ושבח לה׳ להודות על הטוב, על הנס ועל החסד, מתוך שמחה והכרת הטוב.' ),
		'tefilot-shmira'   => array( 'תפילות לשמירה והגנה', 'תפילות לשמירה מכל רע, להגנה מפגע ומעין הרע ולביטחון בה׳ בכל עת.' ),
		'tefilot-tzara'    => array( 'תפילות לעת צרה', 'תפילות לעת צרה ומצוקה — לישועה, לרחמים ולמענה מהיר מן השמים.' ),
		'tefilot-hatzlacha' => array( 'תפילות להצלחה', 'תפילות להצלחה בכל דרך — במבחן, במשפט, בעבודה ובכל מעשי ידיכם.' ),
		'tefilot-yoledet'  => array( 'תפילות להריון וללידה', 'תפילות להריון בריא, ללידה קלה ולזרע של קיימא, ותפילות ליולדת.' ),
		'segulot'          => array( 'סגולות', 'סגולות נבחרות מרבותינו — סגולות ותפילות המסוגלות לפרנסה, לרפואה, לשמירה מעין הרע, לזיווג ולהצלחה.' ),
		'brachot'          => array( 'ברכות ותפילות יום־יום', 'ברכות ותפילות לכל יום: ברכת כהנים, קריאת שמע שעל המיטה, תפילת הדרך וברכות לחיים.' ),
		'tefilot-klaliyot' => array( 'תפילות לעם ישראל', 'תפילות לכלל ישראל — לשלום העם, לשלום חיילי צה״ל, לגאולה שלמה ולאחדות.' ),
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
// Auto-seeding is disabled — prayers are entered by the site owner through the
// "ניהול תפילות" dashboard. Kept for reference / manual re-seed only.

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
 * Migration: the Prayers section must contain actual prayers — not Tehilim
 * chapters. Remove any earlier chapter-based entries (Psalms belong in the
 * dedicated /tehilim/ reader) and seed a set of prayers per category.
 * Prayer texts here are either ancient public-domain liturgy or original
 * devotional compositions written for this site — never third-party
 * copyrighted material. Runs once.
 */
function tehilim_migrate_prayers_content() {
	if ( get_option( 'tehilim_prayers_v4' ) ) {
		return;
	}

	// Remove every auto-generated prayer: the old chapter-based entries
	// (Psalms belong in the /tehilim/ reader) and any theme-seeded prayers.
	// From here on prayers are entered by the site owner via the dashboard,
	// so no sample content remains.
	$auto = get_posts( array(
		'post_type'      => 'prayer',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => 'psalms_chapter', 'compare' => 'EXISTS' ),
			array( 'key' => '_tehilim_seeded_prayer', 'compare' => 'EXISTS' ),
		),
	) );
	foreach ( $auto as $pid ) {
		wp_delete_post( $pid, true );
	}

	update_option( 'tehilim_prayers_v4', 1 );
	delete_option( 'tehilim_prayers_rewrites' );
}
add_action( 'init', 'tehilim_migrate_prayers_content', 26 );

/**
 * The seed prayer set — original devotional compositions + ancient
 * public-domain liturgy, grouped by category. Filterable so a site can
 * add its own nuschaot.
 *
 * @return array<int, array{title:string,cat:string,excerpt:string,intro:string,body:string}>
 */
function tehilim_seed_prayer_set() {
	$set = array(
		// ---- פרנסה ----
		array( 'cat' => 'tefilot-parnasa', 'title' => 'תפילה לפני היציאה לעבודה', 'excerpt' => 'תפילה קצרה לאמירה לפני היום — לסייעתא דשמיא ולהצלחה במעשי הידיים.',
			'intro' => 'תפילה קצרה לפני תחילת יום העבודה, לבקש סייעתא דשמיא והצלחה.',
			'body' => "רִבּוֹנוֹ שֶׁל עוֹלָם, הִנְנִי יוֹצֵא לְמַעֲשַׂי. תֵּן בְּיָדַי בְּרָכָה וְהַצְלָחָה, וְשַׁלַּח סִיַּעְתָּא דִשְׁמַיָּא בְּכָל אֲשֶׁר אֶפְנֶה. תֵּן לִי לֵב לָדַעַת שֶׁהַכֹּל מִיָּדְךָ, וְאֶשָּׂא חֵן בְּעֵינֵי כָּל רוֹאַי. אָמֵן." ),
		array( 'cat' => 'tefilot-parnasa', 'title' => 'תפילה לפרנסה ברווח ולא בצער', 'excerpt' => 'בקשה לפרנסה בשפע וברווח, בכבוד וללא דאגה.',
			'intro' => 'בקשה שהפרנסה תבוא בכבוד וברווח, ומתוך מנוחת הנפש.',
			'body' => "אָבִינוּ שֶׁבַּשָּׁמַיִם, זַכֵּנִי לְפַרְנָסָה בְּרֶוַח וְלֹא בְּצַעַר, בְּכָבוֹד וְלֹא בְּבִזָּיוֹן, מִיָּדְךָ הַמְּלֵאָה וְהָרְחָבָה. הָסֵר מִמֶּנִּי דְּאָגָה, וְתֵן בְּלִבִּי בִּטָּחוֹן שָׁלֵם בְּךָ. אָמֵן." ),

		// ---- רפואה ----
		array( 'cat' => 'tefilot-refua', 'title' => 'תפילה לרפואת הנפש', 'excerpt' => 'תפילה לשלוות הנפש, לרוגע ולחיזוק פנימי.',
			'intro' => 'תפילה לרפואת הנפש, לרוגע ולחיזוק בעת מצוקה נפשית.',
			'body' => "רְפָאֵנִי ה׳ וְאֵרָפֵא. הָסֵר מִלִּבִּי עֶצֶב וְדַאֲגָה, וּמַלֵּא אוֹתִי אֱמוּנָה, שַׁלְוָה וְשִׂמְחָה. חַזֵּק אֶת רוּחִי, וְתֵן בִּי כֹּחַ לְהַמְשִׁיךְ מִתּוֹךְ תִּקְוָה. אָמֵן." ),
		array( 'cat' => 'tefilot-refua', 'title' => 'תפילה קודם ביקור אצל רופא', 'excerpt' => 'בקשה שהרופא יכוון לרפואה ושתבוא החלמה.',
			'intro' => 'נהוג לבקש רחמים לפני בדיקה או טיפול, שהרופאים יצליחו בדרכם.',
			'body' => "יְהִי רָצוֹן מִלְּפָנֶיךָ ה׳ אֱלֹהַי, שֶׁתִּשְׁלַח דְּבַר רְפוּאָה עַל יְדֵי הָרוֹפְאִים, וְתַכְוִינֵם לְרַפְּאֹתֵנִי רְפוּאָה שְׁלֵמָה. כִּי אֵל רוֹפֵא נֶאֱמָן וְרַחֲמָן אָתָּה. אָמֵן." ),

		// ---- זיווג ----
		array( 'cat' => 'tefilot-zivug', 'title' => 'תפילת הורים לזיווג הבן והבת', 'excerpt' => 'תפילת הורים שבנם או בתם ימצאו את זיווגם ההגון.',
			'intro' => 'תפילה שנושאים הורים לזיווגם ההגון של ילדיהם.',
			'body' => "רִבּוֹנוֹ שֶׁל עוֹלָם, זַכֵּה אֶת בְּנֵנוּ/בִּתֵּנוּ לִמְצֹא אֶת זִוּוּגָם הָהָגוּן מְהֵרָה, בְּעֵת רָצוֹן. יִבְנוּ בַּיִת נֶאֱמָן בְּיִשְׂרָאֵל, מִתּוֹךְ אַהֲבָה, אֱמוּנָה וְשָׁלוֹם, וְנִזְכֶּה לְנַחַת. אָמֵן." ),

		// ---- שלום בית ----
		array( 'cat' => 'tefilot-shalom-bait', 'title' => 'תפילה לשלום בית ולאהבה', 'excerpt' => 'בקשה לאהבה, אחווה ושלום בין בני הזוג.',
			'intro' => 'תפילה לשלום בית — לחיזוק האהבה והשלום בין בני הזוג.',
			'body' => "רִבּוֹנוֹ שֶׁל עוֹלָם, הַשְׁכֵּן אַהֲבָה וְאַחֲוָה, שָׁלוֹם וְרֵעוּת בֵּינֵינוּ. תֵּן בְּלִבֵּנוּ סַבְלָנוּת וּמְחִילָה, וְנִזְכֶּה לִבְנוֹת בַּיִת שֶׁל שַׁלְוָה וְשִׂמְחָה, מִשְׁכַּן לִשְׁכִינָתְךָ. אָמֵן." ),

		// ---- ילדים ----
		array( 'cat' => 'tefilot-yeladim', 'title' => 'תפילת הורים להצלחת הילדים', 'excerpt' => 'תפילה לחינוך טוב, ליראת שמים ולנחת מהילדים.',
			'intro' => 'תפילה יומית שנושאים הורים להצלחת ילדיהם ולחינוכם.',
			'body' => "אָבִינוּ שֶׁבַּשָּׁמַיִם, זַכֵּנוּ לְגַדֵּל אֶת יְלָדֵינוּ לְתוֹרָה, לְיִרְאַת שָׁמַיִם וּלְמַעֲשִׂים טוֹבִים. תֵּן בָּהֶם בְּרִיאוּת, חָכְמָה וְלֵב טוֹב, וְנִזְכֶּה לִרְאוֹת בָּהֶם רַק נַחַת. אָמֵן." ),
		array( 'cat' => 'tefilot-yeladim', 'title' => 'תפילה לפני מבחן', 'excerpt' => 'תפילה קצרה של תלמיד לפני מבחן — ליישוב הדעת ולהצלחה.',
			'intro' => 'תפילה קצרה לאמירה לפני בחינה, ליישוב הדעת ולהצלחה.',
			'body' => "רִבּוֹנוֹ שֶׁל עוֹלָם, תֵּן בִּי יִשּׁוּב הַדַּעַת וּמְנוּחַת הַנֶּפֶשׁ. פְּתַח אֶת לִבִּי, וְעָזְרֵנִי לְהַרְאוֹת אֶת אֲשֶׁר לָמַדְתִּי וּלְהַצְלִיחַ. אָמֵן." ),

		// ---- הודיה ----
		array( 'cat' => 'tefilot-hodaya', 'title' => 'מודה אני', 'excerpt' => 'נוסח "מודה אני" הנאמר עם הקימה בבוקר.',
			'intro' => 'מודה אני נאמר מיד עם הקימה בבוקר, כהכרת הטוב על החזרת הנשמה.',
			'body' => "מוֹדֶה אֲנִי לְפָנֶיךָ מֶלֶךְ חַי וְקַיָּם, שֶׁהֶחֱזַרְתָּ בִּי נִשְׁמָתִי בְּחֶמְלָה — רַבָּה אֱמוּנָתֶךָ." ),
		array( 'cat' => 'tefilot-hodaya', 'title' => 'תפילת הודיה על הטוב', 'excerpt' => 'תפילה להודות לה׳ על החסד ועל הטובה.',
			'intro' => 'תפילת הודיה קצרה — להכיר טובה ולהודות על החסדים.',
			'body' => "מוֹדֶה אֲנִי לְפָנֶיךָ ה׳ אֱלֹהַי עַל כָּל הַטּוֹב שֶׁגָּמַלְתָּ עִמָּדִי. עֵינַי נְשׂוּאוֹת אֵלֶיךָ בְּהוֹדָיָה, וְלִבִּי מָלֵא תּוֹדָה עַל חַסְדֶּךָ בְּכָל עֵת. אָמֵן." ),

		// ---- שמירה ----
		array( 'cat' => 'tefilot-shmira', 'title' => 'תפילה לשמירה מכל רע', 'excerpt' => 'בקשה לשמירה מכל פגע ולהגנה מן השמים.',
			'intro' => 'תפילה קצרה לשמירה מכל רע ולהגנה בכל הדרכים.',
			'body' => "שׁוֹמֵר יִשְׂרָאֵל, שְׁמֹר אוֹתִי וְאֶת בְּנֵי בֵיתִי מִכָּל רַע. הָגֵן עָלֵינוּ מִכָּל פֶּגַע וּמִכָּל צָרָה, וְהוֹלִיכֵנוּ לְשָׁלוֹם. בְּצֵל כְּנָפֶיךָ נֶחְסֶה. אָמֵן." ),

		// ---- לעת צרה ----
		array( 'cat' => 'tefilot-tzara', 'title' => 'תפילה לעת צרה', 'excerpt' => 'בקשת רחמים וישועה בעת מצוקה.',
			'intro' => 'תפילה מעומק הלב לעת צרה — לבקש ישועה ורחמים.',
			'body' => "מִן הַמֵּצַר קָרָאתִי יָּהּ, עֲנֵנִי בְמֶרְחָב. רִבּוֹנוֹ שֶׁל עוֹלָם, שְׁמַע אֶת קוֹלִי בְּעֵת צָרָתִי, וּשְׁלַח לִי יְשׁוּעָה וְרַחֲמִים מְהֵרָה. אַל תַּסְתֵּר פָּנֶיךָ מִמֶּנִּי. אָמֵן." ),

		// ---- הצלחה ----
		array( 'cat' => 'tefilot-hatzlacha', 'title' => 'תפילה להצלחה בכל דרך', 'excerpt' => 'בקשה להצלחה ולסייעתא דשמיא בכל מעשי הידיים.',
			'intro' => 'תפילה לפני מעשה חשוב — לבקש הצלחה וברכה.',
			'body' => "יְהִי רָצוֹן מִלְּפָנֶיךָ ה׳ אֱלֹהַי, שֶׁתַּצְלִיחַ אֶת דְּרָכַי וְאֶת כָּל מַעֲשֵׂי יָדַי. תֵּן בִּי חָכְמָה וְדַעַת, וְשַׂמְתָּ בְּפִי מִלִּים נְכוֹנוֹת, וְאֶמְצָא חֵן וְשֵׂכֶל טוֹב בְּעֵינֶיךָ וּבְעֵינֵי אָדָם. אָמֵן." ),

		// ---- הריון ולידה ----
		array( 'cat' => 'tefilot-yoledet', 'title' => 'תפילה להריון בריא וללידה קלה', 'excerpt' => 'תפילה לשמירת ההריון וללידה קלה ובטוחה.',
			'intro' => 'תפילה לשמירת ההריון ולידה קלה, לבריאות האם והתינוק.',
			'body' => "רִבּוֹנוֹ שֶׁל עוֹלָם, שְׁמֹר נָא עַל הָעֻבָּר וְעַל אִמּוֹ. תֵּן הֵרָיוֹן בָּרִיא וּשְׁלֵם, וְלֵדָה קַלָּה וּבְטוּחָה בְּעִתָּהּ, וְזַכֵּנוּ לְגַדֵּל אֶת הַיֶּלֶד לְתוֹרָה וּלְמַעֲשִׂים טוֹבִים. אָמֵן." ),

		// ---- עם ישראל ----
		array( 'cat' => 'tefilot-klaliyot', 'title' => 'תפילה לשלום עם ישראל', 'excerpt' => 'בקשה לשלום, אחדות וישועה לכלל ישראל.',
			'intro' => 'תפילה לשלום עם ישראל, לאחדות ולישועה.',
			'body' => "אָבִינוּ שֶׁבַּשָּׁמַיִם, שְׁמֹר נָא עַל עַמְּךָ יִשְׂרָאֵל בְּכָל מְקוֹמוֹת מוֹשְׁבוֹתֵיהֶם. תֵּן שָׁלוֹם וְאַחְדוּת בָּאָרֶץ, וְקָרֵב לִבּוֹת בָּנֶיךָ זֶה לָזֶה. שְׁלַח רְפוּאָה לְחוֹלֵינוּ וּפְדוּת לְכָל הַנְּתוּנִים בְּצָרָה. אָמֵן." ),
		array( 'cat' => 'tefilot-klaliyot', 'title' => 'תפילה לשלום חיילי צה״ל וכוחות הביטחון', 'excerpt' => 'בקשה לשמירה על החיילים ולשובם לשלום.',
			'intro' => 'תפילה לשמירה על חיילי צה״ל וכוחות הביטחון.',
			'body' => "רִבּוֹנוֹ שֶׁל עוֹלָם, שְׁמֹר נָא עַל חַיָּלֵי צְבָא הַהֲגַנָּה לְיִשְׂרָאֵל וְעַל כָּל אַנְשֵׁי כֹּחוֹת הַבִּטָּחוֹן. הָגֵן עֲלֵיהֶם מִכָּל פֶּגַע, חַזֵּק אֶת יְדֵיהֶם, וַהֲשִׁיבֵם לְבָתֵּיהֶם לְשָׁלוֹם וּלְחַיִּים טוֹבִים. אָמֵן." ),

		// ---- ברכות ----
		array( 'cat' => 'brachot', 'title' => 'שמע ישראל', 'excerpt' => 'פסוק "שמע ישראל" — קבלת עול מלכות שמים.',
			'intro' => 'פסוק "שמע ישראל" נאמר בקריאת שמע בבוקר ובערב, כקבלת עול מלכות שמים.',
			'body' => "שְׁמַע יִשְׂרָאֵל, ה׳ אֱלֹהֵינוּ, ה׳ אֶחָד.\nבָּרוּךְ שֵׁם כְּבוֹד מַלְכוּתוֹ לְעוֹלָם וָעֶד." ),
	);

	return apply_filters( 'tehilim_seed_prayer_set', $set );
}

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
