<?php
/**
 * Ambassador dashboard.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var bool  $logged_in Whether the user is logged in.
 * @var array $rows       Per-ambassador entries with 'campaign_title','link','stats'.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="tcm-wrap">
	<div class="tcm-card">
		<h2><?php esc_html_e('Ambassador dashboard', 'tehillim-campaign-manager'); ?></h2>

		<?php if (empty($logged_in)) : ?>
			<p><?php esc_html_e('Please log in to view your ambassador dashboard.', 'tehillim-campaign-manager'); ?></p>
		<?php elseif (empty($rows)) : ?>
			<p><?php esc_html_e('No ambassador links yet.', 'tehillim-campaign-manager'); ?></p>
		<?php else : ?>
			<?php foreach ($rows as $row) : ?>
				<div class="tcm-card">
					<h3><?php echo esc_html($row['campaign_title']); ?></h3>
					<ul class="tcm-mini-stats">
						<li class="tcm-mini-stat"><strong><?php echo esc_html($row['stats']['total']); ?></strong> <?php esc_html_e('chapters taken', 'tehillim-campaign-manager'); ?></li>
						<li class="tcm-mini-stat"><strong><?php echo esc_html($row['stats']['done']); ?></strong> <?php esc_html_e('chapters completed', 'tehillim-campaign-manager'); ?></li>
						<li class="tcm-mini-stat"><strong><?php echo esc_html($row['stats']['books']); ?></strong> <?php esc_html_e('books in your merit', 'tehillim-campaign-manager'); ?></li>
					</ul>
					<input class="tcm-share-link" readonly value="<?php echo esc_attr($row['link']); ?>" style="direction:ltr;text-align:left;width:100%">
					<button type="button" class="tcm-btn is-secondary" data-tcm-copy="<?php echo esc_attr($row['link']); ?>"><?php esc_html_e('Copy link', 'tehillim-campaign-manager'); ?></button>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
