<?php
/**
 * 01 · What "Savta al HaSafsal" is.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="sv-section sv-band sv-band--sand sv-about" aria-labelledby="sv-about-title">
	<div id="about" class="sv-container sv-about__grid sv-anchor" data-reveal>
		<div class="sv-about__lead">
			<p class="sv-eyebrow"><?php echo esc_html( (string) savta_get( 'about_number' ) ); ?></p>
			<h2 id="sv-about-title" class="sv-h2"><?php echo savta_lines( (string) savta_get( 'about_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></h2>
			<?php savta_the_window( 'arch-down', savta_get( 'about_image' ), (string) savta_get( 'about_image_alt' ), (string) savta_get( 'about_image_alt' ), array( 'class' => 'sv-about__window' ) ); ?>
		</div>

		<div class="sv-about__body">
			<p class="sv-p"><?php echo savta_lines( (string) savta_get( 'about_p1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<blockquote class="sv-about__quote">
				<p><?php echo savta_lines( (string) savta_get( 'about_quote' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			</blockquote>
			<p class="sv-p sv-about__p2"><?php echo savta_lines( (string) savta_get( 'about_p2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>

			<div class="sv-about__goal">
				<div class="sv-about__cup" data-draw aria-hidden="true">
					<svg viewBox="0 0 120 120" fill="none" stroke="#5F6B49" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" focusable="false">
						<path d="M30 50 H86 C86 78 74 92 58 92 C42 92 30 78 30 50 Z"></path>
						<path d="M86 58 C102 58 102 80 86 80"></path>
						<path d="M18 102 H98" stroke="#C98573"></path>
						<path class="sv-steam sv-steam--1" d="M44 40 C38 31 50 27 44 17" stroke="#C98573" stroke-width="3.4"></path>
						<path class="sv-steam sv-steam--2" d="M58 40 C52 30 64 25 58 13" stroke="#C98573" stroke-width="3.4"></path>
						<path class="sv-steam sv-steam--3" d="M72 40 C66 31 78 27 72 17" stroke="#C98573" stroke-width="3.4"></path>
					</svg>
				</div>
				<p class="sv-about__goal-text"><?php echo savta_lines( (string) savta_get( 'about_goal' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			</div>
		</div>
	</div>
</section>
