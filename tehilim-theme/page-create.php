<?php
/**
 * Campaign Creation Page
 */
get_header();
?>

<main class="site-main">
	<div class="container">
		<div class="create-campaign-wrapper">
			<header class="create-header">
				<h1><?php esc_html_e( 'Create a Campaign', 'tehilim' ); ?></h1>
				<p><?php esc_html_e( 'Start a campaign to bring the community together', 'tehilim' ); ?></p>
			</header>

			<form class="form-campaign-create">
				<!-- Occasion Selector -->
				<div class="form-group">
					<label for="occasion"><?php esc_html_e( 'What is this campaign for?', 'tehilim' ); ?></label>
					<select id="occasion" name="occasion" required>
						<option value=""><?php esc_html_e( 'Select an occasion', 'tehilim' ); ?></option>
						<?php
						$occasions = get_terms( array(
							'taxonomy'   => 'occasion',
							'hide_empty' => false,
						) );

						foreach ( $occasions as $occasion ) {
							?>
							<option value="<?php echo esc_attr( $occasion->term_id ); ?>">
								<?php echo esc_html( $occasion->name ); ?>
							</option>
							<?php
						}
						?>
					</select>
				</div>

				<!-- Dedication Name -->
				<div class="form-group">
					<label for="dedication_name"><?php esc_html_e( 'Name to dedicate this to', 'tehilim' ); ?></label>
					<input type="text" id="dedication_name" name="dedication_name" required placeholder="e.g., Sarah bat David">
				</div>

				<!-- Organizer Name -->
				<div class="form-group">
					<label for="organizer_name"><?php esc_html_e( 'Your name', 'tehilim' ); ?></label>
					<input type="text" id="organizer_name" name="organizer_name" required placeholder="Your name">
				</div>

				<!-- Goal Books -->
				<div class="form-group">
					<label for="goal_books">
						<?php esc_html_e( 'Goal (books of Psalms)', 'tehilim' ); ?>
						<output for="goal_books">1</output>
					</label>
					<input type="range" id="goal_books" name="goal_books" min="1" max="100" value="1">
				</div>

				<!-- Turnstile (if configured) -->
				<?php if ( defined( 'TURNSTILE_SITE_KEY' ) && TURNSTILE_SITE_KEY ) : ?>
					<div class="form-group">
						<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( TURNSTILE_SITE_KEY ); ?>"></div>
					</div>
				<?php endif; ?>

				<button type="submit" class="btn btn-primary">
					<?php esc_html_e( 'Create Campaign', 'tehilim' ); ?>
				</button>
			</form>
		</div>
	</div>
</main>

<script>
document.getElementById('goal_books').addEventListener('input', function(e) {
	document.querySelector('output[for="goal_books"]').textContent = e.target.value;
});
</script>

<?php
get_footer();
