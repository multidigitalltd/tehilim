<?php
/**
 * 03 · Who the conversation suits.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

$savta_items = savta_rows( 'who_items' );
?>
<section class="sv-section sv-band sv-band--sage sv-who" aria-labelledby="sv-who-title">
	<div id="who" class="sv-container sv-who__grid sv-anchor" data-reveal>
		<div class="sv-who__lead">
			<p class="sv-eyebrow sv-eyebrow--sage"><?php echo esc_html( (string) savta_get( 'who_number' ) ); ?></p>
			<h2 id="sv-who-title" class="sv-h2 sv-who__title"><?php echo savta_lines( (string) savta_get( 'who_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></h2>
			<p class="sv-who__lede"><?php echo savta_lines( (string) savta_get( 'who_lede' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<p class="sv-pill"><?php echo esc_html( (string) savta_get( 'who_pill' ) ); ?></p>

			<div class="sv-who__window-wrap">
				<?php savta_the_window( 'circle', savta_get( 'who_image' ), (string) savta_get( 'who_image_alt' ), (string) savta_get( 'who_image_alt' ), array( 'class' => 'sv-who__window' ) ); ?>
				<svg class="sv-who__steam" viewBox="0 0 120 90" fill="none" stroke="#8F9B78" stroke-width="4" stroke-linecap="round" aria-hidden="true" focusable="false">
					<path class="sv-steam sv-steam--1" d="M34 84 C26 68 42 60 34 44"></path>
					<path class="sv-steam sv-steam--4" d="M60 84 C52 66 68 56 60 36"></path>
					<path class="sv-steam sv-steam--5" d="M86 84 C78 68 94 60 86 44"></path>
				</svg>
			</div>
		</div>

		<div class="sv-who__list-col">
			<ul class="sv-checks">
				<?php foreach ( $savta_items as $savta_item ) : ?>
					<li class="sv-checks__item">
						<svg class="sv-checks__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#4E5A3B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 12L10 18L20 6"></path></svg>
						<span><?php echo esc_html( $savta_item['text'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="sv-who__note"><?php echo savta_lines( (string) savta_get( 'who_note' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
		</div>
	</div>
</section>
