<?php
/**
 * Default chapter preview — the next free chapter, shown read-only so the
 * campaign page always presents a Tehillim chapter to read.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var int    $campaign_id Campaign id.
 * @var int    $chapter     Chapter number.
 * @var string $text        Sanitised chapter HTML ('' when not stored).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TCM\Support\Hebrew;

$chapter_label = Hebrew::chapter_label( (int) $chapter );
?>
<section class="tcm-card tcm-reader tcm-chapter-preview" id="tcm-read" aria-labelledby="tcm-preview-heading">
	<p class="tcm-eyebrow">✦ <?php esc_html_e( 'Chapter to say now', 'tehillim-campaign-manager' ); ?></p>
	<h3 id="tcm-preview-heading">
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
