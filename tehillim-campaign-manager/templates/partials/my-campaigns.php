<?php
/**
 * Owner campaign management.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var bool  $logged_in Whether the user is logged in.
 * @var array $campaigns  Owner's campaigns.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tcm-wrap tcm-my-campaigns">
	<h2><?php esc_html_e( 'My campaigns', 'tehillim-campaign-manager' ); ?></h2>

	<?php if ( empty( $logged_in ) ) : ?>
		<div class="tcm-card"><?php esc_html_e( 'Please log in to view the campaigns you opened.', 'tehillim-campaign-manager' ); ?></div>
	<?php elseif ( empty( $campaigns ) ) : ?>
		<div class="tcm-card"><?php esc_html_e( 'No campaigns yet.', 'tehillim-campaign-manager' ); ?></div>
	<?php else : ?>
		<?php foreach ( $campaigns as $c ) : ?>
			<div class="tcm-card">
				<h3><?php echo esc_html( $c['title'] ); ?></h3>
				<div class="tcm-progress" role="progressbar" aria-valuenow="<?php echo esc_attr( $c['stats']['percent'] ); ?>" aria-valuemin="0" aria-valuemax="100">
					<span style="width:<?php echo esc_attr( $c['stats']['percent'] ); ?>%"></span>
				</div>
				<p><a class="tcm-btn" href="<?php echo esc_url( $c['permalink'] ); ?>"><?php esc_html_e( 'Open campaign', 'tehillim-campaign-manager' ); ?></a></p>

				<div class="tcm-share-box">
					<label class="screen-reader-text" for="tcm-share-<?php echo esc_attr( $c['id'] ); ?>"><?php esc_html_e( 'Share link', 'tehillim-campaign-manager' ); ?></label>
					<input id="tcm-share-<?php echo esc_attr( $c['id'] ); ?>" readonly value="<?php echo esc_attr( $c['permalink'] ); ?>" style="direction:ltr;text-align:left;width:100%">
					<button type="button" class="tcm-btn is-secondary" data-tcm-copy="<?php echo esc_attr( $c['permalink'] ); ?>"><?php esc_html_e( 'Copy link', 'tehillim-campaign-manager' ); ?></button>
				</div>

				<form class="tcm-form tcm-inline-form" data-tcm-update data-tcm-id="<?php echo esc_attr( $c['id'] ); ?>">
					<div class="tcm-field">
						<label for="tcm-t-<?php echo esc_attr( $c['id'] ); ?>"><?php esc_html_e( 'Title / dedication', 'tehillim-campaign-manager' ); ?></label>
						<input type="text" id="tcm-t-<?php echo esc_attr( $c['id'] ); ?>" name="title" value="<?php echo esc_attr( $c['title'] ); ?>" aria-describedby="tcm-update-error-<?php echo esc_attr( $c['id'] ); ?>" required>
					</div>
					<div class="tcm-field">
						<label for="tcm-d-<?php echo esc_attr( $c['id'] ); ?>"><?php esc_html_e( 'Description', 'tehillim-campaign-manager' ); ?></label>
						<textarea id="tcm-d-<?php echo esc_attr( $c['id'] ); ?>" name="content"><?php echo esc_textarea( $c['content'] ); ?></textarea>
					</div>
					<div class="tcm-field">
						<label for="tcm-g-<?php echo esc_attr( $c['id'] ); ?>"><?php esc_html_e( 'Base goal (books)', 'tehillim-campaign-manager' ); ?></label>
						<input type="number" id="tcm-g-<?php echo esc_attr( $c['id'] ); ?>" name="target" min="1" value="<?php echo esc_attr( $c['stats']['target'] ); ?>">
					</div>
					<button class="tcm-btn" type="submit"><?php esc_html_e( 'Save changes', 'tehillim-campaign-manager' ); ?></button>
					<button class="tcm-btn is-secondary" type="button" data-tcm-bonus data-tcm-id="<?php echo esc_attr( $c['id'] ); ?>"><?php esc_html_e( 'Add one bonus book', 'tehillim-campaign-manager' ); ?></button>
					<p class="tcm-form-error" id="tcm-update-error-<?php echo esc_attr( $c['id'] ); ?>" role="alert" aria-live="assertive" tabindex="-1" hidden></p>
					<p class="tcm-form-success" role="status" tabindex="-1" hidden><?php esc_html_e( 'Saved.', 'tehillim-campaign-manager' ); ?></p>
				</form>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
