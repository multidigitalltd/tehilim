<?php
/**
 * 08 · The booking form.
 *
 * Works as a plain POST to admin-post.php; main.js upgrades it to fetch. The
 * calendar is rendered by the script from the slots configured on the page.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

$savta_outcome = savta_form_outcome();
$savta_privacy = savta_privacy_url();
?>
<section id="signup" class="sv-section sv-signup sv-anchor" aria-labelledby="sv-signup-title">
	<div class="sv-container sv-signup__grid">
		<div class="sv-signup__lead" data-reveal>
			<p class="sv-eyebrow"><?php echo esc_html( (string) savta_get( 'signup_number' ) ); ?></p>
			<h2 id="sv-signup-title" class="sv-h2"><?php echo savta_lines( (string) savta_get( 'signup_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></h2>
			<p class="sv-signup__lede"><?php echo savta_lines( (string) savta_get( 'signup_lede' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<p class="sv-signup__note"><?php echo esc_html( (string) savta_get( 'signup_note' ) ); ?></p>
			<?php savta_the_window( 'leaf', savta_get( 'signup_image' ), (string) savta_get( 'signup_image_alt' ), (string) savta_get( 'signup_image_alt' ), array( 'class' => 'sv-signup__window' ) ); ?>
		</div>

		<div class="sv-signup__form-col">
			<?php if ( $savta_outcome && $savta_outcome['ok'] ) : ?>
				<div class="sv-thanks" role="status" tabindex="-1" data-sv-thanks>
					<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#4E5A3B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"></circle><path d="M8 12.5L11 15.5L16 9.5"></path></svg>
					<p class="sv-thanks__title"><?php echo esc_html( (string) savta_get( 'thanks_title' ) ); ?></p>
					<p class="sv-thanks__text"><?php echo esc_html( (string) savta_get( 'thanks_text' ) ); ?></p>
				</div>
			<?php else : ?>
				<form class="sv-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sv-form novalidate>
					<input type="hidden" name="action" value="savta_lead" />
					<input type="hidden" name="savta_nonce" value="<?php echo esc_attr( wp_create_nonce( SAVTA_FORM_NONCE ) ); ?>" data-sv-nonce />
					<input type="hidden" name="savta_t" value="<?php echo esc_attr( (string) time() ); ?>" />
					<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( (string) get_permalink() ); ?>" />
					<?php // Honeypot: hidden from people, filled by bots. ?>
					<p class="sv-form__hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" /></label></p>

					<p class="sv-form__status" role="alert" data-sv-form-status <?php echo $savta_outcome ? '' : 'hidden'; ?>>
						<?php echo $savta_outcome ? esc_html( $savta_outcome['message'] ) : ''; ?>
					</p>

					<div class="sv-form__pair">
						<label class="sv-form__field">
							<span class="sv-form__label"><?php echo esc_html( (string) savta_get( 'form_name' ) ); ?> <span class="sv-form__req" aria-hidden="true">*</span><span class="sv-sr-only"><?php esc_html_e( '(שדה חובה)', 'savta-al-hasafsal' ); ?></span></span>
							<input class="sv-form__input" type="text" name="name" required aria-required="true" autocomplete="given-name" maxlength="80" />
						</label>
						<label class="sv-form__field">
							<span class="sv-form__label"><?php echo esc_html( (string) savta_get( 'form_phone' ) ); ?> <span class="sv-form__req" aria-hidden="true">*</span><span class="sv-sr-only"><?php esc_html_e( '(שדה חובה)', 'savta-al-hasafsal' ); ?></span></span>
							<input class="sv-form__input" type="tel" name="phone" required aria-required="true" autocomplete="tel" inputmode="tel" dir="ltr" />
						</label>
						<label class="sv-form__field">
							<span class="sv-form__label"><?php echo esc_html( (string) savta_get( 'form_city' ) ); ?></span>
							<input class="sv-form__input" type="text" name="city" autocomplete="address-level2" maxlength="80" />
						</label>
						<label class="sv-form__field">
							<span class="sv-form__label"><?php echo esc_html( (string) savta_get( 'form_age' ) ); ?></span>
							<input class="sv-form__input" type="number" name="age" min="16" max="120" inputmode="numeric" />
						</label>
					</div>

					<fieldset class="sv-form__group">
						<legend class="sv-form__label"><?php echo esc_html( (string) savta_get( 'form_contact_label' ) ); ?></legend>
						<div class="sv-form__radios">
							<label class="sv-form__radio"><input type="radio" name="contact" value="phone" checked /><?php echo esc_html( (string) savta_get( 'form_contact_phone' ) ); ?></label>
							<label class="sv-form__radio"><input type="radio" name="contact" value="whatsapp" /><?php echo esc_html( (string) savta_get( 'form_contact_wa' ) ); ?></label>
						</div>
					</fieldset>

					<div class="sv-cal" data-cal>
						<p class="sv-form__label" id="sv-cal-label"><?php echo esc_html( (string) savta_get( 'form_when' ) ); ?></p>
						<button type="button" class="sv-cal__toggle" data-cal-toggle aria-expanded="false" aria-controls="sv-cal-panel" aria-describedby="sv-cal-label">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#C98573" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="16" rx="3"></rect><path d="M3 10H21"></path><path d="M8 3V6"></path><path d="M16 3V6"></path></svg>
							<span class="sv-cal__value" data-cal-label><?php echo esc_html( (string) savta_get( 'form_slot_empty' ) ); ?></span>
							<span class="sv-cal__hint" data-cal-toggle-text><?php echo esc_html( (string) savta_get( 'form_cal_open' ) ); ?></span>
						</button>
						<div class="sv-cal__panel" id="sv-cal-panel" data-cal-panel hidden>
							<p class="sv-cal__intro"><?php echo savta_lines( (string) savta_get( 'form_cal_intro' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
							<div class="sv-cal__days" data-cal-days role="group" aria-labelledby="sv-cal-label"></div>
							<p class="sv-cal__none"><?php echo esc_html( (string) savta_get( 'form_cal_none' ) ); ?></p>
						</div>
						<?php // Without JavaScript the picker cannot render, so the slot stays optional and empty. ?>
						<input type="hidden" name="slot" value="" data-cal-input />
						<p class="sv-sr-only" role="status" aria-live="polite" data-cal-status></p>
					</div>

					<label class="sv-form__field">
						<span class="sv-form__label"><?php echo esc_html( (string) savta_get( 'form_topic' ) ); ?></span>
						<textarea class="sv-form__input sv-form__textarea" name="topic" rows="3" maxlength="2000"></textarea>
					</label>

					<label class="sv-form__consent">
						<input type="checkbox" name="consent" value="1" required aria-required="true" />
						<span>
							<?php echo esc_html( (string) savta_get( 'form_consent' ) ); ?>
							<?php if ( '' !== $savta_privacy ) : ?>
								<a href="<?php echo esc_url( $savta_privacy ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'למדיניות הפרטיות', 'savta-al-hasafsal' ); ?><span class="sv-sr-only"> <?php esc_html_e( '(נפתח בכרטיסייה חדשה)', 'savta-al-hasafsal' ); ?></span></a>
							<?php endif; ?>
						</span>
					</label>

					<button type="submit" class="sv-btn sv-btn--rose sv-form__submit" data-sv-submit><?php echo esc_html( (string) savta_get( 'form_submit' ) ); ?></button>
				</form>

				<template data-sv-thanks-template>
					<div class="sv-thanks" role="status" tabindex="-1">
						<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#4E5A3B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"></circle><path d="M8 12.5L11 15.5L16 9.5"></path></svg>
						<p class="sv-thanks__title"><?php echo esc_html( (string) savta_get( 'thanks_title' ) ); ?></p>
						<p class="sv-thanks__text"><?php echo esc_html( (string) savta_get( 'thanks_text' ) ); ?></p>
					</div>
				</template>
			<?php endif; ?>
		</div>
	</div>
</section>
