<?php
/**
 * Archive of all campaigns.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var array $campaigns Array of ['id','title','permalink','stats'] entries.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="tcm-wrap">
	<?php if (!$campaigns) : ?>
		<div class="tcm-card"><?php esc_html_e('No campaigns yet.', 'tehillim-campaign-manager'); ?></div>
	<?php else : ?>
		<div class="tcm-campaign-list">
			<?php foreach ($campaigns as $campaign) : ?>
				<?php $stats = $campaign['stats']; ?>
				<a class="tcm-card tcm-campaign-link" href="<?php echo esc_url($campaign['permalink']); ?>">
					<span class="tcm-badge"><?php echo esc_html($campaign['title']); ?></span>
					<h3><?php esc_html_e('Tehillim distribution', 'tehillim-campaign-manager'); ?></h3>
					<div class="tcm-progress"
						role="progressbar"
						aria-valuenow="<?php echo esc_attr($stats['percent']); ?>"
						aria-valuemin="0" aria-valuemax="100">
						<span style="width:<?php echo esc_attr($stats['percent']); ?>%"></span>
					</div>
					<p>
						<?php
						printf(
							/* translators: 1: completed books, 2: target. */
							esc_html__('Base goal: %1$s of %2$s books', 'tehillim-campaign-manager'),
							esc_html($stats['base_completed']),
							esc_html($stats['target'])
						);
						?>
					</p>
					<span class="tcm-btn"><?php esc_html_e('Join the reading', 'tehillim-campaign-manager'); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
