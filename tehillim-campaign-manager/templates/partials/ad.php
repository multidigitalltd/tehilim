<?php
/**
 * A single advertisement (commercial zones only — never in the reader).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $image     Image URL.
 * @var string $title     Ad title (used as alt text).
 * @var string $click_url Tracking click URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $image ) || empty( $click_url ) ) {
	return;
}
?>
<div class="tcm-ad">
	<span class="tcm-ad__label"><?php esc_html_e( 'Sponsored', 'tehillim-campaign-manager' ); ?></span>
	<a href="<?php echo esc_url( $click_url ); ?>" rel="sponsored noopener" target="_blank">
		<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async">
	</a>
</div>
