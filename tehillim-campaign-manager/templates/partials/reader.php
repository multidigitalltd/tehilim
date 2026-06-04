<?php
/**
 * In-page chapter reader (the "sacred space" — no ads here).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var int    $campaign_id Campaign id.
 * @var string $permalink   Campaign permalink.
 * @var object $row         The current assignment row.
 * @var array  $siblings    All chapters in this claim.
 * @var string $text        Sanitised chapter HTML ('' when not stored).
 * @var string $token       Access token.
 */

if (!defined('ABSPATH')) {
    exit;
}

use TCM\Support\Hebrew;

$total      = max(1, count($siblings));
$index      = 1;
$done_count = 0;
foreach ($siblings as $i => $sibling) {
    if ((int) $sibling->id === (int) $row->id) {
        $index = $i + 1;
    }
    if ('done' === $sibling->status) {
        $done_count++;
    }
}
$chapter_label = Hebrew::chapter_label($row->chapter_number);
?>
<section class="tcm-card tcm-reader" id="tcm-read"
	tabindex="-1"
	aria-label="<?php esc_attr_e('Chapter reader', 'tehillim-campaign-manager'); ?>"
	data-tcm-permalink="<?php echo esc_url($permalink); ?>">

	<h3 tabindex="-1">
		<?php
		printf(
			/* translators: %s: Hebrew chapter label. */
			esc_html__('Your chapter: Tehillim %s', 'tehillim-campaign-manager'),
			esc_html($chapter_label)
		);
		?>
	</h3>

	<?php if ($total > 1) : ?>
		<p class="tcm-badge">
			<?php
			printf(
				/* translators: 1: current index, 2: total chapters. */
				esc_html__('Chapter %1$s of %2$s', 'tehillim-campaign-manager'),
				esc_html($index),
				esc_html($total)
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ($text) : ?>
		<div class="tcm-chapter-text"><?php echo wp_kses_post($text); ?></div>
	<?php else : ?>
		<div class="tcm-notice"><?php esc_html_e('This chapter has no text yet. It can be added from the admin under "Tehillim chapters".', 'tehillim-campaign-manager'); ?></div>
	<?php endif; ?>

	<div class="tcm-actions">
		<button type="button" class="tcm-btn is-secondary"
			data-tcm-action="done"
			data-tcm-id="<?php echo esc_attr($row->id); ?>"
			data-tcm-token="<?php echo esc_attr($token); ?>"
			data-tcm-permalink="<?php echo esc_url($permalink); ?>">
			<?php esc_html_e('I finished this chapter', 'tehillim-campaign-manager'); ?>
		</button>
		<button type="button" class="tcm-btn"
			data-tcm-action="take-more"
			data-tcm-id="<?php echo esc_attr($row->id); ?>"
			data-tcm-token="<?php echo esc_attr($token); ?>"
			data-tcm-permalink="<?php echo esc_url($permalink); ?>">
			<?php esc_html_e('Finished — take another chapter', 'tehillim-campaign-manager'); ?>
		</button>
	</div>
</section>
