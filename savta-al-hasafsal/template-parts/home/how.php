<?php
/**
 * 02 · How it works: four steps along a line that fills as you scroll.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

$savta_steps = savta_rows( 'how_steps' );
?>
<section id="how-it-works" class="sv-section sv-how sv-anchor" aria-labelledby="sv-how-title">
	<div class="sv-container sv-container--narrow">
		<p class="sv-eyebrow"><?php echo esc_html( (string) savta_get( 'how_number' ) ); ?></p>
		<h2 id="sv-how-title" class="sv-h2 sv-how__title"><?php echo esc_html( (string) savta_get( 'how_title' ) ); ?></h2>

		<div class="sv-steps" data-progress-track>
			<div class="sv-steps__track" aria-hidden="true"></div>
			<div class="sv-steps__fill" data-progress-fill aria-hidden="true"></div>

			<ol class="sv-steps__list">
			<?php foreach ( $savta_steps as $savta_index => $savta_step ) : ?>
				<li class="sv-step" data-reveal data-progress-step>
					<span class="sv-step__dot" data-progress-dot aria-hidden="true"></span>
					<p class="sv-step__num" aria-hidden="true"><?php echo esc_html( (string) ( $savta_index + 1 ) ); ?></p>
					<div class="sv-step__body">
						<h3 class="sv-step__title"><?php echo esc_html( $savta_step['title'] ); ?></h3>
						<p class="sv-step__text"><?php echo savta_lines( $savta_step['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
					</div>
				</li>
			<?php endforeach; ?>
			</ol>
		</div>
		<div class="sv-steps__end" aria-hidden="true"></div>
	</div>
</section>
