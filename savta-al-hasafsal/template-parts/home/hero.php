<?php
/**
 * Hero (the "classic" variant of the design) and the info strip beneath it.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

$savta_btn_text  = (string) savta_get( 'hero_btn_text' );
$savta_btn_url   = (string) savta_get( 'hero_btn_url' );
$savta_link_text = (string) savta_get( 'hero_link_text' );
$savta_link_url  = (string) savta_get( 'hero_link_url' );
$savta_facts     = savta_rows( 'hero_facts' );
?>
<header id="top" class="sv-hero">
	<?php savta_the_falling_layer( 'hero' ); ?>

	<div class="sv-container sv-hero__grid">
		<div class="sv-hero__text" data-reveal>
			<p class="sv-hero__kicker">
				<span class="sv-hero__eyebrow"><?php echo esc_html( (string) savta_get( 'hero_eyebrow' ) ); ?></span>
				<span class="sv-hero__lead"><?php echo esc_html( (string) savta_get( 'hero_lead' ) ); ?></span>
			</p>
			<h1 class="sv-hero__title"><?php echo savta_lines( (string) savta_get( 'hero_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></h1>
			<div class="sv-hero__underline" data-draw aria-hidden="true">
				<svg viewBox="0 0 300 12" fill="none" stroke="#C98573" stroke-width="3" stroke-linecap="round" focusable="false">
					<path d="M3 8 C70 3 150 3 297 6"></path>
				</svg>
			</div>
			<p class="sv-hero__lede"><?php echo savta_lines( (string) savta_get( 'hero_lede' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<div class="sv-hero__actions">
				<?php if ( '' !== $savta_btn_text && '' !== $savta_btn_url ) : ?>
					<a class="sv-btn sv-btn--sage" href="<?php echo esc_url( $savta_btn_url ); ?>"><?php echo esc_html( $savta_btn_text ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $savta_link_text && '' !== $savta_link_url ) : ?>
					<a class="sv-hero__link" href="<?php echo esc_url( $savta_link_url ); ?>"><?php echo esc_html( $savta_link_text ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<div class="sv-hero__logo-col">
			<div class="sv-hero__halo" aria-hidden="true"></div>
			<?php savta_the_falling_layer( 'logo' ); ?>
			<?php
			savta_the_image(
				savta_get( 'hero_logo' ),
				array(
					'alt'           => get_bloginfo( 'name', 'display' ),
					'class'         => 'sv-hero__logo',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				)
			);
			?>
		</div>
	</div>

	<div class="sv-container sv-hero__info">
		<p class="sv-hero__info-text"><?php echo savta_lines( (string) savta_get( 'hero_info' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>

		<?php if ( array() !== $savta_facts ) : ?>
			<dl class="sv-facts">
				<?php foreach ( $savta_facts as $savta_fact ) : ?>
					<div class="sv-facts__item">
						<dt class="sv-facts__label"><?php echo esc_html( $savta_fact['label'] ); ?></dt>
						<dd class="sv-facts__value"><?php echo savta_lines( $savta_fact['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>

		<div class="sv-hero__area">
			<p class="sv-hero__area-label"><?php echo esc_html( (string) savta_get( 'hero_area_label' ) ); ?></p>
			<p class="sv-hero__area-text"><strong><?php echo esc_html( (string) savta_get( 'hero_area_city' ) ); ?></strong> <?php echo esc_html( (string) savta_get( 'hero_area_text' ) ); ?></p>
		</div>
	</div>
</header>
