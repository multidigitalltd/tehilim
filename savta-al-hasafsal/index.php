<?php
/**
 * Fallback template: single posts, archives and search results.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<article class="sv-page">
	<div class="sv-page__inner">
		<?php if ( have_posts() ) : ?>
			<?php if ( is_singular() ) : ?>
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/page/content' );
				}
				?>
			<?php else : ?>
				<header class="sv-page__head">
					<h1 class="sv-page__title"><?php echo is_search() ? esc_html( get_search_query() ) : esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
				</header>

				<ul class="sv-list">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<li class="sv-list__item">
							<a class="sv-list__link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							<?php if ( has_excerpt() ) : ?>
								<p class="sv-list__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
							<?php endif; ?>
						</li>
					<?php endwhile; ?>
				</ul>

				<?php
				the_posts_pagination(
					array(
						'prev_text' => __( 'הקודם', 'savta-al-hasafsal' ),
						'next_text' => __( 'הבא', 'savta-al-hasafsal' ),
					)
				);
				?>
			<?php endif; ?>
		<?php else : ?>
			<header class="sv-page__head">
				<h1 class="sv-page__title"><?php esc_html_e( 'לא נמצא תוכן', 'savta-al-hasafsal' ); ?></h1>
			</header>
			<p><?php esc_html_e( 'לא מצאנו את מה שחיפשת.', 'savta-al-hasafsal' ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'חזרה לדף הבית', 'savta-al-hasafsal' ); ?></a></p>
		<?php endif; ?>
	</div>
</article>

<?php
get_footer();
