<?php
/**
 * 07 · FAQ accordion. Real buttons with aria-expanded; the answers are in the
 * DOM and readable without JavaScript.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

$savta_faqs = savta_rows( 'faq_items' );
?>
<section class="sv-section sv-band sv-band--sand sv-faq" aria-labelledby="sv-faq-title">
	<div id="faq" class="sv-container sv-faq__grid sv-anchor" data-reveal>
		<div class="sv-faq__lead">
			<p class="sv-eyebrow"><?php echo esc_html( (string) savta_get( 'faq_number' ) ); ?></p>
			<h2 id="sv-faq-title" class="sv-h2"><?php echo savta_lines( (string) savta_get( 'faq_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></h2>
			<p class="sv-faq__lede"><?php echo savta_lines( (string) savta_get( 'faq_lede' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
		</div>

		<div class="sv-faq__list">
			<?php foreach ( $savta_faqs as $savta_index => $savta_faq ) : ?>
				<?php $savta_id = 'sv-faq-' . ( $savta_index + 1 ); ?>
				<div class="sv-faq__item" data-faq>
					<h3 class="sv-faq__q">
						<button type="button" class="sv-faq__btn" data-faq-q aria-expanded="false" aria-controls="<?php echo esc_attr( $savta_id ); ?>" id="<?php echo esc_attr( $savta_id ); ?>-btn">
							<span class="sv-faq__mark" data-faq-mark aria-hidden="true">+</span>
							<span class="sv-faq__label"><?php echo esc_html( $savta_faq['question'] ); ?></span>
						</button>
					</h3>
					<div class="sv-faq__a" id="<?php echo esc_attr( $savta_id ); ?>" data-faq-a role="region" aria-labelledby="<?php echo esc_attr( $savta_id ); ?>-btn">
						<p><?php echo savta_lines( $savta_faq['answer'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
