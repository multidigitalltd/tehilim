<?php
/**
 * Ambassador invite card.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var bool   $logged_in Whether the user is logged in.
 * @var string $title     Campaign title (when logged in).
 * @var string $link      Personal ambassador link (when logged in).
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="tcm-card">
	<?php if (empty($logged_in)) : ?>
		<h3><?php esc_html_e('Want to help spread the word?', 'tehillim-campaign-manager'); ?></h3>
		<p><?php esc_html_e('Registered users get a personal sharing link and can track the chapters they brought in.', 'tehillim-campaign-manager'); ?></p>
	<?php else : ?>
		<h3><?php esc_html_e('Your personal ambassador link', 'tehillim-campaign-manager'); ?></h3>
		<p class="tcm-muted"><?php esc_html_e('Anyone who takes a chapter through this link counts towards your progress.', 'tehillim-campaign-manager'); ?></p>
		<input class="tcm-share-link" readonly value="<?php echo esc_attr($link); ?>" style="direction:ltr;text-align:left;width:100%">
		<div class="tcm-share-actions">
			<button type="button" class="tcm-btn is-secondary" data-tcm-copy="<?php echo esc_attr($link); ?>"><?php esc_html_e('Copy link', 'tehillim-campaign-manager'); ?></button>
			<a class="tcm-btn" target="_blank" rel="noopener" href="<?php echo esc_url('https://wa.me/?text=' . rawurlencode($title . "\n" . $link)); ?>"><?php esc_html_e('Share on WhatsApp', 'tehillim-campaign-manager'); ?></a>
		</div>
	<?php endif; ?>
</div>
