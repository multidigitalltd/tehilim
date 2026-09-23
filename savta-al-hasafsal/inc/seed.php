<?php
/**
 * First-run setup: creates the homepage and writes the design's copy into it.
 *
 * Runs once, on activation, and never overwrites content that already exists.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

const SAVTA_SEED_OPTION = 'savta_seeded';

/**
 * Writes every schema default into a page's meta, so the dashboard shows
 * exactly what the site shows. Existing values are left alone.
 *
 * @param int $page_id Page to fill.
 * @return void
 */
function savta_seed_page_content( int $page_id ): void {
	foreach ( savta_fields_flat() as $name => $definition ) {
		$key = SAVTA_META_PREFIX . $name;

		if ( metadata_exists( 'post', $page_id, $key ) ) {
			continue;
		}

		update_post_meta( $page_id, $key, $definition['default'] ?? '' );
	}
}

/**
 * Finds or creates a page by slug.
 *
 * @param string $slug    Page slug.
 * @param string $title   Page title.
 * @param string $content Initial block content.
 * @return int Page ID, or 0 on failure.
 */
function savta_seed_page( string $slug, string $title, string $content = '' ): int {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $existing instanceof WP_Post ) {
		return (int) $existing->ID;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		),
		true
	);

	return is_wp_error( $page_id ) ? 0 : (int) $page_id;
}

/**
 * Builds the primary menu from the section anchors, unless one is assigned.
 *
 * @return void
 */
function savta_seed_menu(): void {
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );

	if ( ! empty( $locations['primary'] ) ) {
		return;
	}

	$menu_id = wp_create_nav_menu( __( 'תפריט ראשי', 'savta-al-hasafsal' ) );

	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	$home = home_url( '/' );

	foreach ( savta_default_nav() as $anchor => $label ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $label,
				'menu-item-url'    => $home . $anchor,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			)
		);
	}

	$locations['primary'] = (int) $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * The initial content of the accessibility statement page.
 *
 * @return string
 */
function savta_seed_a11y_statement(): string {
	$site = get_bloginfo( 'name', 'display' );

	return implode(
		"\n\n",
		array(
			'<!-- wp:paragraph --><p>' . esc_html( sprintf( /* translators: %s: site name. */ __( 'האתר "%s" נבנה מתוך מחויבות להנגשה מלאה של תכניו לאנשים עם מוגבלות, בהתאם לתקן הישראלי ת"י 5568 ולהנחיות WCAG 2.2 ברמה AA.', 'savta-al-hasafsal' ), $site ) ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'מה נעשה באתר', 'savta-al-hasafsal' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:list --><ul><li>' . esc_html__( 'ניווט מלא במקלדת וסימון מיקוד ברור.', 'savta-al-hasafsal' ) . '</li><li>' . esc_html__( 'סרגל נגישות: הגדלת טקסט, ניגודיות, עצירת אנימציות ועוד.', 'savta-al-hasafsal' ) . '</li><li>' . esc_html__( 'טקסט חלופי לתמונות, מבנה כותרות תקין וניגודיות צבעים תקנית.', 'savta-al-hasafsal' ) . '</li><li>' . esc_html__( 'כיבוד העדפת "הפחתת תנועה" של מערכת ההפעלה.', 'savta-al-hasafsal' ) . '</li></ul><!-- /wp:list -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'פנייה בנושא נגישות', 'savta-al-hasafsal' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'נתקלתם בבעיית נגישות? נשמח לשמוע ולתקן. [יש להשלים: שם רכז/ת הנגישות, טלפון ואימייל]', 'savta-al-hasafsal' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'תאריך עדכון ההצהרה: [יש להשלים]', 'savta-al-hasafsal' ) . '</p><!-- /wp:paragraph -->',
		)
	);
}

/**
 * The initial content of the privacy page.
 *
 * @return string
 */
function savta_seed_privacy(): string {
	return implode(
		"\n\n",
		array(
			'<!-- wp:paragraph --><p>' . esc_html__( 'הפרטים שנמסרים בטופס התיאום (שם, טלפון, עיר, גיל, דרך קשר מועדפת, מועד מבוקש ונושא השיחה) משמשים אך ורק לחזרה אל הפונה ולתיאום השיחה. הם נשמרים במערכת האתר ואינם מועברים לצד שלישי.', 'savta-al-hasafsal' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'השיחות עצמן דיסקרטיות. במצבים חריגים שבהם נדרש לפנות לעזרה מתאימה, ייתכן שנפנה לגורם מקצועי, כמפורט באתר.', 'savta-al-hasafsal' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'לבקשת עיון, תיקון או מחיקה של הפרטים אפשר לפנות אלינו. [יש להשלים: פרטי קשר]', 'savta-al-hasafsal' ) . '</p><!-- /wp:paragraph -->',
		)
	);
}

/**
 * Runs the one-time setup.
 *
 * @return void
 */
function savta_seed(): void {
	if ( get_option( SAVTA_SEED_OPTION ) ) {
		return;
	}

	$home_id = (int) get_option( 'page_on_front' );
	$home    = get_post( $home_id );

	if ( ! $home instanceof WP_Post || 'trash' === $home->post_status ) {
		$home_id = savta_seed_page( 'home', __( 'דף הבית', 'savta-al-hasafsal' ) );
	}

	if ( $home_id > 0 ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
	}

	if ( $home_id > 0 ) {
		savta_seed_page_content( $home_id );
	}

	$a11y_id = savta_seed_page( 'accessibility', __( 'הצהרת נגישות', 'savta-al-hasafsal' ), savta_seed_a11y_statement() );
	if ( $a11y_id > 0 && (int) savta_option( 'savta_a11y_page' ) <= 0 ) {
		set_theme_mod( 'savta_a11y_page', $a11y_id );
	}

	$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $privacy_id <= 0 ) {
		$privacy_id = savta_seed_page( 'privacy', __( 'מדיניות פרטיות', 'savta-al-hasafsal' ), savta_seed_privacy() );
		if ( $privacy_id > 0 ) {
			update_option( 'wp_page_for_privacy_policy', $privacy_id );
		}
	}
	if ( $privacy_id > 0 && (int) savta_option( 'savta_privacy_page' ) <= 0 ) {
		set_theme_mod( 'savta_privacy_page', $privacy_id );
	}

	savta_seed_menu();

	update_option( SAVTA_SEED_OPTION, SAVTA_VERSION );
}
add_action( 'after_switch_theme', 'savta_seed' );

/**
 * Runs the setup on the first dashboard visit if activation did not, and
 * fills in fields added in a later theme version on existing installs.
 *
 * The after_switch_theme hook fires on the request after activation; a site where
 * that request never reached the theme (a preview, a CLI switch, a cached
 * admin) would otherwise stay on the posts index with no homepage.
 * Only fields with no stored value are touched, so edits are never lost.
 *
 * @return void
 */
function savta_seed_upgrade(): void {
	if ( ! is_admin() || wp_doing_ajax() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$seeded = get_option( SAVTA_SEED_OPTION );

	if ( ! $seeded ) {
		savta_seed();
		return;
	}

	if ( SAVTA_VERSION === $seeded ) {
		return;
	}

	$home_id = (int) get_option( 'page_on_front' );

	if ( $home_id > 0 ) {
		savta_seed_page_content( $home_id );
	}

	update_option( SAVTA_SEED_OPTION, SAVTA_VERSION );
}
add_action( 'admin_init', 'savta_seed_upgrade' );
