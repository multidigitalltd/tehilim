<?php
/**
 * My reading activity.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var bool  $logged_in Whether the user is logged in.
 * @var array $rows       Activity rows.
 */

if (!defined('ABSPATH')) {
    exit;
}

$labels = array(
    'free'  => __('Free', 'tehillim-campaign-manager'),
    'taken' => __('Awaiting completion', 'tehillim-campaign-manager'),
    'done'  => __('Completed', 'tehillim-campaign-manager'),
);
?>
<div class="tcm-wrap tcm-my-activity">
	<div class="tcm-card">
		<h3><?php esc_html_e('My Tehillim activity', 'tehillim-campaign-manager'); ?></h3>

		<?php if (empty($logged_in)) : ?>
			<p><?php esc_html_e('Please log in to view your activity.', 'tehillim-campaign-manager'); ?></p>
		<?php elseif (empty($rows)) : ?>
			<p><?php esc_html_e('No activity yet.', 'tehillim-campaign-manager'); ?></p>
		<?php else : ?>
			<table class="tcm-admin-table">
				<thead>
					<tr>
						<th><?php esc_html_e('Campaign', 'tehillim-campaign-manager'); ?></th>
						<th><?php esc_html_e('Chapter', 'tehillim-campaign-manager'); ?></th>
						<th><?php esc_html_e('Status', 'tehillim-campaign-manager'); ?></th>
						<th><?php esc_html_e('Action', 'tehillim-campaign-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rows as $row) : ?>
						<tr>
							<td><?php echo esc_html($row['campaign_title']); ?></td>
							<td><?php echo esc_html($row['chapter']); ?></td>
							<td><?php echo esc_html($labels[$row['status']] ?? $row['status']); ?></td>
							<td>
								<?php if ($row['read_url']) : ?>
									<a class="tcm-btn is-secondary" href="<?php echo esc_url($row['read_url']); ?>"><?php esc_html_e('Read / finish', 'tehillim-campaign-manager'); ?></a>
								<?php else : ?>
									<a class="tcm-btn" href="<?php echo esc_url($row['permalink']); ?>"><?php esc_html_e('Open', 'tehillim-campaign-manager'); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
