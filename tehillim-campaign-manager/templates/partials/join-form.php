<?php
/**
 * Join form — choose chapter(s) and submit via REST.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var int    $campaign_id  Campaign id.
 * @var string $permalink    Campaign permalink (for the reader redirect).
 * @var array  $free         Free chapter rows in the current round.
 * @var bool   $allow_multi  Whether multi-chapter options are shown.
 * @var array  $multi_options Allowed multi counts.
 * @var bool   $allow_full   Whether a full untouched book is available.
 * @var string $site_key     Turnstile site key ('' when disabled).
 * @var string $join_title   Card heading.
 * @var string $button_text  Submit label.
 */

if (!defined('ABSPATH')) {
    exit;
}

use TCM\Support\Hebrew;

$free_count = is_array($free) ? count($free) : 0;
$select_id  = 'tcm-chapter-' . (int) $campaign_id;
?>
<div class="tcm-card tcm-join-card">
	<h3><?php echo esc_html($join_title); ?></h3>

	<?php if (!$free) : ?>
		<p><?php esc_html_e('All chapters in the current book are taken. Please check back soon.', 'tehillim-campaign-manager'); ?></p>
	<?php else : ?>
		<form class="tcm-form tcm-join-form"
			data-tcm-join
			data-tcm-id="<?php echo esc_attr($campaign_id); ?>"
			data-tcm-permalink="<?php echo esc_url($permalink); ?>">

			<div class="tcm-field">
				<label for="<?php echo esc_attr($select_id); ?>">
					<?php esc_html_e('Choose a chapter / how many', 'tehillim-campaign-manager'); ?>
				</label>
				<select id="<?php echo esc_attr($select_id); ?>" name="choice" class="tcm-chapter-select" required>
					<option value="0"><?php esc_html_e('Auto-pick a free chapter', 'tehillim-campaign-manager'); ?></option>

					<optgroup label="<?php esc_attr_e('Specific chapter', 'tehillim-campaign-manager'); ?>">
						<?php foreach ($free as $row) : ?>
							<option value="<?php echo esc_attr($row->chapter_number); ?>">
								<?php
								printf(
									/* translators: %s: Hebrew chapter label. */
									esc_html__('Chapter %s', 'tehillim-campaign-manager'),
									esc_html(Hebrew::chapter_label($row->chapter_number))
								);
								?>
							</option>
						<?php endforeach; ?>
					</optgroup>

					<?php if ($allow_multi && $multi_options) : ?>
						<optgroup label="<?php esc_attr_e('Several chapters', 'tehillim-campaign-manager'); ?>">
							<?php foreach ($multi_options as $count) : ?>
								<?php if ($count > 1 && $free_count >= $count) : ?>
									<option value="multi:<?php echo esc_attr($count); ?>">
										<?php
										printf(
											/* translators: %s: number of chapters. */
											esc_html__('Take %s chapters', 'tehillim-campaign-manager'),
											esc_html($count)
										);
										?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>

					<?php if ($allow_full) : ?>
						<optgroup label="<?php esc_attr_e('Whole book', 'tehillim-campaign-manager'); ?>">
							<option value="book:150"><?php esc_html_e('Take a whole book (150 chapters)', 'tehillim-campaign-manager'); ?></option>
						</optgroup>
					<?php endif; ?>
				</select>
			</div>

			<div class="tcm-fields-row tcm-contact-fields">
				<div class="tcm-field">
					<label for="tcm-name-<?php echo esc_attr($campaign_id); ?>">
						<?php esc_html_e('Name', 'tehillim-campaign-manager'); ?>
						<span class="tcm-muted"><?php esc_html_e('optional', 'tehillim-campaign-manager'); ?></span>
					</label>
					<input type="text" id="tcm-name-<?php echo esc_attr($campaign_id); ?>" name="name" autocomplete="name">
				</div>
				<div class="tcm-field">
					<label for="tcm-email-<?php echo esc_attr($campaign_id); ?>">
						<?php esc_html_e('Email', 'tehillim-campaign-manager'); ?>
						<span class="tcm-muted"><?php esc_html_e('optional', 'tehillim-campaign-manager'); ?></span>
					</label>
					<input type="email" id="tcm-email-<?php echo esc_attr($campaign_id); ?>" name="email" autocomplete="email">
				</div>
				<div class="tcm-field">
					<label for="tcm-phone-<?php echo esc_attr($campaign_id); ?>">
						<?php esc_html_e('Phone', 'tehillim-campaign-manager'); ?>
						<span class="tcm-muted"><?php esc_html_e('optional', 'tehillim-campaign-manager'); ?></span>
					</label>
					<input type="tel" id="tcm-phone-<?php echo esc_attr($campaign_id); ?>" name="phone" autocomplete="tel">
				</div>
			</div>

			<p class="tcm-muted"><?php esc_html_e('Your details are used only to send your chapter and reminders, and are optional.', 'tehillim-campaign-manager'); ?></p>

			<?php if ($site_key) : ?>
				<div class="tcm-turnstile cf-turnstile" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
			<?php endif; ?>

			<button class="tcm-btn tcm-submit-btn" type="submit"><?php echo esc_html($button_text); ?></button>
			<p class="tcm-form-error" role="alert" hidden></p>
		</form>
	<?php endif; ?>
</div>
