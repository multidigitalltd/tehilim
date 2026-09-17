<?php
/**
 * Prayers landing — /prayers/ (CPT archive).
 * SEO hub linking to every prayer category.
 */
get_header();

$prayer_cats = get_terms( array(
	'taxonomy'   => 'prayer_cat',
	'hide_empty' => false,
	'parent'     => 0,
) );
if ( is_wp_error( $prayer_cats ) ) {
	$prayer_cats = array();
}
?>

<div class="prayers-page page-anim">

	<section class="prayers-hero">
		<h1><?php esc_html_e( 'תפילות — אוסף תפילות, ברכות וסגולות', 'tehilim' ); ?></h1>
		<p><?php esc_html_e( 'ליקוט תפילות מכל הלב לפרנסה, לרפואה, לזיווג, לשלום בית ולהצלחת הילדים, לצד ברכות יום־יום וסגולות מרבותינו. בחרו קטגוריה והתחילו להתפלל.', 'tehilim' ); ?></p>
	</section>

	<?php if ( $prayer_cats ) : ?>
		<section class="prayers-cats">
			<div class="prayers-cats-grid">
				<?php foreach ( $prayer_cats as $cat ) : ?>
					<a class="prayer-cat-card" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
						<h2 class="prayer-cat-card-title"><?php echo esc_html( $cat->name ); ?></h2>
						<?php if ( $cat->description ) : ?>
							<p class="prayer-cat-card-desc"><?php echo esc_html( wp_trim_words( $cat->description, 22 ) ); ?></p>
						<?php endif; ?>
						<span class="prayer-cat-card-count"><?php printf( esc_html__( '%d תפילות ←', 'tehilim' ), (int) $cat->count ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="prayers-recent">
		<h2><?php esc_html_e( 'תפילות שנוספו לאחרונה', 'tehilim' ); ?></h2>
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
			<?php else : ?>
				<p><?php esc_html_e( 'התפילות יתווספו בקרוב.', 'tehilim' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
</div>

<?php
get_footer();
