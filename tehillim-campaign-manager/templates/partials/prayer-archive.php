<?php
/**
 * Prayers / Segulot archive.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var array  $prayers  Entries with 'title','permalink','excerpt'.
 * @var array  $terms    Category terms.
 * @var string $current  Current category slug.
 * @var string $search   Current search string.
 * @var string $base_url Archive base URL.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="tcm-wrap tcm-segulot">
	<form class="tcm-segulot-filter" method="get" action="<?php echo esc_url($base_url); ?>" role="search">
		<label class="screen-reader-text" for="tcm-segulot-q"><?php esc_html_e('Search prayers', 'tehillim-campaign-manager'); ?></label>
		<input type="search" id="tcm-segulot-q" name="tcm_q" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search prayers…', 'tehillim-campaign-manager'); ?>">
		<button class="tcm-btn" type="submit"><?php esc_html_e('Search', 'tehillim-campaign-manager'); ?></button>
	</form>

	<?php if ($terms) : ?>
		<nav class="tcm-segulot-cats" aria-label="<?php esc_attr_e('Prayer categories', 'tehillim-campaign-manager'); ?>">
			<a class="tcm-badge <?php echo '' === $current ? 'is-active' : ''; ?>" href="<?php echo esc_url($base_url); ?>"><?php esc_html_e('All', 'tehillim-campaign-manager'); ?></a>
			<?php foreach ($terms as $term) : ?>
				<a class="tcm-badge <?php echo $current === $term->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tcm_cat', $term->slug, $base_url)); ?>"><?php echo esc_html($term->name); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if (!$prayers) : ?>
		<div class="tcm-card"><?php esc_html_e('No prayers found.', 'tehillim-campaign-manager'); ?></div>
	<?php else : ?>
		<div class="tcm-campaign-list">
			<?php foreach ($prayers as $prayer) : ?>
				<a class="tcm-card" href="<?php echo esc_url($prayer['permalink']); ?>">
					<h3><?php echo esc_html($prayer['title']); ?></h3>
					<?php if ($prayer['excerpt']) : ?>
						<p class="tcm-muted"><?php echo esc_html(wp_trim_words($prayer['excerpt'], 24)); ?></p>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
