<?php
/**
 * The theme's own admin screen: where the content lives, and a way to create
 * or restore the homepage when it is missing.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

const SAVTA_DASHBOARD_SCREEN = 'savta-dashboard';
const SAVTA_SETUP_ACTION     = 'savta_setup_site';

/**
 * Registers the screen under Appearance.
 *
 * @return void
 */
function savta_register_dashboard(): void {
	add_theme_page(
		__( 'סבתא על הספסל', 'savta-al-hasafsal' ),
		__( 'סבתא על הספסל', 'savta-al-hasafsal' ),
		'edit_theme_options',
		SAVTA_DASHBOARD_SCREEN,
		'savta_render_dashboard'
	);
}
add_action( 'admin_menu', 'savta_register_dashboard' );

/**
 * The homepage that carries the sections, when one is set and exists.
 *
 * @return WP_Post|null
 */
function savta_home_page(): ?WP_Post {
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		return null;
	}

	$page = get_post( (int) get_option( 'page_on_front' ) );

	return $page instanceof WP_Post && 'trash' !== $page->post_status ? $page : null;
}

/**
 * Handles the "create / restore the homepage" button.
 *
 * @return void
 */
function savta_handle_setup(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'אין לך הרשאה לפעולה זו.', 'savta-al-hasafsal' ) );
	}

	check_admin_referer( SAVTA_SETUP_ACTION );

	// Run the first-run setup again: it creates only what is missing.
	delete_option( SAVTA_SEED_OPTION );
	savta_seed();

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'       => SAVTA_DASHBOARD_SCREEN,
				'savta_done' => '1',
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_post_' . SAVTA_SETUP_ACTION, 'savta_handle_setup' );

/**
 * Renders the screen.
 *
 * @return void
 */
function savta_render_dashboard(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$home      = savta_home_page();
	$a11y_id   = (int) savta_option( 'savta_a11y_page' );
	$privacy   = (int) savta_option( 'savta_privacy_page' );
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag after a redirect.
	$done = isset( $_GET['savta_done'] );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'סבתא על הספסל', 'savta-al-hasafsal' ); ?></h1>

		<?php if ( $done ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'עמוד הבית והתוכן מוכנים.', 'savta-al-hasafsal' ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'עמוד הבית', 'savta-al-hasafsal' ); ?></h2>
		<?php if ( $home ) : ?>
			<p>
				<?php
				printf(
					/* translators: %s: page title. */
					esc_html__( 'כל תוכן האתר נערך בתוך העמוד "%s" — מקטע אחד לכל תיבה מתחת לאזור העריכה.', 'savta-al-hasafsal' ),
					esc_html( get_the_title( $home ) )
				);
				?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( (string) get_edit_post_link( $home->ID, 'raw' ) ); ?>"><?php esc_html_e( 'עריכת התוכן', 'savta-al-hasafsal' ); ?></a>
				<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'צפייה באתר', 'savta-al-hasafsal' ); ?></a>
			</p>
		<?php else : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'לא מוגדר עמוד בית, ולכן האתר מציג את רשימת הפוסטים במקום העיצוב. הכפתור למטה יוצר את עמוד הבית עם כל התוכן ומגדיר אותו כעמוד הראשי.', 'savta-al-hasafsal' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( SAVTA_SETUP_ACTION ); ?>" />
			<?php wp_nonce_field( SAVTA_SETUP_ACTION ); ?>
			<p>
				<button type="submit" class="button <?php echo $home ? '' : 'button-primary'; ?>"><?php echo $home ? esc_html__( 'השלמת תוכן חסר', 'savta-al-hasafsal' ) : esc_html__( 'יצירת עמוד הבית עם התוכן', 'savta-al-hasafsal' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'יוצר רק מה שחסר: עמוד הבית, עמודי הנגישות והפרטיות, התפריט, ושדות תוכן שעדיין לא נשמרו. תוכן שכבר נערך לא נדרס.', 'savta-al-hasafsal' ); ?></p>
		</form>

		<h2><?php esc_html_e( 'עמודים נלווים', 'savta-al-hasafsal' ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'הצהרת נגישות:', 'savta-al-hasafsal' ); ?> <?php echo $a11y_id > 0 && get_post( $a11y_id ) ? '<a href="' . esc_url( (string) get_edit_post_link( $a11y_id, 'raw' ) ) . '">' . esc_html( get_the_title( $a11y_id ) ) . '</a>' : esc_html__( 'לא הוגדר', 'savta-al-hasafsal' ); ?></li>
			<li><?php esc_html_e( 'מדיניות פרטיות:', 'savta-al-hasafsal' ); ?> <?php echo $privacy > 0 && get_post( $privacy ) ? '<a href="' . esc_url( (string) get_edit_post_link( $privacy, 'raw' ) ) . '">' . esc_html( get_the_title( $privacy ) ) . '</a>' : esc_html__( 'לא הוגדר', 'savta-al-hasafsal' ); ?></li>
			<li><?php esc_html_e( 'תפריט ראשי:', 'savta-al-hasafsal' ); ?> <?php echo ! empty( $locations['primary'] ) ? esc_html__( 'מוגדר', 'savta-al-hasafsal' ) : esc_html__( 'לא מוגדר — מוצגים עוגני העיצוב', 'savta-al-hasafsal' ); ?> · <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'תפריטים', 'savta-al-hasafsal' ); ?></a></li>
			<li><?php esc_html_e( 'הגדרות כלל-אתריות:', 'savta-al-hasafsal' ); ?> <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=savta_panel' ) ); ?>"><?php esc_html_e( 'התאמה אישית', 'savta-al-hasafsal' ); ?></a></li>
			<li><?php esc_html_e( 'פניות מהטופס:', 'savta-al-hasafsal' ); ?> <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . SAVTA_LEAD_TYPE ) ); ?>"><?php esc_html_e( 'פניות', 'savta-al-hasafsal' ); ?></a></li>
		</ul>
	</div>
	<?php
}

/**
 * Points the way when the theme is active but no homepage is set.
 *
 * @return void
 */
function savta_setup_notice(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || savta_home_page() ) {
		return;
	}

	$screen = get_current_screen();

	if ( $screen && 'appearance_page_' . SAVTA_DASHBOARD_SCREEN === $screen->id ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
		esc_html__( 'סבתא על הספסל: עמוד הבית עדיין לא נוצר, ולכן העיצוב לא מוצג באתר.', 'savta-al-hasafsal' ),
		esc_url( admin_url( 'themes.php?page=' . SAVTA_DASHBOARD_SCREEN ) ),
		esc_html__( 'ליצירת עמוד הבית', 'savta-al-hasafsal' )
	);
}
add_action( 'admin_notices', 'savta_setup_notice' );
