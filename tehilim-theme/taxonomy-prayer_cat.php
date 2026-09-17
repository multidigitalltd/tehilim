<?php
/**
 * Prayer category archive — lists prayers in a category with an SEO intro.
 */
get_header();

$term = get_queried_object();
?>

<div class="prayers-page page-anim">

	<nav class="prayer-breadcrumb" aria-label="<?php esc_attr_e( 'ניווט', 'tehilim' ); ?>">
		<a href="<?php echo esc_url( get_post_type_archive_link( 'prayer' ) ); ?>"><?php esc_html_e( 'תפילות', 'tehilim' ); ?></a>
		<span aria-hidden="true">›</span>
		<span><?php echo esc_html( $term->name ); ?></span>
	</nav>

	<section class="prayers-hero">
		<h1><?php echo esc_html( $term->name ); ?></h1>
		<?php if ( $term && ! is_wp_error( $term ) && $term->description ) : ?>
			<p><?php echo esc_html( $term->description ); ?></p>
		<?php endif; ?>
	</section>

	<section class="prayers-recent">
		<div class="prayers-list">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<a class="prayer-list-item" href="<?php the_permalink(); ?>">
						<span class="prayer-list-item-title"><?php the_title(); ?></span>
						<?php if ( has_excerpt() ) : ?>
							<span class="prayer-list-item-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></span>
						<?php endif; ?>
					</a>
				<?php endwhile; ?>
				<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'תפילות בקטגוריה זו יתווספו בקרוב.', 'tehilim' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<div class="prayers-cta">
		<a class="btn-account-secondary" href="<?php echo esc_url( get_post_type_archive_link( 'prayer' ) ); ?>"><?php esc_html_e( 'לכל התפילות', 'tehilim' ); ?></a>
	</div>
</div>

<?php
get_footer();
