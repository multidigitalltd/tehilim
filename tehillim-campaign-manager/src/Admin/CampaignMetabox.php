<?php
/**
 * Campaign settings meta box.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\RoundService;
use TCM\Services\StatsService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds the "Campaign settings" meta box (goal, bonus, status) and persists it
 * with a nonce + capability check. On first publish it seeds round 1.
 */
final class CampaignMetabox implements Registerable {

    const NONCE_ACTION = 'tcm_save_campaign';
    const NONCE_FIELD  = 'tcm_meta_nonce';

    /**
     * {@inheritDoc}
     */
    public function register() {
        add_action('add_meta_boxes', array($this, 'add'));
        add_action('save_post_' . CampaignPostType::POST_TYPE, array($this, 'save'), 10, 2);
    }

    /**
     * Register the meta box.
     *
     * @return void
     */
    public function add() {
        add_meta_box(
            'tcm_settings',
            __('Tehillim campaign settings', 'tehillim-campaign-manager'),
            array($this, 'render'),
            CampaignPostType::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Render the meta box.
     *
     * @param \WP_Post $post Post.
     * @return void
     */
    public function render($post) {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        $target = max(1, (int) get_post_meta($post->ID, '_tcm_target_books', true));
        $bonus  = max(0, (int) get_post_meta($post->ID, '_tcm_bonus_books', true));
        $status = get_post_meta($post->ID, '_tcm_status', true) ?: 'active';
        ?>
        <p><strong><?php esc_html_e('The post title is the main dedication.', 'tehillim-campaign-manager'); ?></strong></p>
        <p>
            <label for="tcm_target_books"><strong><?php esc_html_e('Base goal: how many books to complete?', 'tehillim-campaign-manager'); ?></strong></label><br>
            <input type="number" id="tcm_target_books" name="tcm_target_books" min="1" value="<?php echo esc_attr($target); ?>" style="width:120px">
        </p>
        <p>
            <label for="tcm_bonus_books"><strong><?php esc_html_e('Bonus books', 'tehillim-campaign-manager'); ?></strong></label><br>
            <input type="number" id="tcm_bonus_books" name="tcm_bonus_books" min="0" value="<?php echo esc_attr($bonus); ?>" style="width:120px">
        </p>
        <p>
            <label for="tcm_status"><strong><?php esc_html_e('Status', 'tehillim-campaign-manager'); ?></strong></label><br>
            <select id="tcm_status" name="tcm_status">
                <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active', 'tehillim-campaign-manager'); ?></option>
                <option value="paused" <?php selected($status, 'paused'); ?>><?php esc_html_e('Paused', 'tehillim-campaign-manager'); ?></option>
                <option value="completed" <?php selected($status, 'completed'); ?>><?php esc_html_e('Completed', 'tehillim-campaign-manager'); ?></option>
            </select>
        </p>
        <?php
    }

    /**
     * Persist the meta.
     *
     * @param int      $post_id Post id.
     * @param \WP_Post $post    Post.
     * @return void
     */
    public function save($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST[self::NONCE_FIELD]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])), self::NONCE_ACTION)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_tcm_target_books', max(1, absint($_POST['tcm_target_books'] ?? 1)));
        update_post_meta($post_id, '_tcm_bonus_books', max(0, absint($_POST['tcm_bonus_books'] ?? 0)));

        $status = sanitize_key($_POST['tcm_status'] ?? 'active');
        update_post_meta($post_id, '_tcm_status', in_array($status, array('active', 'paused', 'completed'), true) ? $status : 'active');

        $rounds = new RoundService();
        if ('publish' === $post->post_status && !$rounds->has_round($post_id, 1)) {
            $rounds->generate($post_id, 1);
        }
        StatsService::flush($post_id);
    }
}
