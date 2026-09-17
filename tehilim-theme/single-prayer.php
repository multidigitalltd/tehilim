<?php
/**
 * Single prayer — the prayer text with breadcrumb and structured data.
 */
get_header();

while ( have_posts() ) :
	the_post();

	$prayer_id  = get_the_ID();
	$terms      = get_the_terms( $prayer_id, 'prayer_cat' );
	$first_term = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	?>

	<div class="prayers-page prayer-single page-anim">

		<nav class="prayer-breadcrumb" aria-label="<?php esc_attr_e( 'ניווט', 'tehilim' ); ?>">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'prayer' ) ); ?>"><?php esc_html_e( 'תפילות', 'tehilim' ); ?></a>
			<?php if ( $first_term ) : ?>
				<span aria-hidden="true">›</span>
				<a href="<?php echo esc_url( get_term_link( $first_term ) ); ?>"><?php echo esc_html( $first_term->name ); ?></a>
			<?php endif; ?>
			<span aria-hidden="true">›</span>
			<span><?php the_title(); ?></span>
		</nav>

		<article class="prayer-article">
			<h1 class="prayer-title"><?php the_title(); ?></h1>

			<div class="prayer-content">
				<?php the_content(); ?>
			</div>

			<div class="prayer-share">
				<button class="btn-account-share btn-share-modal" data-share-url="<?php echo esc_url( get_permalink() ); ?>" data-share-text="<?php echo esc_attr( get_the_title() ); ?>">
					<?php esc_html_e( 'שיתוף התפילה', 'tehilim' ); ?>
				</button>
			</div>
		</article>

		<?php
		// Other prayers in the same category
		if ( $first_term ) :
			$related = get_posts( array(
				'post_type'      => 'prayer',
				'posts_per_page' => 6,
				'post__not_in'   => array( $prayer_id ),
				'tax_query'      => array(
					array(
						'taxonomy' => 'prayer_cat',
						'field'    => 'term_id',
						'terms'    => $first_term->term_id,
					),
				),
			) );
			if ( $related ) :
				?>
				<section class="prayers-related">
					<h2><?php printf( esc_html__( 'עוד ב%s', 'tehilim' ), esc_html( $first_term->name ) ); ?></h2>
					<div class="prayers-list">
						<?php foreach ( $related as $r ) : ?>
							<a class="prayer-list-item" href="<?php echo esc_url( get_permalink( $r ) ); ?>">
								<span class="prayer-list-item-title"><?php echo esc_html( get_the_title( $r ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
				<?php
			endif;
		endif;
		?>
	</div>

	<?php
endwhile;

get_footer();
