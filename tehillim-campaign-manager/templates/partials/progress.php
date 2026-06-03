<?php
/**
 * Campaign header + progress bar.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $title       Campaign title (dedication).
 * @var string $description Campaign description HTML (already sanitised).
 * @var array  $stats       Stats array from StatsService.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="tcm-card">
	<h2 class="tcm-title"><?php echo esc_html($title); ?></h2>

	<?php if (!empty($description)) : ?>
		<div class="tcm-description"><?php echo wp_kses_post($description); ?></div>
	<?php endif; ?>

	<div class="tcm-progress"
		role="progressbar"
		aria-valuenow="<?php echo esc_attr($stats['percent']); ?>"
		aria-valuemin="0"
		aria-valuemax="100"
		aria-label="<?php esc_attr_e('Campaign progress', 'tehillim-campaign-manager'); ?>">
		<span style="width:<?php echo esc_attr($stats['percent']); ?>%"></span>
	</div>

	<p class="tcm-progress-label">
		<?php
		printf(
			/* translators: %s: completion percentage. */
			esc_html__('Overall progress: %s%%', 'tehillim-campaign-manager'),
			esc_html($stats['percent'])
		);
		?>
	</p>

	<ul class="tcm-stats">
		<li class="tcm-stat">
			<?php
			printf(
				/* translators: 1: completed books, 2: target books. */
				esc_html__('Base goal: %1$s of %2$s books', 'tehillim-campaign-manager'),
				esc_html($stats['base_completed']),
				esc_html($stats['target'])
			);
			?>
		</li>
		<?php if ($stats['bonus'] > 0 || $stats['bonus_completed'] > 0) : ?>
			<li class="tcm-stat">
				<?php
				printf(
					/* translators: 1: bonus completed, 2: bonus target. */
					esc_html__('Bonus: %1$s of %2$s books', 'tehillim-campaign-manager'),
					esc_html($stats['bonus_completed']),
					esc_html($stats['bonus'])
				);
				?>
			</li>
		<?php endif; ?>
		<li class="tcm-stat">
			<?php
			printf(
				/* translators: 1: chapters done in current book, 2: total (150). */
				esc_html__('Current book: %1$s/%2$s chapters', 'tehillim-campaign-manager'),
				esc_html($stats['round_done']),
				150
			);
			?>
		</li>
	</ul>
</div>
