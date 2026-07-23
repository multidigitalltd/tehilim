<?php
/**
 * Campaign Archive Template - Display all campaigns with filtering
 */

get_header();
?>

<main class="site-main">
	<div style="animation: floatIn 0.55s cubic-bezier(0.2, 0.7, 0.2, 1) both; max-width: 1200px; margin: 0 auto; padding: 46px 32px 74px; text-align: center;">
		<div style="display: inline-flex; align-items: center; gap: 7px; background: #FBF3E4; border: 1px solid #EED9B2; color: #B9822B; font-weight: 700; font-size: 13px; padding: 7px 14px; border-radius: 999px; margin-bottom: 16px;">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="2" stroke-linecap="round">
				<path d="M4 7h16M4 12h16M4 17h10"></path>
			</svg>
			<?php esc_html_e( 'ארכיון הקמפיינים', 'tehilim' ); ?>
		</div>

		<h1 style="font-weight: 900; font-size: 46px; margin: 0 0 10px; color: #2E2318;">
			<?php esc_html_e( 'כל הקמפיינים', 'tehilim' ); ?>
		</h1>
		<p style="font-size: 18px; color: #6B5D4C; margin: 0;">
			<?php
			global $wp_query;
			$campaign_total = (int) $wp_query->found_posts;
			printf(
				esc_html__( 'גלו קמפיינים פעילים והצטרפו לומר תהילים יחד · %d קמפיינים', 'tehilim' ),
				$campaign_total
			);
			?>
		</p>
	</div>

	<!-- Campaign Filter Buttons -->
	<div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin: 30px 32px 36px; max-width: 1200px; margin-left: auto; margin-right: auto;">
		<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>" style="padding: 10px 16px; border-radius: 999px; border: 1.5px solid #C05A3A; background: #C05A3A; color: #FFF7F2; font-size: 14px; font-weight: 700; display: inline-block;">
			<?php esc_html_e( 'הכל', 'tehilim' ); ?>
		</a>
		<?php
		$occasions = get_terms( array(
			'taxonomy'   => 'occasion',
			'hide_empty' => true,
		) );

		if ( ! is_wp_error( $occasions ) && ! empty( $occasions ) ) {
			foreach ( $occasions as $occasion ) {
				echo sprintf(
					'<a href="%s" style="padding: 10px 16px; border-radius: 999px; border: 1.5px solid #EADCC6; background: #FFFCF6; color: #6B5D4C; font-size: 14px; font-weight: 700; display: inline-block;">%s</a>',
					esc_url( get_term_link( $occasion ) ),
					esc_html( $occasion->name )
				);
			}
		}
		?>
	</div>

	<!-- Campaign Grid -->
	<div style="max-width: 1200px; margin: 0 auto; padding: 0 32px 70px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; text-align: right;">
		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/campaign-card' );
			}
		} else {
			echo '<div style="grid-column: 1/-1; text-align: center; padding: 60px 32px;"><p style="font-size: 18px; color: #6B5D4C;">' . esc_html__( 'אין קמפיינים עדיין.', 'tehilim' ) . '</p></div>';
		}
		?>
	</div>

	<!-- Pagination -->
	<div style="max-width: 1200px; margin: 0 auto; padding: 0 32px; margin-bottom: 40px;">
		<?php the_posts_pagination(); ?>
	</div>
</main>

<?php
get_footer();
