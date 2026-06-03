<?php
/**
 * Admin dashboard.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\StatsService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Overview screen listing campaigns and their progress.
 */
final class Dashboard implements Registerable {

    /**
     * @var StatsService
     */
    private $stats;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->stats = new StatsService();
    }

    /**
     * {@inheritDoc}
     */
    public function register() {
        add_action('admin_menu', array($this, 'add_page'));
    }

    /**
     * Register the page.
     *
     * @return void
     */
    public function add_page() {
        add_submenu_page(
            'edit.php?post_type=' . CampaignPostType::POST_TYPE,
            __('Distribution dashboard', 'tehillim-campaign-manager'),
            __('Dashboard', 'tehillim-campaign-manager'),
            'manage_options',
            'tcm-dashboard',
            array($this, 'render')
        );
    }

    /**
     * Render the dashboard.
     *
     * @return void
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $campaigns = get_posts(
            array(
                'post_type'      => CampaignPostType::POST_TYPE,
                'posts_per_page' => 100,
                'post_status'    => 'any',
            )
        );
        ?>
        <div class="wrap" dir="rtl">
            <h1><?php esc_html_e('Tehillim distribution dashboard', 'tehillim-campaign-manager'); ?></h1>
            <p><?php esc_html_e('Archive shortcode:', 'tehillim-campaign-manager'); ?> <code>[tehillim_campaigns]</code></p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Campaign', 'tehillim-campaign-manager'); ?></th>
                        <th><?php esc_html_e('Base goal', 'tehillim-campaign-manager'); ?></th>
                        <th><?php esc_html_e('Bonus', 'tehillim-campaign-manager'); ?></th>
                        <th><?php esc_html_e('Completed', 'tehillim-campaign-manager'); ?></th>
                        <th><?php esc_html_e('Current book', 'tehillim-campaign-manager'); ?></th>
                        <th><?php esc_html_e('Progress', 'tehillim-campaign-manager'); ?></th>
                        <th><?php esc_html_e('Export', 'tehillim-campaign-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $campaign) : ?>
                        <?php $s = $this->stats->for_campaign($campaign->ID); ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($campaign->ID)); ?>"><?php echo esc_html(get_the_title($campaign)); ?></a>
                                &nbsp;·&nbsp;
                                <a href="<?php echo esc_url(get_permalink($campaign->ID)); ?>" target="_blank" rel="noopener"><?php esc_html_e('View', 'tehillim-campaign-manager'); ?></a>
                            </td>
                            <td><?php echo esc_html($s['target']); ?></td>
                            <td><?php echo esc_html($s['bonus']); ?></td>
                            <td><?php echo esc_html($s['completed_books']); ?></td>
                            <td><?php echo esc_html($s['round']); ?></td>
                            <td><?php echo esc_html($s['percent']); ?>%</td>
                            <td>
                                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=' . Exporter::ACTION . '&campaign_id=' . $campaign->ID), 'tcm_export')); ?>"><?php esc_html_e('CSV', 'tehillim-campaign-manager'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
