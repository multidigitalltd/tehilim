<?php
/**
 * Standalone Tehillim reader (no campaign), chapter-by-chapter.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var int    $chapter Current chapter number.
 * @var int    $prev    Previous chapter (wraps).
 * @var int    $next    Next chapter (wraps).
 * @var string $text    Sanitised chapter HTML ('' when not stored).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TCM\Support\Hebrew;

$chapter   = (int) $chapter;
$label     = Hebrew::chapter_label( $chapter );
$select_id = 'tcm-reader-select';
$prev_url  = esc_url( add_query_arg( 'tcm_ch', (int) $prev ) ) . '#tcm-reader';
$next_url  = esc_url( add_query_arg( 'tcm_ch', (int) $next ) ) . '#tcm-reader';
?>
<section class="tcm-card tcm-reader tcm-chapter-preview" id="tcm-reader" aria-labelledby="tcm-reader-heading">
	<div class="tcm-reader-bar">
		<a class="tcm-reader-next" href="<?php echo $prev_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">‹ <?php esc_html_e( 'Previous', 'tehillim-campaign-manager' ); ?></a>
		<div class="tcm-reader-pick">
			<label class="screen-reader-text" for="<?php echo esc_attr( $select_id ); ?>"><?php esc_html_e( 'Choose chapter', 'tehillim-campaign-manager' ); ?></label>
			<select id="<?php echo esc_attr( $select_id ); ?>" class="tcm-chapter-select" data-tcm-chapter-select>
				<?php for ( $n = 1; $n <= 150; $n++ ) : ?>
					<option value="<?php echo esc_attr( $n ); ?>" <?php selected( $n, $chapter ); ?>>
						<?php
						printf(
							/* translators: %s: Hebrew chapter label. */
							esc_html__( 'Tehillim %s', 'tehillim-campaign-manager' ),
							esc_html( Hebrew::chapter_label( $n ) )
						);
						?>
					</option>
				<?php endfor; ?>
			</select>
		</div>
		<a class="tcm-reader-next" href="<?php echo $next_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php esc_html_e( 'Next', 'tehillim-campaign-manager' ); ?> ›</a>
	</div>

	<h3 id="tcm-reader-heading" class="tcm-reader-title">
		<?php
		printf(
			/* translators: %s: Hebrew chapter label. */
			esc_html__( 'Tehillim %s', 'tehillim-campaign-manager' ),
			esc_html( $label )
		);
		?>
	</h3>

	<?php if ( $text ) : ?>
		<div class="tcm-chapter-text"><?php echo wp_kses_post( $text ); ?></div>
	<?php else : ?>
		<div class="tcm-notice"><?php esc_html_e( 'This chapter has no text yet. It can be added from the admin under "Tehillim chapters".', 'tehillim-campaign-manager' ); ?></div>
	<?php endif; ?>
</section>
