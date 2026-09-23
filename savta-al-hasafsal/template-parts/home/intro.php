<?php
/**
 * Intro: the arched bench frame beside the large statement.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="sv-section sv-intro" aria-labelledby="sv-intro-title">
	<div class="sv-container sv-intro__grid" data-reveal>
		<?php savta_the_window( 'arch', savta_get( 'intro_image' ), (string) savta_get( 'intro_image_alt' ), (string) savta_get( 'intro_image_alt' ) ); ?>

		<div class="sv-intro__text">
			<div class="sv-intro__heart" data-draw aria-hidden="true">
				<svg viewBox="0 0 120 110" fill="none" stroke="#C98573" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" focusable="false">
					<path d="M60 98 C60 98 16 68 16 42 C16 25 28 15 41 15 C50 15 56 20 60 27 C64 20 70 15 79 15 C92 15 104 25 104 42 C104 68 60 98 60 98 Z"></path>
				</svg>
			</div>
			<h2 id="sv-intro-title" class="sv-intro__title"><?php echo savta_lines( (string) savta_get( 'intro_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></h2>
			<p class="sv-intro__body"><?php echo savta_lines( (string) savta_get( 'intro_text' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
		</div>
	</div>
</section>
