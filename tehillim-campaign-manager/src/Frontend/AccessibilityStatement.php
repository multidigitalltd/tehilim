<?php
/**
 * Accessibility statement shortcode.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The [tehillim_accessibility_statement] shortcode renders a Hebrew accessibility
 * statement (IS 5568 / WCAG 2.0 AA), with the coordinator contact from settings.
 */
final class AccessibilityStatement implements Registerable {

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_shortcode( 'tehillim_accessibility_statement', array( $this, 'render' ) );
	}

	/**
	 * Render the statement.
	 *
	 * @return string
	 */
	public function render() {
		Assets::ensure();
		return Templating::render(
			'partials/accessibility-statement',
			array(
				'contact_name'  => (string) Options::get( 'a11y_contact_name' ),
				'contact_email' => (string) Options::get( 'a11y_contact_email' ),
				'contact_phone' => (string) Options::get( 'a11y_contact_phone' ),
				'updated'       => wp_date( (string) get_option( 'date_format' ) ),
			)
		);
	}
}
