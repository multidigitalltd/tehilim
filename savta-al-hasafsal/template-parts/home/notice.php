<?php
/**
 * "Good to know": the narrow disclaimer block.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="notice" class="sv-section sv-notice sv-anchor" aria-labelledby="sv-notice-title">
	<div class="sv-container sv-container--narrow sv-notice__inner" data-reveal>
		<svg class="sv-notice__icon" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#4E5A3B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3C12 3 5 8 5 13.5C5 17.6 8.1 21 12 21C15.9 21 19 17.6 19 13.5C19 8 12 3 12 3Z"></path><path d="M12 21C12 16 12 12 12 9"></path></svg>
		<div class="sv-notice__body">
			<h2 id="sv-notice-title" class="sv-notice__title"><?php echo esc_html( (string) savta_get( 'notice_title' ) ); ?></h2>
			<?php foreach ( array( 'notice_p1', 'notice_p2', 'notice_p3' ) as $savta_field ) : ?>
				<?php $savta_text = (string) savta_get( $savta_field ); ?>
				<?php if ( '' !== trim( $savta_text ) ) : ?>
					<p class="sv-notice__p"><?php echo savta_lines( $savta_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
