<?php
/**
 * Chapter status grid. Status is conveyed by colour AND an icon + text label
 * (WCAG 1.4.1 — colour is never the only signal).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var array $rows Assignment rows for the current round.
 */

if (!defined('ABSPATH')) {
    exit;
}

use TCM\Support\Hebrew;

$labels = array(
    'free'  => array('icon' => '○', 'text' => __('Free', 'tehillim-campaign-manager')),
    'taken' => array('icon' => '◐', 'text' => __('Taken', 'tehillim-campaign-manager')),
    'done'  => array('icon' => '●', 'text' => __('Done', 'tehillim-campaign-manager')),
);
?>
<section class="tcm-card" aria-label="<?php esc_attr_e('Chapter status in the current book', 'tehillim-campaign-manager'); ?>">
	<h3><?php esc_html_e('Chapter status in the current book', 'tehillim-campaign-manager'); ?></h3>
	<ul class="tcm-grid">
		<?php foreach ($rows as $row) : ?>
			<?php
			$status = isset($labels[$row->status]) ? $row->status : 'free';
			$label  = $labels[$status];
			?>
			<li class="tcm-chapter" data-status="<?php echo esc_attr($status); ?>">
				<span aria-hidden="true"><?php echo esc_html(Hebrew::chapter_label($row->chapter_number)); ?></span>
				<span class="tcm-chapter__icon">
					<span aria-hidden="true"><?php echo esc_html($label['icon']); ?></span>
					<span class="screen-reader-text">
						<?php
						printf(
							/* translators: 1: chapter label, 2: status text. */
							esc_html__('Chapter %1$s — %2$s', 'tehillim-campaign-manager'),
							esc_html(Hebrew::chapter_label($row->chapter_number)),
							esc_html($label['text'])
						);
						?>
					</span>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="tcm-muted">
		<?php esc_html_e('○ Free · ◐ Taken · ● Done', 'tehillim-campaign-manager'); ?>
	</p>
</section>
