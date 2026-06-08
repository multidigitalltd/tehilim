<?php
/**
 * Single prayer / segula (sacred space - no ads on the text).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $title   Prayer title.
 * @var string $content Prayer content HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tcm-wrap">
	<div class="tcm-card tcm-prayer">
		<h2 class="tcm-title"><?php echo esc_html( $title ); ?></h2>
		<div class="tcm-chapter-text"><?php echo wp_kses_post( $content ); ?></div>
	</div>
</div>
