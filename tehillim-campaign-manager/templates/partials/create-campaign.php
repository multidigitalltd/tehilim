<?php
/**
 * Create-campaign wizard (four steps: details, goal, ambassadors, review).
 *
 * @package Tehillim_Campaign_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tcm-wrap">
	<div class="tcm-card tcm-wizard" data-tcm-wizard>
		<p class="tcm-wizard-steplabel"><?php esc_html_e( 'Step', 'tehillim-campaign-manager' ); ?> <span data-tcm-stepnum>1</span> / 4</p>
		<div class="tcm-wizard-bars" aria-hidden="true">
			<span class="is-active"></span><span></span><span></span><span></span>
		</div>

		<form class="tcm-form tcm-wizard-form" data-tcm-create>
			<div class="tcm-wizard-pane" data-tcm-pane="1">
				<h3><?php esc_html_e( 'Campaign details', 'tehillim-campaign-manager' ); ?></h3>
				<div class="tcm-field">
					<label for="tcm-c-title"><?php esc_html_e( 'Dedication / title', 'tehillim-campaign-manager' ); ?></label>
					<input type="text" id="tcm-c-title" name="title" maxlength="120" required>
				</div>
				<div class="tcm-field">
					<label for="tcm-c-ded"><?php esc_html_e( 'Dedicated to', 'tehillim-campaign-manager' ); ?> <span class="tcm-muted"><?php esc_html_e( 'optional', 'tehillim-campaign-manager' ); ?></span></label>
					<input type="text" id="tcm-c-ded" name="dedicated_to" maxlength="120">
				</div>
				<div class="tcm-field">
					<label for="tcm-c-content"><?php esc_html_e( 'Short description', 'tehillim-campaign-manager' ); ?> <span class="tcm-muted"><?php esc_html_e( 'optional', 'tehillim-campaign-manager' ); ?></span></label>
					<textarea id="tcm-c-content" name="content" rows="4"></textarea>
				</div>
			</div>

			<div class="tcm-wizard-pane" data-tcm-pane="2" hidden>
				<h3><?php esc_html_e( 'Goal', 'tehillim-campaign-manager' ); ?></h3>
				<div class="tcm-field">
					<label for="tcm-c-target"><?php esc_html_e( 'Base goal: how many books to complete?', 'tehillim-campaign-manager' ); ?></label>
					<input type="number" id="tcm-c-target" name="target" min="1" value="1" required>
				</div>
				<div class="tcm-field">
					<label for="tcm-c-bonus"><?php esc_html_e( 'Bonus books', 'tehillim-campaign-manager' ); ?> <span class="tcm-muted"><?php esc_html_e( 'optional', 'tehillim-campaign-manager' ); ?></span></label>
					<input type="number" id="tcm-c-bonus" name="bonus" min="0" value="0">
				</div>
			</div>

			<div class="tcm-wizard-pane" data-tcm-pane="3" hidden>
				<h3><?php esc_html_e( 'Ambassadors', 'tehillim-campaign-manager' ); ?></h3>
				<p class="tcm-muted"><?php esc_html_e( 'After you publish, you will get a shareable link and can invite ambassadors — each gets a personal link and a place on the campaign leaderboard.', 'tehillim-campaign-manager' ); ?></p>
			</div>

			<div class="tcm-wizard-pane" data-tcm-pane="4" hidden>
				<h3><?php esc_html_e( 'Review', 'tehillim-campaign-manager' ); ?></h3>
				<ul class="tcm-wizard-review" data-tcm-review></ul>
				<p class="tcm-muted"><?php esc_html_e( 'All set? Publish your campaign.', 'tehillim-campaign-manager' ); ?></p>
			</div>

			<div class="tcm-wizard-nav">
				<button type="button" class="tcm-btn is-secondary" data-tcm-prev hidden><?php esc_html_e( 'Back', 'tehillim-campaign-manager' ); ?></button>
				<button type="button" class="tcm-btn" data-tcm-next><?php esc_html_e( 'Continue', 'tehillim-campaign-manager' ); ?></button>
				<button type="submit" class="tcm-btn" data-tcm-publish hidden><?php esc_html_e( 'Publish campaign', 'tehillim-campaign-manager' ); ?></button>
			</div>
			<p class="tcm-form-error" role="alert" hidden></p>
		</form>
	</div>
</div>
