<?php
/**
 * Ambassador leaderboard.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var array $entries Array of ['name','done','total'].
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="tcm-card tcm-leaderboard" aria-label="<?php esc_attr_e('Ambassador leaderboard', 'tehillim-campaign-manager'); ?>">
	<h3>🏆 <?php esc_html_e('Ambassador leaderboard', 'tehillim-campaign-manager'); ?></h3>
	<?php if (!$entries) : ?>
		<p class="tcm-muted"><?php esc_html_e('No ambassadors yet — be the first to share.', 'tehillim-campaign-manager'); ?></p>
	<?php else : ?>
		<ol class="tcm-leaderboard-list">
			<?php foreach ($entries as $i => $entry) : ?>
				<li class="tcm-leaderboard-row">
					<span class="tcm-rank tcm-rank--<?php echo (int) ($i + 1); ?>"><?php echo esc_html($i + 1); ?></span>
					<span class="tcm-leaderboard-name"><?php echo esc_html($entry['name']); ?></span>
					<span class="tcm-leaderboard-score">
						<?php
						printf(
							/* translators: 1: chapters completed, 2: chapters taken. */
							esc_html__('%1$s done · %2$s taken', 'tehillim-campaign-manager'),
							esc_html($entry['done']),
							esc_html($entry['total'])
						);
						?>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</section>
