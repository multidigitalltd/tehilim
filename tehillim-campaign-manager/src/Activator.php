<?php
/**
 * Activation routine.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM;

use TCM\Database\Schema;
use TCM\PostTypes\CampaignPostType;
use TCM\PostTypes\PrayerPostType;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Runs once on plugin activation: installs tables, default options, schedules
 * the cron task and flushes rewrite rules so pretty permalinks work.
 */
final class Activator {

    /**
     * Activate.
     *
     * @return void
     */
    public static function activate() {
        Schema::install();

        if (false === get_option('tcm_options')) {
            add_option('tcm_options', self::default_options());
        }
        update_option('tcm_db_version', TCM_VERSION);

        if (!wp_next_scheduled('tcm_cron_tasks')) {
            wp_schedule_event(time() + 300, 'hourly', 'tcm_cron_tasks');
        }

        // Register post types so their rewrite rules exist before the flush.
        (new CampaignPostType())->register_post_type();
        (new PrayerPostType())->register_post_type();
        flush_rewrite_rules();
    }

    /**
     * Default plugin options.
     *
     * @return array<string,mixed>
     */
    private static function default_options() {
        return array(
            'link_base'             => 'tehillim',
            'allow_multi_chapters'  => '1',
            'multi_chapter_options' => '3,5,10',
            'allow_full_book'       => '1',
        );
    }
}
