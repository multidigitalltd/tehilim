<?php
/**
 * 05 · Our first "savta".
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="first-savta" class="sv-section sv-band sv-band--blush sv-first sv-anchor" aria-labelledby="sv-first-title">
	<div class="sv-container sv-first__grid" data-reveal>
		<div class="sv-first__body">
			<p class="sv-eyebrow"><?php echo esc_html( (string) savta_get( 'first_number' ) ); ?></p>
			<h2 id="sv-first-title" class="sv-h2 sv-h2--small"><?php echo esc_html( (string) savta_get( 'first_title' ) ); ?></h2>
			<p class="sv-p"><?php echo savta_lines( (string) savta_get( 'first_p1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<p class="sv-p"><?php echo savta_lines( (string) savta_get( 'first_p2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<p class="sv-p sv-first__p3"><?php echo savta_lines( (string) savta_get( 'first_p3' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<p class="sv-first__quote"><?php echo savta_lines( (string) savta_get( 'first_quote' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
		</div>

		<div class="sv-first__media">
			<?php savta_the_window( 'side', savta_get( 'first_image' ), (string) savta_get( 'first_image_alt' ), (string) savta_get( 'first_placeholder' ), array( 'class' => 'sv-first__window' ) ); ?>
		</div>
	</div>
</section>
