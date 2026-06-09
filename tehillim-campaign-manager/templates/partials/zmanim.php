<?php
/**
 * Hebrew date + daily zmanim card.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string                $hebrew_date Hebrew date string ('' when unavailable).
 * @var string                $city_label  City label.
 * @var array<string,string>  $zmanim      key => "HH:MM".
 * @var array<string,string>  $labels      key => label.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tcm-wrap tcm-zmanim">
	<div class="tcm-card">
		<div class="tcm-zmanim-head">
			<p class="tcm-eyebrow">✦ <?php esc_html_e( 'Times of the day', 'tehillim-campaign-manager' ); ?></p>
			<?php if ( '' !== $hebrew_date ) : ?>
				<h3 class="tcm-zmanim-date"><?php echo esc_html( $hebrew_date ); ?></h3>
			<?php endif; ?>
			<p class="tcm-muted tcm-zmanim-city"><?php echo esc_html( $city_label ); ?></p>
		</div>

		<?php if ( empty( $zmanim ) ) : ?>
			<p class="tcm-muted"><?php esc_html_e( 'Times are not available right now.', 'tehillim-campaign-manager' ); ?></p>
		<?php else : ?>
			<ul class="tcm-zmanim-list">
				<?php foreach ( $zmanim as $key => $time ) : ?>
					<li class="tcm-zmanim-row">
						<span class="tcm-zmanim-label"><?php echo esc_html( $labels[ $key ] ?? $key ); ?></span>
						<span class="tcm-zmanim-time"><?php echo esc_html( $time ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
