<?php
/**
 * Subscribe form (e.g. daily Tehillim chapter).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $list List key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tcm-wrap">
	<div class="tcm-card">
		<h3><?php esc_html_e( 'Subscribe to a daily Tehillim chapter', 'tehillim-campaign-manager' ); ?></h3>
		<form class="tcm-form tcm-subscribe-form" data-tcm-subscribe data-tcm-list="<?php echo esc_attr( $list ); ?>">
			<div class="tcm-field">
				<label for="tcm-sub-channel"><?php esc_html_e( 'Receive via', 'tehillim-campaign-manager' ); ?></label>
				<select id="tcm-sub-channel" name="channel">
					<option value="whatsapp"><?php esc_html_e( 'WhatsApp', 'tehillim-campaign-manager' ); ?></option>
					<option value="email"><?php esc_html_e( 'Email', 'tehillim-campaign-manager' ); ?></option>
				</select>
			</div>
			<div class="tcm-field">
				<label for="tcm-sub-name"><?php esc_html_e( 'Name', 'tehillim-campaign-manager' ); ?> <span class="tcm-muted"><?php esc_html_e( 'optional', 'tehillim-campaign-manager' ); ?></span></label>
				<input type="text" id="tcm-sub-name" name="name" autocomplete="name">
			</div>
			<div class="tcm-field">
				<label for="tcm-sub-phone"><?php esc_html_e( 'Phone (for WhatsApp)', 'tehillim-campaign-manager' ); ?></label>
				<input type="tel" id="tcm-sub-phone" name="phone" autocomplete="tel">
			</div>
			<div class="tcm-field">
				<label for="tcm-sub-email"><?php esc_html_e( 'Email', 'tehillim-campaign-manager' ); ?></label>
				<input type="email" id="tcm-sub-email" name="email" autocomplete="email">
			</div>
			<p class="tcm-field">
				<label>
					<input type="checkbox" name="consent" value="1" required>
					<?php esc_html_e( 'I agree to receive messages and can unsubscribe at any time.', 'tehillim-campaign-manager' ); ?>
				</label>
			</p>
			<button class="tcm-btn tcm-submit-btn" type="submit"><?php esc_html_e( 'Subscribe', 'tehillim-campaign-manager' ); ?></button>
			<p class="tcm-form-error" role="alert" hidden></p>
			<p class="tcm-form-success" role="status" hidden><?php esc_html_e( 'Thank you! Your subscription is active.', 'tehillim-campaign-manager' ); ?></p>
		</form>
	</div>
</div>
