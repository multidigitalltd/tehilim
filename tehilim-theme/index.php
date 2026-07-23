<?php
/**
 * Index Template
 */
get_header();
?>

<main class="site-main">
	<div class="container">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				?>
				<article <?php post_class(); ?>>
					<h1><?php the_title(); ?></h1>
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>
				<?php
			}
		} else {
			?>
			<p><?php esc_html_e( 'No posts found.', 'tehilim' ); ?></p>
			<?php
		}
		?>
	</div>
</main>

<?php
get_footer();
