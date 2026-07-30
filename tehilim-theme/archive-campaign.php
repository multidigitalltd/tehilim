<?php
/**
 * Campaign Archive Template — exact design match (archive.html)
 */

get_header();
?>

<main class="site-main">
	<div class="archive-page">

		<div class="archive-head">
			<div class="archive-badge">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="2" stroke-linecap="round">
					<path d="M4 7h16M4 12h16M4 17h10"></path>
				</svg>
				<?php esc_html_e( 'ארכיון קבוצות התהילים', 'tehilim' ); ?>
			</div>

			<h1><?php esc_html_e( 'כל קבוצות התהילים', 'tehilim' ); ?></h1>
			<p>
				<?php
				global $wp_query;
				$campaign_total = (int) $wp_query->found_posts;
				printf(
					esc_html__( 'גלו קבוצות תהילים פעילות והצטרפו לומר תהילים יחד · %d קבוצות תהילים', 'tehilim' ),
					$campaign_total
				);
				?>
			</p>
		</div>

		<!-- Campaign grid -->
		<div class="archive-grid">
			<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/campaign-card' );
				}
			} else {
				echo '<div style="grid-column: 1/-1; text-align: center; padding: 60px 32px;"><p style="font-size: 18px; color: #6B5D4C; margin: 0;">' . esc_html__( 'אין קבוצות תהילים עדיין.', 'tehilim' ) . '</p></div>';
			}
			?>
		</div>

		<?php the_posts_pagination(); ?>
	</div>
</main>

<?php
get_footer();
