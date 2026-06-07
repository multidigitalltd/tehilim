<?php
/**
 * Recent activity feed (first names only).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var array $items Array of ['name','chapter','done','ago'].
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="tcm-card tcm-activity" aria-label="<?php esc_attr_e('Recent activity', 'tehillim-campaign-manager'); ?>">
	<h3><?php esc_html_e('Recent activity', 'tehillim-campaign-manager'); ?></h3>
	<?php if (!$items) : ?>
		<p class="tcm-muted"><?php esc_html_e('No activity yet.', 'tehillim-campaign-manager'); ?></p>
	<?php else : ?>
		<ul class="tcm-activity-list">
			<?php foreach ($items as $item) : ?>
				<li class="tcm-activity-row">
					<span class="tcm-activity-dot <?php echo $item['done'] ? 'is-done' : ''; ?>" aria-hidden="true"></span>
					<span class="tcm-activity-text">
						<?php
						if ($item['done']) {
							printf(
								/* translators: 1: first name, 2: Hebrew chapter label. */
								esc_html__('%1$s completed chapter %2$s', 'tehillim-campaign-manager'),
								'<strong>' . esc_html($item['name']) . '</strong>',
								esc_html($item['chapter'])
							);
						} else {
							printf(
								/* translators: 1: first name, 2: Hebrew chapter label. */
								esc_html__('%1$s took chapter %2$s', 'tehillim-campaign-manager'),
								'<strong>' . esc_html($item['name']) . '</strong>',
								esc_html($item['chapter'])
							);
						}
						?>
					</span>
					<?php if ($item['ago']) : ?>
						<span class="tcm-activity-ago">
							<?php
							printf(
								/* translators: %s: human-readable time difference. */
								esc_html__('%s ago', 'tehillim-campaign-manager'),
								esc_html($item['ago'])
							);
							?>
						</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
