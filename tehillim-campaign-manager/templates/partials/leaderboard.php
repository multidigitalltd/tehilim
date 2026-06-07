<?php
/**
 * Ambassador leaderboard.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var array $entries Array of ['name','done','total'].
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="tcm-card tcm-leaderboard" aria-label="<?php esc_attr_e( 'Ambassador leaderboard', 'tehillim-campaign-manager' ); ?>">
	<h3>🏆 <?php esc_html_e( 'Ambassador leaderboard', 'tehillim-campaign-manager' ); ?></h3>
	<?php if ( ! $entries ) : ?>
		<p class="tcm-muted"><?php esc_html_e( 'No ambassadors yet — be the first to share.', 'tehillim-campaign-manager' ); ?></p>
	<?php else : ?>
		<ol class="tcm-leaderboard-list">
			<?php foreach ( $entries as $i => $entry ) : ?>
				<?php
				$done = (int) $entry['done'];
				$tot  = (int) $entry['total'];
				$pct  = $tot > 0 ? min( 100, (int) round( $done / $tot * 100 ) ) : 0;
				?>
				<li class="tcm-leaderboard-row">
					<div class="tcm-leaderboard-top">
						<span class="tcm-rank tcm-rank--<?php echo (int) ( $i + 1 ); ?>"><?php echo esc_html( $i + 1 ); ?></span>
						<span class="tcm-leaderboard-name"><?php echo esc_html( $entry['name'] ); ?></span>
						<span class="tcm-leaderboard-score"><?php echo esc_html( $done . ' / ' . $tot ); ?></span>
					</div>
					<span class="tcm-leaderboard-bar" aria-hidden="true"><span style="width:<?php echo esc_attr( max( 3, $pct ) ); ?>%"></span></span>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</section>
