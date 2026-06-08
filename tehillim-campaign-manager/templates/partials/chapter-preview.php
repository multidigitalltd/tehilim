<?php
/**
 * Default chapter reader - the campaign-page centrepiece (Lovable layout):
 * a chapter selector, the chapter title, and the verse text shown read-only
 * so a Tehillim chapter is always presented. "Take this chapter" joins.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var int    $campaign_id Campaign id.
 * @var int    $chapter     Chapter number.
 * @var int    $next        Next chapter number (wraps 150 -> 1).
 * @var string $text        Sanitised chapter HTML ('' when not stored).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TCM\Support\Hebrew;

$chapter       = (int) $chapter;
$next          = isset( $next ) ? (int) $next : ( $chapter >= 150 ? 1 : $chapter + 1 );
$chapter_label = Hebrew::chapter_label( $chapter );
$select_id     = 'tcm-ch-select-' . (int) $campaign_id;
?>
<section class="tcm-card tcm-reader tcm-chapter-preview" id="tcm-read" aria-labelledby="tcm-preview-heading">
	<div class="tcm-reader-bar">
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
		<a class="tcm-reader-next" href="<?php echo esc_url( add_query_arg( 'tcm_ch', $next ) ); ?>#tcm-read"><?php esc_html_e( 'Next chapter', 'tehillim-campaign-manager' ); ?> ›</a>
	</div>

	<p class="tcm-eyebrow tcm-reader-eyebrow">✦ <?php esc_html_e( 'Chapter to say now', 'tehillim-campaign-manager' ); ?></p>
	<h3 id="tcm-preview-heading" class="tcm-reader-title">
		<?php
		printf(
			/* translators: %s: Hebrew chapter label. */
			esc_html__( 'Tehillim %s', 'tehillim-campaign-manager' ),
			esc_html( $chapter_label )
		);
		?>
	</h3>

	<?php if ( $text ) : ?>
		<div class="tcm-chapter-text"><?php echo wp_kses_post( $text ); ?></div>
	<?php else : ?>
		<div class="tcm-notice"><?php esc_html_e( 'This chapter has no text yet. It can be added from the admin under "Tehillim chapters".', 'tehillim-campaign-manager' ); ?></div>
	<?php endif; ?>

	<div class="tcm-actions">
		<a class="tcm-btn" href="#tcm-join"><?php esc_html_e( 'Take this chapter', 'tehillim-campaign-manager' ); ?></a>
	</div>
</section>
