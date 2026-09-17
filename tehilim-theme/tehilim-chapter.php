<?php
/**
 * Tehilim reader — a single chapter (/tehilim/{n}).
 */
$chapter = intval( get_query_var( 'tehilim_chapter' ) );
$chapter = max( 1, min( 150, $chapter ) );
$gem     = tehilim_hebrew_numeral( $chapter );

get_header();
?>

<div class="tehilim-reader-page prayer-single page-anim">

	<nav class="prayer-breadcrumb" aria-label="<?php esc_attr_e( 'ניווט', 'tehilim' ); ?>">
		<a href="<?php echo esc_url( home_url( '/tehilim/' ) ); ?>"><?php esc_html_e( 'ספר תהילים', 'tehilim' ); ?></a>
		<span aria-hidden="true">›</span>
		<span><?php printf( esc_html__( 'פרק %s', 'tehilim' ), esc_html( $gem ) ); ?></span>
	</nav>

	<article class="prayer-article tehilim-chapter-article">
		<h1 class="prayer-title"><?php printf( esc_html__( 'תהילים · פרק %s', 'tehilim' ), esc_html( $gem ) ); ?></h1>

		<div class="prayer-content">
			<?php
			echo tehilim_render_psalms_chapter_html( $chapter ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>

		<nav class="tehilim-chapter-nav" aria-label="<?php esc_attr_e( 'ניווט בין פרקים', 'tehilim' ); ?>">
			<?php if ( $chapter > 1 ) : ?>
				<a class="btn-account-view" href="<?php echo esc_url( home_url( '/tehilim/' . ( $chapter - 1 ) . '/' ) ); ?>">
					<?php printf( esc_html__( '→ פרק %s', 'tehilim' ), esc_html( tehilim_hebrew_numeral( $chapter - 1 ) ) ); ?>
				</a>
			<?php else : ?><span></span><?php endif; ?>

			<a class="btn-account-view" href="<?php echo esc_url( home_url( '/tehilim/' ) ); ?>"><?php esc_html_e( 'כל הפרקים', 'tehilim' ); ?></a>

			<?php if ( $chapter < 150 ) : ?>
				<a class="btn-account-view" href="<?php echo esc_url( home_url( '/tehilim/' . ( $chapter + 1 ) . '/' ) ); ?>">
					<?php printf( esc_html__( 'פרק %s ←', 'tehilim' ), esc_html( tehilim_hebrew_numeral( $chapter + 1 ) ) ); ?>
				</a>
			<?php else : ?><span></span><?php endif; ?>
		</nav>

		<div class="tehilim-chapter-share">
			<button class="btn-account-share btn-share-modal" data-share-url="<?php echo esc_url( home_url( '/tehilim/' . $chapter . '/' ) ); ?>" data-share-text="<?php printf( esc_attr__( 'תהילים פרק %s', 'tehilim' ), esc_attr( $gem ) ); ?>">
				<?php esc_html_e( 'שיתוף הפרק', 'tehilim' ); ?>
			</button>
			<a class="btn-account-view" href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>"><?php esc_html_e( 'אמרו תהילים לזכות', 'tehilim' ); ?></a>
		</div>
	</article>
</div>

<?php
get_footer();
