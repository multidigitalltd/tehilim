<?php
/**
 * 404 Template
 */
get_header();
?>

<main class="site-main">
	<div class="container">
		<div class="error-404">
			<h1>עמוד לא נמצא</h1>
			<p>העמוד שחיפשתם לא נמצא.</p>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">
				חזרו לדף הבית
			</a>
		</div>
	</div>
</main>

<?php
get_footer();
