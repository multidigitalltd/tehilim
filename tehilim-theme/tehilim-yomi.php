<?php
/**
 * תהילים יומי — /tehilim-yomi/
 * Today's portion (by Hebrew day of month when available) + the full
 * monthly division table. Public-domain siddur division.
 */
get_header();

$division = tehilim_reader_monthly_division();
$today    = tehilim_reader_hebrew_day(); // 1–30 or null

/**
 * Render a "from–to chapters" range as linked Hebrew numerals.
 */
$range_links = function( $from, $to ) {
	$out = array();
	for ( $c = $from; $c <= $to; $c++ ) {
		$out[] = '<a href="' . esc_url( home_url( '/tehilim/' . $c . '/' ) ) . '">' . esc_html( tehilim_hebrew_numeral( $c ) ) . '</a>';
	}
	return implode( ' · ', $out );
};
?>

<div class="tehilim-reader-page page-anim">

	<section class="prayers-hero">
		<h1><?php esc_html_e( 'תהילים יומי — הפרקים לכל יום בחודש', 'tehilim' ); ?></h1>
		<p><?php esc_html_e( 'חלוקת ספר תהילים לימי החודש. אמרו את הפרקים של היום, או בחרו יום מהטבלה. כך משלימים את כל ספר תהילים מדי חודש.', 'tehilim' ); ?></p>
	</section>

	<?php if ( $today && isset( $division[ $today ] ) ) : ?>
		<?php list( $from, $to, $note ) = $division[ $today ]; ?>
		<section class="tehilim-yomi-today">
			<div class="tehilim-yomi-today-head">
				<span class="tehilim-yomi-badge"><?php esc_html_e( 'הפרקים של היום', 'tehilim' ); ?></span>
				<span class="tehilim-yomi-day"><?php printf( esc_html__( 'יום %s בחודש', 'tehilim' ), esc_html( tehilim_hebrew_numeral( $today ) ) ); ?></span>
			</div>
			<div class="tehilim-yomi-today-chapters">
				<?php echo $range_links( $from, $to ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<?php if ( $note ) : ?>
				<div class="tehilim-yomi-note"><?php echo esc_html( $note ); ?></div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<section class="tehilim-yomi-table-wrap">
		<h2><?php esc_html_e( 'חלוקת תהילים לימי החודש', 'tehilim' ); ?></h2>
		<div class="tehilim-yomi-table">
			<?php foreach ( $division as $day => $row ) : ?>
				<?php list( $from, $to, $note ) = $row; ?>
				<div class="tehilim-yomi-row<?php echo ( $today === $day ) ? ' is-today' : ''; ?>">
					<div class="tehilim-yomi-row-day"><?php printf( esc_html__( 'יום %s', 'tehilim' ), esc_html( tehilim_hebrew_numeral( $day ) ) ); ?></div>
					<div class="tehilim-yomi-row-chapters">
						<?php echo $range_links( $from, $to ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $note ) : ?><span class="tehilim-yomi-row-note"><?php echo esc_html( $note ); ?></span><?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<div class="prayers-cta">
		<a class="btn-account-secondary" href="<?php echo esc_url( home_url( '/tehilim/' ) ); ?>"><?php esc_html_e( 'לספר תהילים המלא', 'tehilim' ); ?></a>
	</div>
</div>

<?php
get_footer();
