<?php
/**
 * Title and body of an inner page or post.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;
?>
<article <?php post_class( 'sv-page' ); ?>>
	<div class="sv-page__inner">
		<header class="sv-page__head">
			<h1 class="sv-page__title"><?php the_title(); ?></h1>
		</header>

		<div class="sv-prose">
			<?php
			the_content();

			wp_link_pages(
				array(
					'before' => '<nav class="sv-page__pages" aria-label="' . esc_attr__( 'עמודים', 'savta-al-hasafsal' ) . '">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>
	</div>
</article>
