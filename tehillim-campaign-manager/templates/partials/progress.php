<?php
/**
 * Campaign hero header + progress bar (Psalms Unite style).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $title        Campaign title (dedication).
 * @var string $dedicated_to Optional "dedicated to" line.
 * @var string $image        Optional featured image URL.
 * @var string $description  Campaign description HTML (already sanitised).
 * @var array  $stats        Stats array from StatsService.
 */

if (!defined('ABSPATH')) {
    exit;
}

$completed    = $stats['percent'] >= 100;
$dedicated_to = isset($dedicated_to) ? $dedicated_to : '';
$image        = isset($image) ? $image : '';
?>
<section class="tcm-card tcm-campaign-hero tcm-gradient-warm" aria-label="<?php esc_attr_e('Campaign progress', 'tehillim-campaign-manager'); ?>">
	<span class="tcm-orb tcm-orb--gold" aria-hidden="true"></span>
	<span class="tcm-orb tcm-orb--indigo" aria-hidden="true"></span>

	<?php if ($image) : ?>
		<img class="tcm-hero-image" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
	<?php endif; ?>

	<p class="tcm-eyebrow">✦ <?php esc_html_e('Tehillim campaign', 'tehillim-campaign-manager'); ?></p>
	<h2 class="tcm-title tcm-hero-title"><?php echo esc_html($title); ?></h2>

	<?php if ($dedicated_to) : ?>
		<p class="tcm-dedicated">♥ <?php
			printf(
				/* translators: %s: who the campaign is dedicated to. */
				esc_html__('Dedicated to %s', 'tehillim-campaign-manager'),
				'<strong>' . esc_html($dedicated_to) . '</strong>'
			);
		?></p>
	<?php endif; ?>

	<?php if (!empty($description)) : ?>
		<div class="tcm-description"><?php echo wp_kses_post($description); ?></div>
	<?php endif; ?>

	<div class="tcm-hero-progress">
		<div class="tcm-progress-top">
			<span class="tcm-pct"><?php echo esc_html($stats['percent']); ?>%</span>
			<span class="tcm-pill <?php echo $completed ? 'is-done' : ''; ?>">
				<?php echo $completed ? esc_html__('Completed', 'tehillim-campaign-manager') : esc_html__('In progress', 'tehillim-campaign-manager'); ?>
			</span>
		</div>

		<div class="tcm-progress" role="progressbar"
			aria-valuenow="<?php echo esc_attr($stats['percent']); ?>" aria-valuemin="0" aria-valuemax="100">
			<span style="width:<?php echo esc_attr(max(2, $stats['percent'])); ?>%"></span>
		</div>

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
</section>
