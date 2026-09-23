<?php
/**
 * Site-wide settings: everything that is not tied to the page content.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default values for the site-wide settings.
 *
 * @return array<string,string|int>
 */
function savta_option_defaults(): array {
	return array(
		'savta_nav_cta_text'     => 'לדבר עם הסבתא',
		'savta_nav_cta_url'      => '#signup',
		'savta_form_mail'        => 1,
		'savta_form_recipient'   => '',
		'savta_footer_name'      => 'סבתא על הספסל',
		'savta_footer_blurb'     => 'מיזם חברתי של הקשבה, חום אנושי ותמיכה קהילתית.',
		'savta_footer_lead'      => 'בהובלת אפרת ברזל.',
		'savta_footer_note'      => 'השירות אינו מחליף טיפול מקצועי או מענה חירום.',
		'savta_footer_copyright' => '© סבתא על הספסל. כל הזכויות שמורות.',
		'savta_credit_enable'    => 1,
		'savta_credit_text'      => 'uxui & dev by',
		'savta_credit_name'      => 'multi digital',
		'savta_credit_url'       => 'https://m-d.co.il/',
		'savta_a11y_enable'      => 1,
		'savta_a11y_page'        => 0,
		'savta_privacy_page'     => 0,
	);
}

/**
 * Reads a site-wide setting with its default applied.
 *
 * @param string $key Setting key.
 * @return string|int
 */
function savta_option( string $key ) {
	$defaults = savta_option_defaults();

	return get_theme_mod( $key, $defaults[ $key ] ?? '' );
}

/**
 * Sanitises a checkbox control.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function savta_sanitize_checkbox( $value ): int {
	return empty( $value ) ? 0 : 1;
}

/**
 * Sanitises a page-picker control.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function savta_sanitize_page_id( $value ): int {
	$id = absint( $value );

	return get_post( $id ) instanceof WP_Post ? $id : 0;
}

/**
 * Sanitises a link that may be an in-page anchor.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function savta_sanitize_link( $value ): string {
	$raw = trim( (string) $value );

	return '' === $raw ? '' : esc_url_raw( $raw, array( 'http', 'https', 'mailto', 'tel' ) );
}

/**
 * Registers the Customizer panel and its sections.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @return void
 */
function savta_customize_register( WP_Customize_Manager $wp_customize ): void {
	$defaults = savta_option_defaults();

	$wp_customize->add_panel(
		'savta_panel',
		array(
			'title'       => __( 'הגדרות סבתא על הספסל', 'savta-al-hasafsal' ),
			'description' => __( 'הגדרות שחלות על כל האתר. תוכן העמוד נערך מתוך העמוד עצמו.', 'savta-al-hasafsal' ),
			'priority'    => 20,
		)
	);

	$sections = array(
		'savta_section_nav'    => array(
			'title'    => __( 'תפריט עליון', 'savta-al-hasafsal' ),
			'settings' => array(
				'savta_nav_cta_text' => array(
					'label'    => __( 'טקסט הכפתור', 'savta-al-hasafsal' ),
					'type'     => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'savta_nav_cta_url'  => array(
					'label'    => __( 'יעד הכפתור', 'savta-al-hasafsal' ),
					'type'     => 'text',
					'sanitize' => 'savta_sanitize_link',
				),
			),
		),
		'savta_section_form'   => array(
			'title'       => __( 'טופס התיאום', 'savta-al-hasafsal' ),
			'description' => __( 'כל פנייה נשמרת בלוח הבקרה תחת "פניות". המייל הוא ערוץ נוסף.', 'savta-al-hasafsal' ),
			'settings'    => array(
				'savta_form_mail'      => array(
					'label'       => __( 'שליחת פניות במייל', 'savta-al-hasafsal' ),
					'description' => __( 'כיבוי מפסיק את המייל בלבד; הפניות ממשיכות להישמר בלוח הבקרה.', 'savta-al-hasafsal' ),
					'type'        => 'checkbox',
					'sanitize'    => 'savta_sanitize_checkbox',
				),
				'savta_form_recipient' => array(
					'label'       => __( 'נמען הפניות', 'savta-al-hasafsal' ),
					'description' => __( 'ריק = כתובת האימייל של האתר (הגדרות ← כללי).', 'savta-al-hasafsal' ),
					'type'        => 'email',
					'sanitize'    => 'sanitize_email',
				),
			),
		),
		'savta_section_footer' => array(
			'title'    => __( 'כותרת תחתונה', 'savta-al-hasafsal' ),
			'settings' => array(
				'savta_footer_name'      => array(
					'label'    => __( 'שם המיזם', 'savta-al-hasafsal' ),
					'type'     => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'savta_footer_blurb'     => array(
					'label'    => __( 'משפט קצר', 'savta-al-hasafsal' ),
					'type'     => 'textarea',
					'sanitize' => 'sanitize_textarea_field',
				),
				'savta_footer_lead'      => array(
					'label'    => __( 'שורת ההובלה', 'savta-al-hasafsal' ),
					'type'     => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'savta_footer_note'      => array(
					'label'    => __( 'הערת האחריות', 'savta-al-hasafsal' ),
					'type'     => 'textarea',
					'sanitize' => 'sanitize_textarea_field',
				),
				'savta_footer_copyright' => array(
					'label'    => __( 'שורת זכויות יוצרים', 'savta-al-hasafsal' ),
					'type'     => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'savta_credit_enable'    => array(
					'label'    => __( 'הצגת קרדיט בונה האתר', 'savta-al-hasafsal' ),
					'type'     => 'checkbox',
					'sanitize' => 'savta_sanitize_checkbox',
				),
				'savta_credit_text'      => array(
					'label'    => __( 'טקסט הקרדיט', 'savta-al-hasafsal' ),
					'type'     => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'savta_credit_name'      => array(
					'label'    => __( 'שם הסטודיו', 'savta-al-hasafsal' ),
					'type'     => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'savta_credit_url'       => array(
					'label'    => __( 'קישור הקרדיט', 'savta-al-hasafsal' ),
					'type'     => 'url',
					'sanitize' => 'savta_sanitize_link',
				),
			),
		),
		'savta_section_a11y'   => array(
			'title'       => __( 'נגישות ופרטיות', 'savta-al-hasafsal' ),
			'description' => __( 'סרגל הנגישות נדרש לעמידה בתקן ת"י 5568.', 'savta-al-hasafsal' ),
			'settings'    => array(
				'savta_a11y_enable'  => array(
					'label'    => __( 'הצגת סרגל נגישות', 'savta-al-hasafsal' ),
					'type'     => 'checkbox',
					'sanitize' => 'savta_sanitize_checkbox',
				),
				'savta_a11y_page'    => array(
					'label'    => __( 'עמוד הצהרת נגישות', 'savta-al-hasafsal' ),
					'type'     => 'dropdown-pages',
					'sanitize' => 'savta_sanitize_page_id',
				),
				'savta_privacy_page' => array(
					'label'    => __( 'עמוד מדיניות פרטיות', 'savta-al-hasafsal' ),
					'type'     => 'dropdown-pages',
					'sanitize' => 'savta_sanitize_page_id',
				),
			),
		),
	);

	$priority = 10;

	foreach ( $sections as $section_id => $section ) {
		$wp_customize->add_section(
			$section_id,
			array(
				'title'       => $section['title'],
				'description' => $section['description'] ?? '',
				'panel'       => 'savta_panel',
				'priority'    => $priority,
			)
		);
		++$priority;

		foreach ( $section['settings'] as $key => $control ) {
			$wp_customize->add_setting(
				$key,
				array(
					'default'           => $defaults[ $key ] ?? '',
					'sanitize_callback' => $control['sanitize'],
					'transport'         => 'refresh',
					'capability'        => 'edit_theme_options',
				)
			);
			$wp_customize->add_control(
				$key,
				array(
					'label'       => $control['label'],
					'description' => $control['description'] ?? '',
					'section'     => $section_id,
					'type'        => $control['type'],
				)
			);
		}
	}
}
add_action( 'customize_register', 'savta_customize_register' );
