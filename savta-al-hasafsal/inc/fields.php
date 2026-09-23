<?php
/**
 * Field schema for the one-page site.
 *
 * Every word, image and list on the page is declared here, with the copy from
 * the design as its default. On activation the defaults are written into the
 * homepage's own post meta, so the content lives in the page — not in the theme.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

const SAVTA_META_PREFIX = '_savta_';

/**
 * The section groups, each with its fields.
 *
 * Field types: text, textarea, url, image, checkbox, repeater.
 *
 * @return array<string,array{label:string,anchor:string,fields:array<string,array<string,mixed>>}>
 */
function savta_field_groups(): array {
	static $groups = null;

	if ( null !== $groups ) {
		return $groups;
	}

	$groups = array(
		'hero'   => array(
			'label'  => __( 'אזור פתיחה', 'savta-al-hasafsal' ),
			'anchor' => 'top',
			'fields' => array(
				'hero_eyebrow'    => array(
					'type'    => 'text',
					'label'   => __( 'שורת פתיחה קטנה', 'savta-al-hasafsal' ),
					'default' => 'מיזם חברתי למען הקהילה',
				),
				'hero_lead'       => array(
					'type'    => 'text',
					'label'   => __( 'שורת ההובלה', 'savta-al-hasafsal' ),
					'default' => 'בהובלת אפרת ברזל',
				),
				'hero_title'      => array(
					'type'    => 'textarea',
					'label'   => __( 'כותרת ראשית', 'savta-al-hasafsal' ),
					'desc'    => __( 'שורה חדשה = שבירת שורה בכותרת.', 'savta-al-hasafsal' ),
					'default' => "סבתא\nעל הספסל",
				),
				'hero_lede'       => array(
					'type'    => 'textarea',
					'label'   => __( 'משפט הפתיחה', 'savta-al-hasafsal' ),
					'default' => 'לפעמים כל מה שצריך הוא מישהי טובה שתשב איתך רגע — ותקשיב באמת.',
				),
				'hero_btn_text'   => array(
					'type'    => 'text',
					'label'   => __( 'טקסט הכפתור', 'savta-al-hasafsal' ),
					'default' => 'אני רוצה לדבר עם הסבתא',
				),
				'hero_btn_url'    => array(
					'type'    => 'url',
					'label'   => __( 'יעד הכפתור', 'savta-al-hasafsal' ),
					'default' => '#signup',
				),
				'hero_link_text'  => array(
					'type'    => 'text',
					'label'   => __( 'טקסט הקישור המשני', 'savta-al-hasafsal' ),
					'default' => 'איך זה עובד?',
				),
				'hero_link_url'   => array(
					'type'    => 'url',
					'label'   => __( 'יעד הקישור המשני', 'savta-al-hasafsal' ),
					'default' => '#how-it-works',
				),
				'hero_logo'       => array(
					'type'    => 'image',
					'label'   => __( 'הלוגו הגדול', 'savta-al-hasafsal' ),
					'default' => 'logo.png',
				),
				'hero_info'       => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקת המידע מתחת לפתיחה', 'savta-al-hasafsal' ),
					'default' => 'שיחה אישית, חינמית ודיסקרטית עם אישה עתירת ניסיון חיים, מכילה ומקשיבה — בעלת לב רחב ונוכחות מרגיעה. מקום לפרוק, לנשום, לעשות קצת סדר, ולצאת עם כוח נוסף לצעד הבא.',
				),
				'hero_facts'      => array(
					'type'    => 'repeater',
					'label'   => __( 'שלושת הנתונים', 'savta-al-hasafsal' ),
					'max'     => 3,
					'fields'  => array(
						'label' => array(
							'type'  => 'text',
							'label' => __( 'תווית', 'savta-al-hasafsal' ),
						),
						'value' => array(
							'type'  => 'textarea',
							'label' => __( 'ערך', 'savta-al-hasafsal' ),
						),
					),
					'default' => array(
						array(
							'label' => 'מסגרת',
							'value' => "פרויקט למען\nהקהילה",
						),
						array(
							'label' => 'עלות',
							'value' => 'ללא עלות',
						),
						array(
							'label' => 'זימון',
							'value' => "בתיאום מראש\nבלבד",
						),
					),
				),
				'hero_area_label' => array(
					'type'    => 'text',
					'label'   => __( 'תווית אזור הפעילות', 'savta-al-hasafsal' ),
					'default' => 'אזור פעילות',
				),
				'hero_area_city'  => array(
					'type'    => 'text',
					'label'   => __( 'העיר (מודגש)', 'savta-al-hasafsal' ),
					'default' => 'בני ברק.',
				),
				'hero_area_text'  => array(
					'type'    => 'text',
					'label'   => __( 'המשך שורת האזור', 'savta-al-hasafsal' ),
					'default' => 'בעזרת ה׳, הפעילות תורחב בהמשך לערים נוספות.',
				),
			),
		),
		'intro'  => array(
			'label'  => __( 'פתיח: הספסל בגן', 'savta-al-hasafsal' ),
			'anchor' => 'intro',
			'fields' => array(
				'intro_enable'    => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'intro_image'     => array(
					'type'    => 'image',
					'label'   => __( 'תמונה', 'savta-al-hasafsal' ),
					'default' => 'bench-garden.webp',
				),
				'intro_image_alt' => array(
					'type'    => 'text',
					'label'   => __( 'תיאור התמונה (נגישות)', 'savta-al-hasafsal' ),
					'default' => 'איור: ספסל בגן, אור אחר צהריים',
				),
				'intro_title'     => array(
					'type'    => 'textarea',
					'label'   => __( 'המשפט הגדול', 'savta-al-hasafsal' ),
					'default' => 'יש רגעים שבהם הכול נראה "בסדר" מבחוץ, אבל בפנים יש עומס, מחשבות, דאגה או תחושה שאין באמת עם מי לדבר.',
				),
				'intro_text'      => array(
					'type'    => 'textarea',
					'label'   => __( 'הפסקה', 'savta-al-hasafsal' ),
					'default' => 'לא תמיד צריך פתרונות גדולים. לפעמים צריך מקום שקט, לב פתוח, ואוזן קשבת של מישהי שכבר ראתה הרבה בחיים — ויודעת להקשיב בלי לשפוט.',
				),
			),
		),
		'about'  => array(
			'label'  => __( '01 · מה זה', 'savta-al-hasafsal' ),
			'anchor' => 'about',
			'fields' => array(
				'about_enable'    => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'about_number'    => array(
					'type'    => 'text',
					'label'   => __( 'מספר המקטע', 'savta-al-hasafsal' ),
					'default' => '01',
				),
				'about_title'     => array(
					'type'    => 'textarea',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => "מה זה\n\"סבתא על הספסל\"?",
				),
				'about_image'     => array(
					'type'    => 'image',
					'label'   => __( 'תמונה', 'savta-al-hasafsal' ),
					'default' => 'two-women-bench.webp',
				),
				'about_image_alt' => array(
					'type'    => 'text',
					'label'   => __( 'תיאור התמונה (נגישות)', 'savta-al-hasafsal' ),
					'default' => 'איור: שתי נשים יושבות בשיחה על ספסל',
				),
				'about_p1'        => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה ראשונה', 'savta-al-hasafsal' ),
					'default' => '"סבתא על הספסל" הוא מיזם חברתי שנולד מתוך צורך אמיתי בקהילה: לאפשר לאנשים לעצור לרגע, לשבת לשיחה אישית, לפרוק מה שיושב על הלב, ולקבל הקשבה טובה, מכבדת ולא שיפוטית.',
				),
				'about_quote'     => array(
					'type'    => 'textarea',
					'label'   => __( 'הציטוט המודגש', 'savta-al-hasafsal' ),
					'default' => 'זו לא קליניקה. זה לא טיפול. וזו לא שיחה שמבטיחה לפתור הכול.',
				),
				'about_p2'        => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה שנייה', 'savta-al-hasafsal' ),
					'default' => 'זו שיחה אנושית, חמה ודיסקרטית — עם "סבתא" מקשיבה, מנוסה ורגישה, שעברה הכוונה מתאימה ומגיעה עם ניסיון חיים, לב פתוח ויכולת לראות את האדם שמולה.',
				),
				'about_goal'      => array(
					'type'    => 'textarea',
					'label'   => __( 'משפט המטרה (ליד הכוס)', 'savta-al-hasafsal' ),
					'default' => 'המטרה פשוטה: לתת מקום לדבר, לעשות סדר, ולהרגיש לרגע שלא סוחבים לבד.',
				),
			),
		),
		'how'    => array(
			'label'  => __( '02 · איך זה עובד', 'savta-al-hasafsal' ),
			'anchor' => 'how-it-works',
			'fields' => array(
				'how_enable' => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'how_number' => array(
					'type'    => 'text',
					'label'   => __( 'מספר המקטע', 'savta-al-hasafsal' ),
					'default' => '02',
				),
				'how_title'  => array(
					'type'    => 'text',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => 'איך זה עובד?',
				),
				'how_steps'  => array(
					'type'    => 'repeater',
					'label'   => __( 'השלבים', 'savta-al-hasafsal' ),
					'desc'    => __( 'המספור נקבע לפי הסדר.', 'savta-al-hasafsal' ),
					'max'     => 8,
					'fields'  => array(
						'title' => array(
							'type'  => 'text',
							'label' => __( 'כותרת השלב', 'savta-al-hasafsal' ),
						),
						'text'  => array(
							'type'  => 'textarea',
							'label' => __( 'הסבר', 'savta-al-hasafsal' ),
						),
					),
					'default' => array(
						array(
							'title' => 'משאירים פרטים',
							'text'  => 'ממלאים טופס קצר ודיסקרטי, כדי שנוכל להבין אם השיחה מתאימה ולחזור אלייך לתיאום.',
						),
						array(
							'title' => 'מתאמים זמן',
							'text'  => 'השיחות מתקיימות בתיאום מראש בלבד, כדי לשמור על פרטיות, זמינות ואווירה נעימה.',
						),
						array(
							'title' => 'יושבים לשיחה',
							'text'  => 'שיחה אישית של 40 דקות עד שעה עם "סבתא" מקשיבה — מקום לפרוק, לשתף, לנשום ולעשות סדר.',
						),
						array(
							'title' => 'יוצאים עם צעד קטן',
							'text'  => 'בסיום השיחה מנסים לזהות יחד נקודה אחת קטנה וברורה שיכולה לתת כוח להמשך.',
						),
					),
				),
			),
		),
		'who'    => array(
			'label'  => __( '03 · למי זה מתאים', 'savta-al-hasafsal' ),
			'anchor' => 'who',
			'fields' => array(
				'who_enable'    => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'who_number'    => array(
					'type'    => 'text',
					'label'   => __( 'מספר המקטע', 'savta-al-hasafsal' ),
					'default' => '03',
				),
				'who_title'     => array(
					'type'    => 'textarea',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => "למי השיחה\nיכולה להתאים?",
				),
				'who_lede'      => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקת הפתיחה', 'savta-al-hasafsal' ),
					'default' => 'למי שמרגישה צורך לעצור רגע, לשתף, לפרוק או לעשות סדר — בלי לחץ, בלי ביקורת ובלי שיפוטיות.',
				),
				'who_pill'      => array(
					'type'    => 'text',
					'label'   => __( 'התגית', 'savta-al-hasafsal' ),
					'default' => 'מגיל 20 ומעלה',
				),
				'who_image'     => array(
					'type'    => 'image',
					'label'   => __( 'תמונה', 'savta-al-hasafsal' ),
					'default' => 'tea-hands.webp',
				),
				'who_image_alt' => array(
					'type'    => 'text',
					'label'   => __( 'תיאור התמונה (נגישות)', 'savta-al-hasafsal' ),
					'default' => 'איור: שתי ידיים עם כוס תה',
				),
				'who_items'     => array(
					'type'    => 'repeater',
					'label'   => __( 'רשימת הסימונים', 'savta-al-hasafsal' ),
					'max'     => 12,
					'fields'  => array(
						'text' => array(
							'type'  => 'text',
							'label' => __( 'שורה', 'savta-al-hasafsal' ),
						),
					),
					'default' => array(
						array( 'text' => 'מרגישה שהיא סוחבת הרבה לבד' ),
						array( 'text' => 'צריכה אוזן קשבת אמיתית' ),
						array( 'text' => 'נמצאת בתקופה עמוסה או מבלבלת' ),
						array( 'text' => 'רוצה לדבר עם מישהי מנוסה, רגועה וטובת לב' ),
						array( 'text' => 'צריכה רגע של חיזוק, סדר ונשימה' ),
						array( 'text' => 'לא מחפשת טיפול, אלא שיחה אנושית פשוטה וטובה' ),
					),
				),
				'who_note'      => array(
					'type'    => 'textarea',
					'label'   => __( 'ההערה מתחת לרשימה', 'savta-al-hasafsal' ),
					'default' => 'המיזם פועל כעת בבני ברק, ובעזרת ה׳ יורחב בהמשך לערים נוספות. מספר המקומות מוגבל והשיחות יתקיימו בהדרגה ובאחריות.',
				),
			),
		),
		'efrat'  => array(
			'label'  => __( '04 · אפרת ברזל', 'savta-al-hasafsal' ),
			'anchor' => 'efrat',
			'fields' => array(
				'efrat_enable'    => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'efrat_number'    => array(
					'type'    => 'text',
					'label'   => __( 'מספר המקטע', 'savta-al-hasafsal' ),
					'default' => '04',
				),
				'efrat_title'     => array(
					'type'    => 'text',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => 'המיזם בהובלת אפרת ברזל',
				),
				'efrat_image'     => array(
					'type'    => 'image',
					'label'   => __( 'תמונה', 'savta-al-hasafsal' ),
					'default' => 'efrat.png',
				),
				'efrat_image_alt' => array(
					'type'    => 'text',
					'label'   => __( 'תיאור התמונה (נגישות)', 'savta-al-hasafsal' ),
					'default' => 'אפרת ברזל',
				),
				'efrat_p1'        => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה ראשונה', 'savta-al-hasafsal' ),
					'default' => 'המיזם "סבתא על הספסל" פועל בהובלתה של הגב\' אפרת ברזל — סופרת, מטפלת רגשית ואשת תקשורת ידועה.',
				),
				'efrat_p2'        => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה שנייה', 'savta-al-hasafsal' ),
					'default' => 'אפרת מובילה את המיזם מתוך רצון לתת מענה קהילתי רך, אחראי ואנושי לצורך הגדול שנוצר בשטח: צורך באוזן קשבת, בחיבור אנושי, במקום שבו אפשר לדבר בפשטות, בכבוד ובלב פתוח.',
				),
				'efrat_quote'     => array(
					'type'    => 'textarea',
					'label'   => __( 'הציטוט המודגש', 'savta-al-hasafsal' ),
					'default' => 'המטרה היא לבנות מרחב קהילתי שמחזיר למרכז את הדבר הכי בסיסי והכי חסר לפעמים: מישהי טובה שמקשיבה באמת.',
				),
			),
		),
		'first'  => array(
			'label'  => __( '05 · הסבתא הראשונה', 'savta-al-hasafsal' ),
			'anchor' => 'first-savta',
			'fields' => array(
				'first_enable'      => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'first_number'      => array(
					'type'    => 'text',
					'label'   => __( 'מספר המקטע', 'savta-al-hasafsal' ),
					'default' => '05',
				),
				'first_title'       => array(
					'type'    => 'text',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => '"הסבתא" הראשונה שלנו',
				),
				'first_image'       => array(
					'type'    => 'image',
					'label'   => __( 'תמונה', 'savta-al-hasafsal' ),
					'desc'    => __( 'עד שתועלה תמונה מוצג ממלא מקום מעוצב.', 'savta-al-hasafsal' ),
					'default' => '',
				),
				'first_image_alt'   => array(
					'type'    => 'text',
					'label'   => __( 'תיאור התמונה (נגישות)', 'savta-al-hasafsal' ),
					'default' => 'איור: סבתא יושבת על ספסל',
				),
				'first_placeholder' => array(
					'type'    => 'text',
					'label'   => __( 'טקסט ממלא המקום', 'savta-al-hasafsal' ),
					'default' => 'התמונה בדרך',
				),
				'first_p1'          => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה ראשונה', 'savta-al-hasafsal' ),
					'default' => '"הסבתא" הראשונה במיזם היא אשת חינוך ותיקה, עם למעלה מ־40 שנות ניסיון בחינוך, מתוכן 13 שנים כמנהלת בבית ספר ידוע.',
				),
				'first_p2'          => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה שנייה', 'savta-al-hasafsal' ),
					'default' => 'לאורך השנים היא ליוותה תלמידות, הורים וצוותים חינוכיים, מתוך חכמה, רגישות, אחריות והבנה עמוקה לאנשים.',
				),
				'first_p3'          => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה שלישית', 'savta-al-hasafsal' ),
					'default' => 'היום היא מביאה אל המיזם את ניסיון החיים, הלב הרחב והיכולת המיוחדת שלה להקשיב — כדי להיות שם עבור מי שצריכה שיחה טובה, רגועה ומחזקת.',
				),
				'first_quote'       => array(
					'type'    => 'textarea',
					'label'   => __( 'המשפט המודגש', 'savta-al-hasafsal' ),
					'default' => 'לא כמטפלת. לא כשופטת. אלא כסבתא מקשיבה — עם לב פתוח ונוכחות טובה.',
				),
			),
		),
		'notice' => array(
			'label'  => __( 'חשוב לדעת', 'savta-al-hasafsal' ),
			'anchor' => 'notice',
			'fields' => array(
				'notice_enable' => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'notice_title'  => array(
					'type'    => 'text',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => 'חשוב לדעת',
				),
				'notice_p1'     => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה ראשונה', 'savta-al-hasafsal' ),
					'default' => 'השיחה במסגרת "סבתא על הספסל" היא שיחת הקשבה קהילתית. היא אינה טיפול רגשי, אינה אבחון, ואינה מחליפה ייעוץ מקצועי או מענה חירום.',
				),
				'notice_p2'     => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה שנייה', 'savta-al-hasafsal' ),
					'default' => 'המטרה היא לתת מקום בטוח לשיחה, הקשבה, סדר וחיזוק ראשוני.',
				),
				'notice_p3'     => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקה שלישית', 'savta-al-hasafsal' ),
					'default' => 'במקרים שבהם נראה שיש צורך בעזרה מקצועית נוספת, נעודד פנייה לגורם מתאים.',
				),
			),
		),
		'quotes' => array(
			'label'  => __( '06 · משפטים', 'savta-al-hasafsal' ),
			'anchor' => 'quotes',
			'fields' => array(
				'quotes_enable' => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'quotes_number' => array(
					'type'    => 'text',
					'label'   => __( 'מספר המקטע', 'savta-al-hasafsal' ),
					'default' => '06',
				),
				'quotes_title'  => array(
					'type'    => 'textarea',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => "משפטים שסבתא\nאומרת על הספסל",
				),
				'quotes_lede'   => array(
					'type'    => 'textarea',
					'label'   => __( 'שורת הלוואי', 'savta-al-hasafsal' ),
					'default' => 'קחי אחד לדרך. אפשר גם להדפיס אותם ולתלות על המקרר.',
				),
				'quotes_items'  => array(
					'type'    => 'repeater',
					'label'   => __( 'המשפטים', 'savta-al-hasafsal' ),
					'max'     => 36,
					'fields'  => array(
						'text' => array(
							'type'  => 'text',
							'label' => __( 'משפט', 'savta-al-hasafsal' ),
						),
					),
					'default' => array(
						array( 'text' => 'גם יום קטן נחשב.' ),
						array( 'text' => 'את לא צריכה להחזיק הכל לבד.' ),
						array( 'text' => 'מותר לנוח באמצע.' ),
						array( 'text' => 'מה שעברת בנה אותך.' ),
						array( 'text' => 'לשאול זו לא חולשה.' ),
						array( 'text' => 'יש מי שמקשיב לך.' ),
						array( 'text' => 'צעד אחד קטן זה כבר כיוון.' ),
						array( 'text' => 'לא חייבים תשובה מיד.' ),
						array( 'text' => 'גם השקט הוא תשובה.' ),
						array( 'text' => 'את שווה את הזמן שלך.' ),
						array( 'text' => 'מחר יהיה יום אחר.' ),
						array( 'text' => 'להתחיל מחדש זה גם אומץ.' ),
						array( 'text' => 'לא כל דבר צריך פתרון. לפעמים מספיק שיקשיבו.' ),
						array( 'text' => 'הלב יודע קצב אחר מהלוח שנה.' ),
						array( 'text' => 'מה שמרגיש בלתי אפשרי היום, ייראה אחרת בעוד שבוע.' ),
						array( 'text' => 'אין בושה בלהיות עייפה.' ),
						array( 'text' => 'גם דלת סגורה מלמדת משהו.' ),
						array( 'text' => 'את בדרך, גם כשזה לא מרגיש כך.' ),
					),
				),
			),
		),
		'faq'    => array(
			'label'  => __( '07 · שאלות', 'savta-al-hasafsal' ),
			'anchor' => 'faq',
			'fields' => array(
				'faq_enable' => array(
					'type'    => 'checkbox',
					'label'   => __( 'הצגת המקטע', 'savta-al-hasafsal' ),
					'default' => 1,
				),
				'faq_number' => array(
					'type'    => 'text',
					'label'   => __( 'מספר המקטע', 'savta-al-hasafsal' ),
					'default' => '07',
				),
				'faq_title'  => array(
					'type'    => 'textarea',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => "שאלות\nשרצית לשאול",
				),
				'faq_lede'   => array(
					'type'    => 'textarea',
					'label'   => __( 'שורת הלוואי', 'savta-al-hasafsal' ),
					'default' => 'ואם נשארה שאלה שלא ענינו עליה — אפשר להשאיר פרטים ונחזור אלייך.',
				),
				'faq_items'  => array(
					'type'    => 'repeater',
					'label'   => __( 'השאלות והתשובות', 'savta-al-hasafsal' ),
					'max'     => 20,
					'fields'  => array(
						'question' => array(
							'type'  => 'text',
							'label' => __( 'שאלה', 'savta-al-hasafsal' ),
						),
						'answer'   => array(
							'type'  => 'textarea',
							'label' => __( 'תשובה', 'savta-al-hasafsal' ),
						),
					),
					'default' => array(
						array(
							'question' => 'האם השיחה עולה כסף?',
							'answer'   => 'לא. השיחה ניתנת תמיד ללא עלות — מדובר בעמותה ללא מטרות רווח, הפועלת למען הקהילה.',
						),
						array(
							'question' => 'האם אפשר להגיע בלי תיאום מראש?',
							'answer'   => 'לא. השיחות מתקיימות תמיד בתיאום מראש בלבד, כדי לשמור על פרטיות, זמינות והתאמה נכונה.',
						),
						array(
							'question' => 'כמה זמן נמשכת שיחה?',
							'answer'   => 'כל שיחה נמשכת בין 40 דקות לשעה.',
						),
						array(
							'question' => 'היכן מתקיימת השיחה?',
							'answer'   => 'השיחות מתקיימות במקומות קהילתיים שנבחרו בקפידה: חדר פרטי ושקט או משרד נעים, בסביבה אסתטית ומזמינה, השומרת על פרטיות מלאה ועל תחושת נינוחות. המיזם פועל כעת בבני ברק, ובעזרת ה׳ יורחב בהמשך לערים נוספות.',
						),
						array(
							'question' => 'האם זו שיחה טיפולית?',
							'answer'   => 'לא. זו שיחת הקשבה ותמיכה קהילתית. היא אינה מחליפה טיפול מקצועי, אבל יכולה לתת מקום לפרוק, לעשות סדר ולקבל חיזוק.',
						),
						array(
							'question' => 'האם השיחה דיסקרטית?',
							'answer'   => 'כן. השיחה מתקיימת באווירה מכבדת ודיסקרטית, תוך שמירה על פרטיות המשתתפות, למעט מצבים חריגים שבהם נדרש לפנות לעזרה מתאימה.',
						),
						array(
							'question' => 'למי מתאים להירשם?',
							'answer'   => 'למי שמרגישה צורך בשיחה אישית, באוזן קשבת, בסדר פנימי ובחיזוק אנושי פשוט.',
						),
					),
				),
			),
		),
		'signup' => array(
			'label'  => __( '08 · תיאום פגישה', 'savta-al-hasafsal' ),
			'anchor' => 'signup',
			'fields' => array(
				'signup_number'      => array(
					'type'    => 'text',
					'label'   => __( 'מספר המקטע', 'savta-al-hasafsal' ),
					'default' => '08',
				),
				'signup_title'       => array(
					'type'    => 'textarea',
					'label'   => __( 'כותרת', 'savta-al-hasafsal' ),
					'default' => "רוצה לתאם\nפגישה עם הסבתא?",
				),
				'signup_lede'        => array(
					'type'    => 'textarea',
					'label'   => __( 'פסקת הפתיחה', 'savta-al-hasafsal' ),
					'default' => 'אנחנו פותחים את השיחות הראשונות בהדרגה ובאחריות. אפשר להשאיר פרטים, ונחזור אלייך לתיאום שיחה או להסבר נוסף.',
				),
				'signup_note'        => array(
					'type'    => 'text',
					'label'   => __( 'שורת ההדגשה', 'savta-al-hasafsal' ),
					'default' => 'מספר המקומות בשלב הראשון מוגבל.',
				),
				'signup_image'       => array(
					'type'    => 'image',
					'label'   => __( 'תמונה', 'savta-al-hasafsal' ),
					'default' => 'empty-bench.webp',
				),
				'signup_image_alt'   => array(
					'type'    => 'text',
					'label'   => __( 'תיאור התמונה (נגישות)', 'savta-al-hasafsal' ),
					'default' => 'איור: ספסל ריק ממתין בגן',
				),
				'form_name'          => array(
					'type'    => 'text',
					'label'   => __( 'תווית: שם פרטי', 'savta-al-hasafsal' ),
					'default' => 'שם פרטי',
				),
				'form_phone'         => array(
					'type'    => 'text',
					'label'   => __( 'תווית: טלפון', 'savta-al-hasafsal' ),
					'default' => 'טלפון',
				),
				'form_city'          => array(
					'type'    => 'text',
					'label'   => __( 'תווית: עיר', 'savta-al-hasafsal' ),
					'default' => 'עיר',
				),
				'form_age'           => array(
					'type'    => 'text',
					'label'   => __( 'תווית: גיל', 'savta-al-hasafsal' ),
					'default' => 'גיל',
				),
				'form_contact_label' => array(
					'type'    => 'text',
					'label'   => __( 'שאלת דרך הקשר', 'savta-al-hasafsal' ),
					'default' => 'איך נוח ליצור קשר?',
				),
				'form_contact_phone' => array(
					'type'    => 'text',
					'label'   => __( 'אפשרות: טלפון', 'savta-al-hasafsal' ),
					'default' => 'טלפון',
				),
				'form_contact_wa'    => array(
					'type'    => 'text',
					'label'   => __( 'אפשרות: וואטסאפ', 'savta-al-hasafsal' ),
					'default' => 'וואטסאפ',
				),
				'form_when'          => array(
					'type'    => 'text',
					'label'   => __( 'שאלת המועד', 'savta-al-hasafsal' ),
					'default' => 'מתי נוח לך?',
				),
				'form_slot_empty'    => array(
					'type'    => 'text',
					'label'   => __( 'טקסט בורר המועד לפני בחירה', 'savta-al-hasafsal' ),
					'default' => 'לבדיקת ימים פנויים',
				),
				'form_cal_open'      => array(
					'type'    => 'text',
					'label'   => __( 'טקסט פתיחת היומן', 'savta-al-hasafsal' ),
					'default' => 'לצפייה ביומן',
				),
				'form_cal_close'     => array(
					'type'    => 'text',
					'label'   => __( 'טקסט סגירת היומן', 'savta-al-hasafsal' ),
					'default' => 'סגירה',
				),
				'form_cal_intro'     => array(
					'type'    => 'textarea',
					'label'   => __( 'הסבר בתוך היומן', 'savta-al-hasafsal' ),
					'default' => 'השיחות מתקיימות בימי שני ושלישי בערב, בין 20:00 ל־22:30. בחרי מועד ונאשר אותו בשיחה.',
				),
				'form_cal_none'      => array(
					'type'    => 'text',
					'label'   => __( 'הערה בתחתית היומן', 'savta-al-hasafsal' ),
					'default' => 'לא מצאת מועד מתאים? אפשר לכתוב לנו בשדה למטה ונמצא זמן אחר.',
				),
				'cal_days'           => array(
					'type'    => 'text',
					'label'   => __( 'ימי הפעילות', 'savta-al-hasafsal' ),
					'desc'    => __( 'מספרי ימים מופרדים בפסיק: 0 = ראשון, 1 = שני ... 6 = שבת.', 'savta-al-hasafsal' ),
					'default' => '1,2',
				),
				'cal_slots'          => array(
					'type'    => 'textarea',
					'label'   => __( 'משבצות הזמן', 'savta-al-hasafsal' ),
					'desc'    => __( 'משבצת אחת בכל שורה.', 'savta-al-hasafsal' ),
					'default' => "20:00–21:00\n21:10–22:10",
				),
				'cal_count'          => array(
					'type'    => 'text',
					'label'   => __( 'כמה ימים להציג', 'savta-al-hasafsal' ),
					'default' => '4',
				),
				'form_topic'         => array(
					'type'    => 'text',
					'label'   => __( 'תווית: נושא השיחה', 'savta-al-hasafsal' ),
					'default' => 'בכמה מילים: על מה היית רוצה לשוחח?',
				),
				'form_consent'       => array(
					'type'    => 'textarea',
					'label'   => __( 'תיבת האישור', 'savta-al-hasafsal' ),
					'default' => 'ידוע לי שמדובר בשיחת הקשבה קהילתית שאינה מחליפה טיפול מקצועי.',
				),
				'form_submit'        => array(
					'type'    => 'text',
					'label'   => __( 'טקסט כפתור השליחה', 'savta-al-hasafsal' ),
					'default' => 'אני רוצה שיחזרו אליי',
				),
				'thanks_title'       => array(
					'type'    => 'text',
					'label'   => __( 'כותרת התודה', 'savta-al-hasafsal' ),
					'default' => 'תודה, הפרטים התקבלו.',
				),
				'thanks_text'        => array(
					'type'    => 'text',
					'label'   => __( 'טקסט התודה', 'savta-al-hasafsal' ),
					'default' => 'נחזור אלייך בהקדם לתיאום או להסבר נוסף.',
				),
			),
		),
	);

	return $groups;
}

/**
 * All fields keyed by name, without the group nesting.
 *
 * @return array<string,array<string,mixed>>
 */
function savta_fields_flat(): array {
	static $flat = null;

	if ( null === $flat ) {
		$flat = array();

		foreach ( savta_field_groups() as $group ) {
			$flat += $group['fields'];
		}
	}

	return $flat;
}
