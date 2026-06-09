<?php
/**
 * Accessible contact form (with optional Turnstile).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $site_key Turnstile site key ('' when disabled).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tcm-wrap">
	<div class="tcm-card">
		<form class="tcm-form tcm-contact-form" data-tcm-contact>
			<div class="tcm-field">
				<label for="tcm-contact-name"><?php esc_html_e( 'Name', 'tehillim-campaign-manager' ); ?></label>
				<input type="text" id="tcm-contact-name" name="name" autocomplete="name" maxlength="120" aria-describedby="tcm-contact-error" required>
			</div>
			<div class="tcm-field">
				<label for="tcm-contact-email"><?php esc_html_e( 'Email', 'tehillim-campaign-manager' ); ?></label>
				<input type="email" id="tcm-contact-email" name="email" autocomplete="email" maxlength="190" aria-describedby="tcm-contact-error" required>
			</div>
			<div class="tcm-field">
				<label for="tcm-contact-subject"><?php esc_html_e( 'Subject', 'tehillim-campaign-manager' ); ?> <span class="tcm-muted"><?php esc_html_e( 'optional', 'tehillim-campaign-manager' ); ?></span></label>
				<input type="text" id="tcm-contact-subject" name="subject" maxlength="160">
			</div>
			<div class="tcm-field">
				<label for="tcm-contact-message"><?php esc_html_e( 'Message', 'tehillim-campaign-manager' ); ?></label>
				<textarea id="tcm-contact-message" name="message" rows="5" maxlength="4000" aria-describedby="tcm-contact-error" required></textarea>
			</div>

			<?php if ( '' !== $site_key ) : ?>
				<div class="tcm-turnstile cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<?php endif; ?>

			<button class="tcm-btn tcm-submit-btn" type="submit"><?php esc_html_e( 'Send message', 'tehillim-campaign-manager' ); ?></button>
			<p class="tcm-form-error" id="tcm-contact-error" role="alert" aria-live="assertive" tabindex="-1" hidden></p>
			<p class="tcm-form-success" role="status" tabindex="-1" hidden><?php esc_html_e( 'Thank you! Your message has been sent.', 'tehillim-campaign-manager' ); ?></p>
		</form>
	</div>
</div>
