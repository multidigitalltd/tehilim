<?php
/**
 * Site footer.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

$savta_name      = (string) savta_option( 'savta_footer_name' );
$savta_blurb     = (string) savta_option( 'savta_footer_blurb' );
$savta_lead      = (string) savta_option( 'savta_footer_lead' );
$savta_note      = (string) savta_option( 'savta_footer_note' );
$savta_copyright = (string) savta_option( 'savta_footer_copyright' );
$savta_a11y_id   = (int) savta_option( 'savta_a11y_page' );
$savta_privacy   = (int) savta_option( 'savta_privacy_page' );

$savta_credit_on   = (bool) savta_option( 'savta_credit_enable' );
$savta_credit_text = (string) savta_option( 'savta_credit_text' );
$savta_credit_name = (string) savta_option( 'savta_credit_name' );
$savta_credit_url  = (string) savta_option( 'savta_credit_url' );
?>
</main>

<footer class="sv-footer">
	<div class="sv-container sv-footer__grid">
		<div>
			<p class="sv-footer__name"><?php echo esc_html( $savta_name ); ?></p>
			<p class="sv-footer__blurb"><?php echo savta_lines( $savta_blurb ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
		</div>
		<div class="sv-footer__side">
			<?php if ( '' !== $savta_lead ) : ?>
				<p class="sv-footer__lead"><?php echo esc_html( $savta_lead ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $savta_note ) : ?>
				<p class="sv-footer__note"><?php echo savta_lines( $savta_note ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in savta_lines(). ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="sv-container sv-footer__bar">
		<span class="sv-footer__copy"><?php echo esc_html( $savta_copyright ); ?></span>

		<div class="sv-footer__legal">
			<?php if ( $savta_a11y_id > 0 ) : ?>
				<a href="<?php echo esc_url( (string) get_permalink( $savta_a11y_id ) ); ?>"><?php echo esc_html( get_the_title( $savta_a11y_id ) ); ?></a>
			<?php endif; ?>

			<?php if ( $savta_privacy > 0 ) : ?>
				<a href="<?php echo esc_url( (string) get_permalink( $savta_privacy ) ); ?>"><?php echo esc_html( get_the_title( $savta_privacy ) ); ?></a>
			<?php endif; ?>

			<?php if ( $savta_credit_on && '' !== $savta_credit_name ) : ?>
				<?php // A Latin credit keeps its own word order inside the RTL footer. ?>
				<p class="sv-credit" <?php echo savta_is_ltr_text( $savta_credit_text . $savta_credit_name ) ? 'dir="ltr"' : ''; ?>>
					<?php echo esc_html( $savta_credit_text ); ?>
					<?php if ( '' !== $savta_credit_url ) : ?>
						<a class="sv-credit__link" href="<?php echo esc_url( $savta_credit_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $savta_credit_name ); ?>
							<span class="sv-sr-only"><?php esc_html_e( '(נפתח בכרטיסייה חדשה)', 'savta-al-hasafsal' ); ?></span>
						</a>
					<?php else : ?>
						<span class="sv-credit__link"><?php echo esc_html( $savta_credit_name ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
