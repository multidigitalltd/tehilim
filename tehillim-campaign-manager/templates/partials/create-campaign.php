<?php
/**
 * Create-campaign wizard (four steps: details, goal, ambassadors, review).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var bool   $logged_in    Whether the visitor is logged in.
 * @var string $login_url    Login URL that returns to this page.
 * @var string $register_url Registration URL ('' when registration is closed).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logged_in    = ! empty( $logged_in );
$login_url    = isset( $login_url ) ? (string) $login_url : '';
$register_url = isset( $register_url ) ? (string) $register_url : '';
?>
<div class="tcm-wrap">
	<div class="tcm-card tcm-wizard" data-tcm-wizard
		<?php echo $logged_in ? 'data-tcm-logged-in="1"' : ''; ?>
		data-tcm-login="<?php echo esc_url( $login_url ); ?>">
		<p class="tcm-wizard-steplabel" aria-live="polite"><?php esc_html_e( 'Step', 'tehillim-campaign-manager' ); ?> <span data-tcm-stepnum>1</span> / 4</p>
		<div class="tcm-wizard-bars" aria-hidden="true">
			<span class="is-active"></span><span></span><span></span><span></span>
		</div>

		<form class="tcm-form tcm-wizard-form" data-tcm-create>
			<div class="tcm-wizard-pane" data-tcm-pane="1">
				<h3 tabindex="-1"><?php esc_html_e( 'Campaign details', 'tehillim-campaign-manager' ); ?></h3>
				<div class="tcm-field">
					<label for="tcm-c-title"><?php esc_html_e( 'Dedication / title', 'tehillim-campaign-manager' ); ?></label>
					<input type="text" id="tcm-c-title" name="title" maxlength="120" aria-describedby="tcm-create-error" required>
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
				<h3 tabindex="-1"><?php esc_html_e( 'Goal', 'tehillim-campaign-manager' ); ?></h3>
				<div class="tcm-field">
					<label for="tcm-c-target"><?php esc_html_e( 'Base goal: how many books to complete?', 'tehillim-campaign-manager' ); ?></label>
					<input type="number" id="tcm-c-target" name="target" min="1" value="1" aria-describedby="tcm-create-error" required>
				</div>
				<div class="tcm-field">
					<label for="tcm-c-bonus"><?php esc_html_e( 'Bonus books', 'tehillim-campaign-manager' ); ?> <span class="tcm-muted"><?php esc_html_e( 'optional', 'tehillim-campaign-manager' ); ?></span></label>
					<input type="number" id="tcm-c-bonus" name="bonus" min="0" value="0">
				</div>
			</div>

			<div class="tcm-wizard-pane" data-tcm-pane="3" hidden>
				<h3 tabindex="-1"><?php esc_html_e( 'Ambassadors', 'tehillim-campaign-manager' ); ?></h3>
				<p class="tcm-muted"><?php esc_html_e( 'After you publish, you will get a shareable link and can invite ambassadors — each gets a personal link and a place on the campaign leaderboard.', 'tehillim-campaign-manager' ); ?></p>
			</div>

			<div class="tcm-wizard-pane" data-tcm-pane="4" hidden>
				<h3 tabindex="-1"><?php esc_html_e( 'Review', 'tehillim-campaign-manager' ); ?></h3>
				<ul class="tcm-wizard-review" data-tcm-review></ul>

				<?php if ( ! $logged_in ) : ?>
					<div class="tcm-auth-notice" data-tcm-auth-notice>
						<p><?php esc_html_e( 'To publish your campaign, please log in or create an account. Your details are saved and you will return here automatically.', 'tehillim-campaign-manager' ); ?></p>
						<p class="tcm-auth-actions">
							<a class="tcm-btn" href="<?php echo esc_url( $login_url ); ?>" data-tcm-login-link data-tcm-save-draft><?php esc_html_e( 'Log in & publish', 'tehillim-campaign-manager' ); ?></a>
							<?php if ( '' !== $register_url ) : ?>
								<a class="tcm-btn is-secondary" href="<?php echo esc_url( $register_url ); ?>" data-tcm-save-draft><?php esc_html_e( 'Create an account', 'tehillim-campaign-manager' ); ?></a>
							<?php endif; ?>
						</p>
					</div>
				<?php else : ?>
					<p class="tcm-muted"><?php esc_html_e( 'All set? Publish your campaign.', 'tehillim-campaign-manager' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="tcm-wizard-nav">
				<button type="button" class="tcm-btn is-secondary" data-tcm-prev hidden><?php esc_html_e( 'Back', 'tehillim-campaign-manager' ); ?></button>
				<button type="button" class="tcm-btn" data-tcm-next><?php esc_html_e( 'Continue', 'tehillim-campaign-manager' ); ?></button>
				<button type="submit" class="tcm-btn" data-tcm-publish hidden><?php echo $logged_in ? esc_html__( 'Publish campaign', 'tehillim-campaign-manager' ) : esc_html__( 'Log in & publish', 'tehillim-campaign-manager' ); ?></button>
			</div>
			<p class="tcm-form-error" id="tcm-create-error" role="alert" aria-live="assertive" tabindex="-1" hidden></p>
		</form>
		<p class="tcm-form-success" data-tcm-pending role="status" tabindex="-1" hidden>
			<?php esc_html_e( 'Your campaign was submitted and is awaiting approval. You will be notified once it is published.', 'tehillim-campaign-manager' ); ?>
		</p>
	</div>
</div>
