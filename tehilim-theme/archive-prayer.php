<?php
/**
 * Prayers landing — /prayers/ (CPT archive).
 * SEO hub with category cards + free-text search over all prayers.
 */
get_header();

/**
 * Simple SVG icon per prayer category slug (inline, theme-styled).
 */
function tehilim_prayer_cat_icon( $slug ) {
	$icons = array(
		'tefilot-parnasa'     => '<path d="M12 3v18M8 7h5.5a2.5 2.5 0 0 1 0 5H9a2.5 2.5 0 0 0 0 5h6"></path>',
		'tefilot-refua'       => '<path d="M12 21s-7.5-4.7-10-9.3C.4 8.6 2 5 5.5 5c2 0 3.4 1.1 4.5 2.6C11 6.1 12.5 5 14.5 5 18 5 19.6 8.6 22 11.7 19.5 16.3 12 21 12 21z"></path>',
		'tefilot-zivug'       => '<circle cx="8" cy="8" r="4"></circle><circle cx="16" cy="8" r="4"></circle><path d="M4 21c0-3 1.8-5 4-5M20 21c0-3-1.8-5-4-5"></path>',
		'tefilot-shalom-bait' => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"></path>',
		'tefilot-yeladim'     => '<circle cx="12" cy="7" r="3"></circle><path d="M6 21c0-4 2.7-7 6-7s6 3 6 7"></path>',
		'tefilot-hodaya'      => '<path d="M12 3l2.4 5.6L20 9l-4.4 4 1.3 6L12 16l-4.9 3 1.3-6L4 9l5.6-.4z"></path>',
		'tefilot-shmira'      => '<path d="M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6z"></path>',
		'tefilot-tzara'       => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path>',
		'tefilot-hatzlacha'   => '<path d="M4 18l5-5 3 3 7-8M16 8h4v4"></path>',
		'tefilot-yoledet'     => '<circle cx="12" cy="8" r="4"></circle><path d="M12 12c-3 0-5 2.5-5 6h10c0-3.5-2-6-5-6z"></path>',
		'segulot'             => '<path d="M12 2l2.4 5.6L20 8l-4.4 4 1.3 6L12 15l-4.9 3 1.3-6L4 8l5.6-.4z"></path>',
		'brachot'             => '<path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5M12 6v13"></path>',
		'tefilot-klaliyot'    => '<path d="M12 2l3 3-3 3-3-3zM4 12l3 3 3-3-3-3zM20 12l-3-3-3 3 3 3zM12 22l3-3-3-3-3 3z"></path>',
	);
	$path = isset( $icons[ $slug ] ) ? $icons[ $slug ] : '<path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5"></path>';
	return '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

$search_q = isset( $_GET['pq'] ) ? sanitize_text_field( wp_unslash( $_GET['pq'] ) ) : '';

$prayer_cats = get_terms( array(
	'taxonomy'   => 'prayer_cat',
	'hide_empty' => false,
	'parent'     => 0,
	'orderby'    => 'count',
	'order'      => 'DESC',
) );
if ( is_wp_error( $prayer_cats ) ) {
	$prayer_cats = array();
}
?>

<div class="prayers-page page-anim">

	<section class="prayers-hero">
		<h1><?php esc_html_e( 'תפילות — אוסף תפילות, ברכות ופרקי תהילים', 'tehilim' ); ?></h1>
		<p><?php esc_html_e( 'ליקוט תפילות ופרקי תהילים לפרנסה, לרפואה, לזיווג, לשלום בית ולהצלחת הילדים, לצד ברכות יום־יום וסגולות. בחרו קטגוריה או חפשו תפילה.', 'tehilim' ); ?></p>

		<form class="prayers-search" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'prayer' ) ); ?>" role="search">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
			<input type="search" name="pq" value="<?php echo esc_attr( $search_q ); ?>" placeholder="<?php esc_attr_e( 'חיפוש תפילה — לרפואה, לפרנסה, פרק תהילים…', 'tehilim' ); ?>" aria-label="<?php esc_attr_e( 'חיפוש תפילות', 'tehilim' ); ?>">
			<button type="submit"><?php esc_html_e( 'חיפוש', 'tehilim' ); ?></button>
		</form>
	</section>

	<?php if ( '' !== $search_q ) : ?>
		<?php
		$results = new WP_Query( array(
			'post_type'      => 'prayer',
			'post_status'    => 'publish',
			's'              => $search_q,
			'posts_per_page' => 40,
		) );
		?>
		<section class="prayers-results">
			<h2><?php printf( esc_html__( 'תוצאות חיפוש עבור "%s"', 'tehilim' ), esc_html( $search_q ) ); ?></h2>
			<?php if ( $results->have_posts() ) : ?>
				<div class="prayers-list">
					<?php while ( $results->have_posts() ) : $results->the_post(); ?>
						<a class="prayer-list-item" href="<?php the_permalink(); ?>">
							<span class="prayer-list-item-title"><?php the_title(); ?></span>
							<?php if ( has_excerpt() ) : ?>
								<span class="prayer-list-item-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></span>
							<?php endif; ?>
						</a>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			<?php else : ?>
				<p class="prayers-noresults"><?php esc_html_e( 'לא נמצאו תפילות התואמות לחיפוש. נסו מילה אחרת, או עיינו בקטגוריות שלמטה.', 'tehilim' ); ?></p>
			<?php endif; ?>
			<div class="prayers-cta">
				<a class="btn-account-secondary" href="<?php echo esc_url( get_post_type_archive_link( 'prayer' ) ); ?>"><?php esc_html_e( 'חזרה לכל הקטגוריות', 'tehilim' ); ?></a>
			</div>
		</section>
	<?php else : ?>

		<?php if ( $prayer_cats ) : ?>
			<section class="prayers-cats">
				<div class="prayers-cats-grid">
					<?php foreach ( $prayer_cats as $cat ) : ?>
						<a class="prayer-cat-card" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
							<span class="prayer-cat-icon"><?php echo tehilim_prayer_cat_icon( $cat->slug ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<h2 class="prayer-cat-card-title"><?php echo esc_html( $cat->name ); ?></h2>
							<?php if ( $cat->description ) : ?>
								<p class="prayer-cat-card-desc"><?php echo esc_html( wp_trim_words( $cat->description, 20 ) ); ?></p>
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

	<?php endif; ?>
</div>

<?php
get_footer();
