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
				<h1>צרו קמפיין</h1>
				<p>הפעילו קמפיין כדי לאחד את הקהילה בקריאת תהילים</p>
				<div class="create-info">
					<h3>למה ליצור קמפיין?</h3>
					<ul>
						<li>אחדו קהילה לתפילה וגדילה רוחנית</li>
						<li>עקבו אחרי ההתקדמות המשותפת לעבר מטרה משמעותית</li>
						<li>שתפו קישורים אישיים עם שגרירים</li>
						<li>יצרו השפעה ארוכת טווח דרך התבטאות משותפות</li>
					</ul>
				</div>
			</header>

			<form class="form-campaign-create">
				<!-- Occasion Selector -->
				<div class="form-group">
					<label for="occasion">למה קמפיין זה?</label>
					<select id="occasion" name="occasion" required>
						<option value="">בחרו סיבה</option>
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
					<label for="dedication_name">שם להקדיש את זה</label>
					<input type="text" id="dedication_name" name="dedication_name" required placeholder="למשל: שרה בת דוד">
				</div>

				<!-- Organizer Name -->
				<div class="form-group">
					<label for="organizer_name">שמך</label>
					<input type="text" id="organizer_name" name="organizer_name" required placeholder="שמך">
				</div>

				<!-- Goal Books -->
				<div class="form-group">
					<label for="goal_books">
						מטרה (ספרי תהילים)
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
					צרו קמפיין
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
