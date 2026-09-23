<?php
/**
 * 06 · The sayings, as a grid of cards in three rotating tints.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

$savta_quotes = savta_rows( 'quotes_items' );
?>
<section id="quotes" class="sv-section sv-quotes sv-anchor" aria-labelledby="sv-quotes-title">
	<div class="sv-container" data-reveal>
		<div class="sv-quotes__head">
			<div>
				<p class="sv-eyebrow"><?php echo esc_html( (string) savta_get( 'quotes_number' ) ); ?></p>
				<h2 id="sv-quotes-title" class="sv-h2"><?php echo savta_lines( (string) savta_get( 'quotes_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></h2>
			</div>
			<p class="sv-quotes__lede"><?php echo savta_lines( (string) savta_get( 'quotes_lede' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
		</div>

		<ul class="sv-cards">
			<?php foreach ( $savta_quotes as $savta_index => $savta_quote ) : ?>
				<li class="sv-card sv-card--<?php echo esc_attr( (string) ( $savta_index % 3 ) ); ?>">
					<p class="sv-card__text"><?php echo esc_html( $savta_quote['text'] ); ?></p>
					<?php savta_the_heart_icon(); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
