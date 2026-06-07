<?php
/**
 * Campaign share card (right rail) — WhatsApp + copy link.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $permalink Campaign URL.
 * @var string $title     Campaign title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wa = 'https://wa.me/?text=' . rawurlencode( $title . "\n" . $permalink );
?>
<section class="tcm-card tcm-share-card" aria-label="<?php esc_attr_e( 'Share', 'tehillim-campaign-manager' ); ?>">
	<h3><span aria-hidden="true">📤</span> <?php esc_html_e( 'Share', 'tehillim-campaign-manager' ); ?></h3>
	<div class="tcm-share-actions tcm-share-actions--stack">
		<a class="tcm-btn is-block" target="_blank" rel="noopener" href="<?php echo esc_url( $wa ); ?>"><?php esc_html_e( 'Share on WhatsApp', 'tehillim-campaign-manager' ); ?></a>
		<button type="button" class="tcm-btn is-secondary is-block" data-tcm-copy="<?php echo esc_attr( $permalink ); ?>"><?php esc_html_e( 'Copy link', 'tehillim-campaign-manager' ); ?></button>
	</div>
</section>
