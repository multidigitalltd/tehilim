<?php
/**
 * 404 Template
 */
get_header();
?>

<main class="site-main">
	<div class="container">
		<div class="error-404">
			<h1><?php esc_html_e( 'Page Not Found', 'tehilim' ); ?></h1>
			<p><?php esc_html_e( 'The page you are looking for could not be found.', 'tehilim' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">
				<?php esc_html_e( 'Back to Home', 'tehilim' ); ?>
			</a>
		</div>
	</div>
</main>

<?php
get_footer();
