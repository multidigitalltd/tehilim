<?php
/**
 * Create-campaign form.
 *
 * @package Tehillim_Campaign_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="tcm-wrap">
	<div class="tcm-card">
		<h3><?php esc_html_e('Open a Tehillim campaign', 'tehillim-campaign-manager'); ?></h3>
		<form class="tcm-form" data-tcm-create>
			<div class="tcm-field">
				<label for="tcm-c-title"><?php esc_html_e('Dedication / title', 'tehillim-campaign-manager'); ?></label>
				<input type="text" id="tcm-c-title" name="title" required>
			</div>
			<div class="tcm-field">
				<label for="tcm-c-target"><?php esc_html_e('How many books is the base goal?', 'tehillim-campaign-manager'); ?></label>
				<input type="number" id="tcm-c-target" name="target" min="1" value="1" required>
			</div>
			<div class="tcm-field">
				<label for="tcm-c-content"><?php esc_html_e('Short description', 'tehillim-campaign-manager'); ?> <span class="tcm-muted"><?php esc_html_e('optional', 'tehillim-campaign-manager'); ?></span></label>
				<textarea id="tcm-c-content" name="content" rows="4"></textarea>
			</div>
			<button class="tcm-btn tcm-submit-btn" type="submit"><?php esc_html_e('Create campaign', 'tehillim-campaign-manager'); ?></button>
			<p class="tcm-form-error" role="alert" hidden></p>
		</form>
	</div>
</div>
