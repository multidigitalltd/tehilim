<?php
/**
 * Accessibility statement (Hebrew, IS 5568 / WCAG 2.0 AA).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $contact_name  Accessibility coordinator name.
 * @var string $contact_email Accessibility email.
 * @var string $contact_phone Accessibility phone.
 * @var string $updated       Last-updated date.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tcm-wrap tcm-a11y-statement">
	<div class="tcm-card">
		<h2><?php esc_html_e( 'Accessibility statement', 'tehillim-campaign-manager' ); ?></h2>

		<p><?php esc_html_e( 'We see great importance in making our website accessible to everyone, including people with disabilities, and we invest ongoing effort to do so.', 'tehillim-campaign-manager' ); ?></p>

		<h3><?php esc_html_e( 'Compliance', 'tehillim-campaign-manager' ); ?></h3>
		<p><?php esc_html_e( 'This site aims to comply with the Equal Rights for Persons with Disabilities Regulations (Service Accessibility Adjustments), 5773-2013, at level AA of the WCAG 2.0 guidelines and Israeli Standard 5568.', 'tehillim-campaign-manager' ); ?></p>

		<h3><?php esc_html_e( 'What is accessible', 'tehillim-campaign-manager' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Keyboard navigation across interactive elements.', 'tehillim-campaign-manager' ); ?></li>
			<li><?php esc_html_e( 'Screen-reader support, ARIA roles and live regions on forms.', 'tehillim-campaign-manager' ); ?></li>
			<li><?php esc_html_e( 'Sufficient colour contrast (AA) and resizable text.', 'tehillim-campaign-manager' ); ?></li>
			<li><?php esc_html_e( 'Clear heading structure, focus indicators and alt text for images.', 'tehillim-campaign-manager' ); ?></li>
			<li><?php esc_html_e( 'Responsive layout for mobile and tablet.', 'tehillim-campaign-manager' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Limitations', 'tehillim-campaign-manager' ); ?></h3>
		<p><?php esc_html_e( 'Despite our efforts, some pages or third-party content may not yet be fully accessible. We continue to improve accessibility over time.', 'tehillim-campaign-manager' ); ?></p>

		<h3><?php esc_html_e( 'Accessibility requests and contact', 'tehillim-campaign-manager' ); ?></h3>
		<p><?php esc_html_e( 'If you encounter an accessibility problem, or need assistance, please contact our accessibility coordinator:', 'tehillim-campaign-manager' ); ?></p>
		<ul class="tcm-a11y-contact">
			<?php if ( '' !== $contact_name ) : ?>
				<li><?php esc_html_e( 'Coordinator:', 'tehillim-campaign-manager' ); ?> <strong><?php echo esc_html( $contact_name ); ?></strong></li>
			<?php endif; ?>
			<?php if ( '' !== $contact_email ) : ?>
				<li><?php esc_html_e( 'Email:', 'tehillim-campaign-manager' ); ?> <a href="<?php echo esc_url( 'mailto:' . $contact_email ); ?>"><?php echo esc_html( $contact_email ); ?></a></li>
			<?php endif; ?>
			<?php if ( '' !== $contact_phone ) : ?>
				<li><?php esc_html_e( 'Phone:', 'tehillim-campaign-manager' ); ?> <a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $contact_phone ) ); ?>"><?php echo esc_html( $contact_phone ); ?></a></li>
			<?php endif; ?>
			<?php if ( '' === $contact_name && '' === $contact_email && '' === $contact_phone ) : ?>
				<li><?php esc_html_e( 'Please add the accessibility contact under the plugin settings.', 'tehillim-campaign-manager' ); ?></li>
			<?php endif; ?>
		</ul>

		<p class="tcm-muted">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: date the statement was last updated. */
					__( 'Statement last updated: %s', 'tehillim-campaign-manager' ),
					$updated
				)
			);
			?>
		</p>
	</div>
</div>
