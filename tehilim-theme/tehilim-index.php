<?php
/**
 * Tehilim reader — index of all 150 chapters (/tehilim/).
 */
get_header();
?>

<div class="tehilim-reader-page page-anim">

	<section class="prayers-hero">
		<h1><?php esc_html_e( 'ספר תהילים המלא — כל 150 הפרקים', 'tehilim' ); ?></h1>
		<p><?php esc_html_e( 'קראו את ספר תהילים המלא והמנוקד באינטרנט — כל 150 הפרקים, פרק אחר פרק. בחרו פרק להתחיל, או אמרו תהילים לפי הצורך.', 'tehilim' ); ?></p>
	</section>

	<section class="tehilim-topics">
		<h2><?php esc_html_e( 'תהילים לפי נושא', 'tehilim' ); ?></h2>
		<div class="tehilim-topics-grid">
			<?php foreach ( tehilim_reader_topics() as $topic => $chapters ) : ?>
				<div class="tehilim-topic">
					<span class="tehilim-topic-label"><?php echo esc_html( 'תהילים ' . $topic ); ?></span>
					<span class="tehilim-topic-chapters">
						<?php
						$links = array();
						foreach ( $chapters as $ch ) {
							$gem     = tehilim_hebrew_numeral( $ch );
							$links[] = '<a href="' . esc_url( home_url( '/tehilim/' . $ch . '/' ) ) . '">' . esc_html( $gem ) . '</a>';
						}
						echo implode( ' · ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="tehilim-name-cta">
			<a class="btn-create-primary" href="<?php echo esc_url( home_url( '/tehilim-lefi-shem/' ) ); ?>"><?php esc_html_e( 'תהילים לפי שם →', 'tehilim' ); ?></a>
		</p>
	</section>

	<section class="tehilim-all">
		<h2><?php esc_html_e( 'כל פרקי התהילים', 'tehilim' ); ?></h2>
		<div class="tehilim-grid">
			<?php for ( $n = 1; $n <= 150; $n++ ) : ?>
				<a class="tehilim-grid-cell" href="<?php echo esc_url( home_url( '/tehilim/' . $n . '/' ) ); ?>" title="<?php printf( esc_attr__( 'תהילים פרק %s', 'tehilim' ), esc_attr( tehilim_hebrew_numeral( $n ) ) ); ?>">
					<?php echo esc_html( tehilim_hebrew_numeral( $n ) ); ?>
				</a>
			<?php endfor; ?>
		</div>
	</section>
</div>

<?php
get_footer();
