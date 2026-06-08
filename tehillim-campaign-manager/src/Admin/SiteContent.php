<?php
/**
 * Designed page content (block markup) for the one-click site builder.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the full, ready-made block layouts that the site builder writes
 * straight into the pages - so the site looks like the design preview out of
 * the box, and every section is editable in the block editor (no manual design
 * work). The markup uses the companion theme's section classes for styling.
 */
final class SiteContent {

	/**
	 * Design revision. Bump when the markup changes so the builder can refresh
	 * pages that still hold an older auto-generated layout.
	 */
	const REVISION = '2';

	/**
	 * Block markup for a page key, or an empty string when none is defined.
	 *
	 * @param string $key Page key (home, segulot, create, my, activity, ambassadors, subscribe).
	 * @return string
	 */
	public static function for_key( $key ) {
		switch ( $key ) {
			case 'home':
				return self::home();
			case 'segulot':
				return self::section(
					'סגולות ותפילות',
					'אוסף תפילות וסגולות מיוחדות לכל עת - לרפואה, לפרנסה, לישועה ולשמירה.',
					'<!-- wp:tehillim/segulot /-->'
				);
			case 'create':
				return self::section(
					'פתיחת קמפיין',
					'פִתחו קמפיין תהילים בשתי דקות, בחרו מטרה ויעד, והזמינו את הקהילה.',
					'<!-- wp:tehillim/create-campaign /-->'
				);
			case 'my':
				return self::section(
					'האזור האישי שלי',
					'הקמפיינים שפתחתם, ההתקדמות שלהם והניהול שלהם - במקום אחד.',
					'<!-- wp:tehillim/my-campaigns /-->'
				);
			case 'activity':
				return self::section(
					'הפעילות שלי',
					'הפרקים שלקחתם והשלמתם בכל הקמפיינים.',
					'<!-- wp:tehillim/my-activity /-->'
				);
			case 'ambassadors':
				return self::section(
					'שגרירים',
					'גייסו את הקהילה שלכם, קבלו לינק אישי וצפו בהתקדמות שלכם בלוח השגרירים.',
					'<!-- wp:tehillim/ambassadors /-->'
				);
			case 'subscribe':
				return self::section(
					'תהילים יומי',
					'הירשמו לקבלת פרק תהילים יומי ותזכורות חכמות ישירות לוואטסאפ.',
					'<!-- wp:tehillim/subscribe /-->'
				);
		}
		return '';
	}

	/**
	 * A simple, centered designed section: heading + intro + a dynamic block.
	 *
	 * @param string $title Section heading.
	 * @param string $intro Intro paragraph.
	 * @param string $block Block markup to embed.
	 * @return string
	 */
	private static function section( $title, $intro, $block ) {
		return '<!-- wp:group {"tagName":"section","className":"tc-section","layout":{"type":"constrained"}} -->' . "\n"
			. '<section class="wp-block-group tc-section">' . "\n"
			. '<!-- wp:heading {"textAlign":"center"} -->' . "\n"
			. '<h2 class="wp-block-heading has-text-align-center">' . esc_html( $title ) . '</h2>' . "\n"
			. '<!-- /wp:heading -->' . "\n\n"
			. '<!-- wp:paragraph {"align":"center","className":"tc-muted"} -->' . "\n"
			. '<p class="has-text-align-center tc-muted">' . esc_html( $intro ) . '</p>' . "\n"
			. '<!-- /wp:paragraph -->' . "\n\n"
			. $block . "\n"
			. '</section>' . "\n"
			. '<!-- /wp:group -->';
	}

	/**
	 * The full homepage layout (hero, live stats, how-it-works, campaigns,
	 * features, testimonials, FAQ, CTA) - the Lovable design as editable blocks.
	 *
	 * @return string
	 */
	private static function home() {
		return <<<'HTML'
<!-- wp:group {"tagName":"section","className":"tc-hero","layout":{"type":"constrained"}} -->
<section class="wp-block-group tc-hero">
	<!-- wp:paragraph {"align":"center","className":"tc-eyebrow"} -->
	<p class="has-text-align-center tc-eyebrow">✦ פלטפורמת קמפיינים קהילתיים - לא אתר תהילים</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","level":1,"className":"tc-hero-title"} -->
	<h1 class="wp-block-heading has-text-align-center tc-hero-title">מאחדים אנשים סביב יעד תהילים משותף</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","className":"tc-hero-sub"} -->
	<p class="has-text-align-center tc-hero-sub">צרו קמפיין תהילים, הזמינו משפחה וחברים, עקבו אחרי ההתקדמות בזמן אמת והשלימו יחד אלפי פרקים - לרפואה, לעילוי נשמה, לישועה או לכל מטרה יקרה ללב.</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"is-style-fill"} -->
		<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="/create-campaign">צרו קמפיין</a></div>
		<!-- /wp:button -->
		<!-- wp:button {"className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/campaigns">גלו קמפיינים</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->

<!-- wp:tehillim/global-stats /-->

<!-- wp:group {"tagName":"section","className":"tc-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group tc-section">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center">איך זה עובד</h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"align":"center","className":"tc-muted"} -->
	<p class="has-text-align-center tc-muted">שלוש פעולות פשוטות. כל השאר מתגלגל מעצמו.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"tc-grid tc-grid-3","layout":{"type":"default"}} -->
	<div class="wp-block-group tc-grid tc-grid-3">
		<!-- wp:group {"className":"tc-step","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-step"><p class="tc-step-num">01</p><h3>פותחים קמפיין</h3><p class="tc-muted">שם, מטרה, יעד של פרקים או ספרים - שתי דקות והקמפיין באוויר.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-step","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-step"><p class="tc-step-num">02</p><h3>מזמינים שגרירים</h3><p class="tc-muted">כל שגריר מקבל לינק אישי ומגייס את הקהילה שלו לעמידה ביעד.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-step","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-step"><p class="tc-step-num">03</p><h3>עוקבים בזמן אמת</h3><p class="tc-muted">מד התקדמות חי, פיד פעילות, ולוח שגרירים שמדרבן כולם.</p></div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"tc-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group tc-section">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center">קמפיינים אחרונים</h2>
	<!-- /wp:heading -->
	<!-- wp:tehillim/campaigns /-->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"tc-section tc-section--alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group tc-section tc-section--alt">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center">כל מה שצריך לקמפיין מנצח</h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"tc-grid tc-grid-4","layout":{"type":"default"}} -->
	<div class="wp-block-group tc-grid tc-grid-4">
		<!-- wp:group {"className":"tc-feature","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-feature"><h3>יצירת קמפיין במגוון מטרות</h3><p class="tc-muted">רפואה, ישועה, זיווג, הצלחה, לעילוי נשמה ועוד.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-feature","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-feature"><h3>מערכת שגרירים</h3><p class="tc-muted">כל שגריר עם לינק ייעודי ולוח התקדמות אישי.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-feature","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-feature"><h3>התקדמות בזמן אמת</h3><p class="tc-muted">מד גדול, ספירת פרקים וספרים מתעדכנת מיד.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-feature","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-feature"><h3>שיתוף בוואטסאפ</h3><p class="tc-muted">כפתור אחד והקמפיין מגיע לכל קבוצה.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-feature","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-feature"><h3>מעקב אישי</h3><p class="tc-muted">כל משתתף רואה את התרומה שלו ליעד.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-feature","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-feature"><h3>השתתפות קבוצתית</h3><p class="tc-muted">משפחות, כיתות, קהילות - כולם תורמים יחד.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-feature","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-feature"><h3>תגי הישג</h3><p class="tc-muted">ספר ראשון, 10 ספרים, שגריר מצטיין ועוד.</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-feature","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-feature"><h3>סטטיסטיקות חיות</h3><p class="tc-muted">מי תרם, מתי, וכמה - הכל שקוף.</p></div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"tc-section","layout":{"type":"constrained"}} -->
<section class="wp-block-group tc-section">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center">מה אומרים מי שכבר הריצו קמפיין</h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"tc-grid tc-grid-3","layout":{"type":"default"}} -->
	<div class="wp-block-group tc-grid tc-grid-3">
		<!-- wp:group {"className":"tc-quote","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-quote"><p class="tc-quote-text">"תוך יומיים סיימנו ספר תהילים שלם לרפואת אבא. אנשים שלא דיברתי איתם שנים הצטרפו."</p><p class="tc-quote-by">- מרים, חיפה</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-quote","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-quote"><p class="tc-quote-text">"מד ההתקדמות החי פשוט מכר את עצמו. כל אחד רצה להיות זה שמשלים עוד פרק."</p><p class="tc-quote-by">- יוסי, בני ברק</p></div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tc-quote","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tc-quote"><p class="tc-quote-text">"מערכת השגרירים עשתה את העבודה - כל אחד גייס את הקבוצה שלו בוואטסאפ."</p><p class="tc-quote-by">- חנה, ירושלים</p></div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"tc-section tc-section--alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group tc-section tc-section--alt">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center">שאלות נפוצות</h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"tc-faq","layout":{"type":"constrained"}} -->
	<div class="wp-block-group tc-faq">
		<!-- wp:details {"className":"tc-faq-item"} -->
		<details class="wp-block-details tc-faq-item"><summary>האם צריך להירשם כדי להצטרף לקמפיין?</summary><!-- wp:paragraph --><p>לא. כל אחד יכול לבחור פרק ולומר אותו. הרשמה נדרשת רק כדי לפתוח קמפיין משלך או לקבל תזכורות.</p><!-- /wp:paragraph --></details>
		<!-- /wp:details -->
		<!-- wp:details {"className":"tc-faq-item"} -->
		<details class="wp-block-details tc-faq-item"><summary>איך מקבלים תזכורות?</summary><!-- wp:paragraph --><p>בעת ההצטרפות לתהילים יומי נשלחת תזכורת חכמה עם קישור ישיר לפרק שלך - מתאים לשליחה אוטומטית לוואטסאפ.</p><!-- /wp:paragraph --></details>
		<!-- /wp:details -->
		<!-- wp:details {"className":"tc-faq-item"} -->
		<details class="wp-block-details tc-faq-item"><summary>למה אפשר להקדיש קמפיין?</summary><!-- wp:paragraph --><p>לרפואה, לישועה, לזיווג, להצלחה, לעילוי נשמה - או לכל מטרה יקרה ללב.</p><!-- /wp:paragraph --></details>
		<!-- /wp:details -->
		<!-- wp:details {"className":"tc-faq-item"} -->
		<details class="wp-block-details tc-faq-item"><summary>האם השימוש חינמי?</summary><!-- wp:paragraph --><p>כן. פתיחת קמפיין והשתתפות בו חינמיים לחלוטין.</p><!-- /wp:paragraph --></details>
		<!-- /wp:details -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"tc-cta","layout":{"type":"constrained"}} -->
<section class="wp-block-group tc-cta">
	<!-- wp:heading {"textAlign":"center","level":2} -->
	<h2 class="wp-block-heading has-text-align-center">מוכנים לפתוח קמפיין?</h2>
	<!-- /wp:heading -->
	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/create-campaign">צרו קמפיין עכשיו</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
HTML;
	}
}
