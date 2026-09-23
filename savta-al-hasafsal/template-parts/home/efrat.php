<?php
/**
 * 04 · The initiative's leader.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="efrat" class="sv-section sv-person sv-anchor" aria-labelledby="sv-efrat-title">
	<div class="sv-container sv-person__grid" data-reveal>
		<div class="sv-person__media">
			<?php savta_the_window( 'portrait', savta_get( 'efrat_image' ), (string) savta_get( 'efrat_image_alt' ), (string) savta_get( 'efrat_image_alt' ), array( 'class' => 'sv-person__window' ) ); ?>
		</div>

		<div class="sv-person__body">
			<p class="sv-eyebrow"><?php echo esc_html( (string) savta_get( 'efrat_number' ) ); ?></p>
			<h2 id="sv-efrat-title" class="sv-h2 sv-h2--small"><?php echo esc_html( (string) savta_get( 'efrat_title' ) ); ?></h2>
			<p class="sv-p"><?php echo savta_lines( (string) savta_get( 'efrat_p1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<p class="sv-p sv-person__p2"><?php echo savta_lines( (string) savta_get( 'efrat_p2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<p class="sv-person__quote"><?php echo savta_lines( (string) savta_get( 'efrat_quote' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
		</div>
	</div>
</section>
