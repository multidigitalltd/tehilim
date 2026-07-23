<?php
/**
 * Campaign Archive Template
 */
get_header();
?>

<main class="site-main">
	<div class="container">
		<header class="archive-header">
			<h1><?php post_type_archive_title(); ?></h1>
		</header>

		<!-- Occasion Filters -->
		<div class="filter-bar">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>" class="filter-chip">
				<?php esc_html_e( 'All', 'tehilim' ); ?>
			</a>

			<?php
			$occasions = get_terms( array(
				'taxonomy'   => 'occasion',
				'hide_empty' => true,
			) );

			foreach ( $occasions as $occasion ) {
				$active = get_query_var( 'occasion' ) === $occasion->slug ? 'active' : '';
				?>
				<a href="<?php echo esc_url( get_term_link( $occasion ) ); ?>" class="filter-chip <?php echo esc_attr( $active ); ?>">
					<?php echo esc_html( $occasion->name ); ?>
				</a>
				<?php
			}
			?>
		</div>

		<!-- Campaigns Grid -->
		<div class="campaigns-grid">
			<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/campaign-card' );
				}
			} else {
				?>
				<p><?php esc_html_e( 'No campaigns found.', 'tehilim' ); ?></p>
				<?php
			}
			?>
		</div>

		<!-- Pagination -->
		<?php the_posts_pagination(); ?>
	</div>
</main>

<?php
get_footer();
