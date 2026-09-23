<?php
/**
 * Accessibility toolbar (ת"י 5568 / WCAG 2.2 AA) and the skip link.
 *
 * The markup renders server-side so it exists before JavaScript runs; the
 * script only toggles classes on <html> and remembers the choice.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

/**
 * The toggles the toolbar offers.
 *
 * @return array<int,array{key:string,label:string,type:string}>
 */
function savta_a11y_controls(): array {
	return array(
		array(
			'key'   => 'text-bigger',
			'label' => __( 'הגדלת טקסט', 'savta-al-hasafsal' ),
			'type'  => 'step',
		),
		array(
			'key'   => 'text-smaller',
			'label' => __( 'הקטנת טקסט', 'savta-al-hasafsal' ),
			'type'  => 'step',
		),
		array(
			'key'   => 'contrast',
			'label' => __( 'ניגודיות גבוהה', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'invert',
			'label' => __( 'היפוך צבעים', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'grayscale',
			'label' => __( 'גווני אפור', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'links',
			'label' => __( 'הדגשת קישורים', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'headings',
			'label' => __( 'הדגשת כותרות', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'readable',
			'label' => __( 'גופן קריא', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'spacing',
			'label' => __( 'ריווח טקסט', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'motion',
			'label' => __( 'עצירת אנימציות', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'guide',
			'label' => __( 'סרגל קריאה', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
		array(
			'key'   => 'focus',
			'label' => __( 'הדגשת מיקוד מקלדת', 'savta-al-hasafsal' ),
			'type'  => 'toggle',
		),
	);
}

/**
 * Renders the toolbar.
 *
 * @return void
 */
function savta_the_a11y_toolbar(): void {
	if ( ! savta_option( 'savta_a11y_enable' ) ) {
		return;
	}

	$statement_id  = (int) savta_option( 'savta_a11y_page' );
	$statement_url = $statement_id > 0 ? get_permalink( $statement_id ) : '';
	?>
	<div class="sv-a11y" data-sv-a11y>
		<button type="button" class="sv-a11y__trigger" data-sv-a11y-trigger aria-expanded="false" aria-controls="sv-a11y-panel">
			<svg class="sv-a11y__icon" viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" focusable="false">
				<circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="1.6" />
				<circle cx="12" cy="6.4" r="1.6" fill="currentColor" />
				<path d="M5.5 9.4h13M12 9.4v5m0 0-2.6 5.2M12 14.4l2.6 5.2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
			</svg>
			<span class="sv-sr-only"><?php esc_html_e( 'תפריט נגישות', 'savta-al-hasafsal' ); ?></span>
		</button>

		<div class="sv-a11y__panel" id="sv-a11y-panel" data-sv-a11y-panel role="group" aria-label="<?php esc_attr_e( 'אפשרויות נגישות', 'savta-al-hasafsal' ); ?>" hidden>
			<div class="sv-a11y__head">
				<h2 class="sv-a11y__title"><?php esc_html_e( 'אפשרויות נגישות', 'savta-al-hasafsal' ); ?></h2>
				<button type="button" class="sv-a11y__close" data-sv-a11y-close>
					<span aria-hidden="true">&times;</span>
					<span class="sv-sr-only"><?php esc_html_e( 'סגירת תפריט הנגישות', 'savta-al-hasafsal' ); ?></span>
				</button>
			</div>

			<ul class="sv-a11y__list">
				<?php foreach ( savta_a11y_controls() as $control ) : ?>
					<li>
						<button
							type="button"
							class="sv-a11y__option"
							data-sv-a11y-option="<?php echo esc_attr( $control['key'] ); ?>"
							data-sv-a11y-type="<?php echo esc_attr( $control['type'] ); ?>"
							<?php echo 'toggle' === $control['type'] ? 'aria-pressed="false"' : ''; ?>
						><?php echo esc_html( $control['label'] ); ?></button>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="sv-a11y__foot">
				<button type="button" class="sv-a11y__reset" data-sv-a11y-reset><?php esc_html_e( 'איפוס הגדרות', 'savta-al-hasafsal' ); ?></button>
				<?php if ( '' !== $statement_url ) : ?>
					<a class="sv-a11y__statement" href="<?php echo esc_url( $statement_url ); ?>"><?php esc_html_e( 'הצהרת נגישות', 'savta-al-hasafsal' ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<div class="sv-a11y__guide" data-sv-a11y-guide aria-hidden="true" hidden></div>
		<p class="sv-sr-only" role="status" aria-live="polite" data-sv-a11y-status></p>
	</div>
	<?php
}
add_action( 'wp_footer', 'savta_the_a11y_toolbar', 5 );

/**
 * Renders the skip link: the first focusable element on the page.
 *
 * @return void
 */
function savta_the_skip_link(): void {
	printf( '<a class="sv-skip-link" href="#sv-main">%s</a>', esc_html__( 'דילוג לתוכן הראשי', 'savta-al-hasafsal' ) );
}
