<?php
/**
 * Archive of all campaigns — cards styled after the "Psalms Unite" design.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var array $campaigns Array of ['id','title','permalink','excerpt','thumb','stats'].
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
				<?php
				$stats     = $campaign['stats'];
				$completed = $stats['percent'] >= 100;
				?>
				<a class="tcm-card tcm-campaign-card" href="<?php echo esc_url($campaign['permalink']); ?>">
					<span class="tcm-campaign-card__media"<?php echo $campaign['thumb'] ? ' style="background-image:url(' . esc_url($campaign['thumb']) . ')"' : ''; ?> aria-hidden="true"></span>
					<span class="tcm-campaign-card__body">
						<span class="tcm-campaign-card__title"><?php echo esc_html($campaign['title']); ?></span>
						<?php if (!empty($campaign['excerpt'])) : ?>
							<span class="tcm-campaign-card__excerpt"><?php echo esc_html($campaign['excerpt']); ?></span>
						<?php endif; ?>

						<span class="tcm-progress-row">
							<span class="tcm-progress-meta">
								<?php
								printf(
									/* translators: 1: completed books, 2: target. */
									esc_html__('%1$s / %2$s books', 'tehillim-campaign-manager'),
									esc_html($stats['base_completed']),
									esc_html($stats['target'])
								);
								?>
							</span>
							<span class="tcm-pill <?php echo $completed ? 'is-done' : ''; ?>">
								<?php echo $completed ? esc_html__('Completed', 'tehillim-campaign-manager') : esc_html($stats['percent'] . '%'); ?>
							</span>
						</span>
						<span class="tcm-progress" role="progressbar" aria-valuenow="<?php echo esc_attr($stats['percent']); ?>" aria-valuemin="0" aria-valuemax="100">
							<span style="width:<?php echo esc_attr(max(2, $stats['percent'])); ?>%"></span>
						</span>

						<span class="tcm-btn is-block"><?php esc_html_e('Join the reading', 'tehillim-campaign-manager'); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
