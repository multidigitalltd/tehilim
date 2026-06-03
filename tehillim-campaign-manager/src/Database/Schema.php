<?php
/**
 * Database schema.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Creates / upgrades the plugin's custom tables via dbDelta().
 *
 * Table base names (without the WordPress prefix):
 *  - tcm_assignments : per-chapter assignment rows for each campaign round.
 *  - tcm_ambassadors : personal sharing codes per campaign/user.
 *  - tcm_referrals   : attribution of an assignment to an ambassador.
 *  - tcm_logs        : audit trail of plugin events.
 *  - tcm_subscribers : opt-in subscribers for lists (daily chapter, etc.).
 *  - tcm_ad_stats    : aggregated impression/click counters per ad.
 */
final class Schema {

    /**
     * Install or upgrade all tables.
     *
     * @return void
     */
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix;

        foreach (self::table_definitions($prefix, $charset) as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * The CREATE TABLE statements.
     *
     * @param string $prefix  WordPress table prefix.
     * @param string $charset Charset/collate clause.
     * @return string[]
     */
    private static function table_definitions($prefix, $charset) {
        $assignments = $prefix . 'tcm_assignments';
        $ambassadors = $prefix . 'tcm_ambassadors';
        $referrals   = $prefix . 'tcm_referrals';
        $logs        = $prefix . 'tcm_logs';
        $subscribers = $prefix . 'tcm_subscribers';
        $ad_stats    = $prefix . 'tcm_ad_stats';

        return array(
            "CREATE TABLE $assignments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id BIGINT UNSIGNED NOT NULL,
                round_number INT UNSIGNED NOT NULL DEFAULT 1,
                chapter_number INT UNSIGNED NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'free',
                participant_name VARCHAR(190) NULL,
                participant_email VARCHAR(190) NULL,
                participant_phone VARCHAR(60) NULL,
                dedication TEXT NULL,
                token VARCHAR(64) NULL,
                taken_at DATETIME NULL,
                completed_at DATETIME NULL,
                reminder_count INT UNSIGNED NOT NULL DEFAULT 0,
                last_reminder_at DATETIME NULL,
                release_notice_at DATETIME NULL,
                released_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY campaign_round (campaign_id, round_number),
                KEY campaign_status (campaign_id, status),
                KEY participant_email (participant_email),
                UNIQUE KEY unique_chapter_round (campaign_id, round_number, chapter_number)
            ) $charset;",

            "CREATE TABLE $ambassadors (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                ambassador_name VARCHAR(190) NULL,
                ambassador_email VARCHAR(190) NULL,
                code VARCHAR(64) NOT NULL,
                goal_chapters INT UNSIGNED NOT NULL DEFAULT 10,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY code (code),
                KEY campaign_user (campaign_id, user_id),
                KEY campaign_id (campaign_id)
            ) $charset;",

            "CREATE TABLE $referrals (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id BIGINT UNSIGNED NOT NULL,
                ambassador_id BIGINT UNSIGNED NOT NULL,
                assignment_id BIGINT UNSIGNED NOT NULL,
                participant_email VARCHAR(190) NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY assignment_id (assignment_id),
                KEY campaign_ambassador (campaign_id, ambassador_id)
            ) $charset;",

            "CREATE TABLE $logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                event VARCHAR(80) NOT NULL,
                campaign_id BIGINT UNSIGNED NULL,
                assignment_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NULL,
                ip VARCHAR(80) NULL,
                data LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY event (event),
                KEY campaign_id (campaign_id),
                KEY assignment_id (assignment_id),
                KEY created_at (created_at)
            ) $charset;",

            "CREATE TABLE $subscribers (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                list_key VARCHAR(60) NOT NULL,
                name VARCHAR(190) NULL,
                email VARCHAR(190) NULL,
                phone VARCHAR(60) NULL,
                channel VARCHAR(20) NOT NULL DEFAULT 'email',
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                consent_at DATETIME NULL,
                unsubscribe_token VARCHAR(64) NULL,
                last_sent_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY list_channel (list_key, channel),
                KEY status (status),
                UNIQUE KEY unsubscribe_token (unsubscribe_token)
            ) $charset;",

            "CREATE TABLE $ad_stats (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                ad_id BIGINT UNSIGNED NOT NULL,
                zone VARCHAR(60) NOT NULL,
                impressions BIGINT UNSIGNED NOT NULL DEFAULT 0,
                clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
                stat_date DATE NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY ad_zone_date (ad_id, zone, stat_date)
            ) $charset;",
        );
    }
}
