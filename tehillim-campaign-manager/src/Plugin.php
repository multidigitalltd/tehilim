<?php
/**
 * Plugin bootstrap / wiring.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\PostTypes\PrayerPostType;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Central bootstrap for the v3.0 architecture.
 *
 * Collects feature "modules" (each a {@see Registerable}) and registers their
 * WordPress hooks on boot. New subsystems (services, repositories, controllers,
 * REST, messaging) are added here as they are implemented. See src/README.md
 * for the full architecture map.
 */
final class Plugin {

    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Whether boot() already ran.
     *
     * @var bool
     */
    private $booted = false;

    /**
     * Registered modules.
     *
     * @var Registerable[]
     */
    private $modules = array();

    /**
     * Get the shared instance.
     *
     * @return Plugin
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Wire up the plugin.
     *
     * @return void
     */
    public function boot() {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        add_action('init', array($this, 'load_textdomain'));

        foreach ($this->modules() as $module) {
            $module->register();
        }
    }

    /**
     * Build the list of feature modules.
     *
     * @return Registerable[]
     */
    private function modules() {
        if (empty($this->modules)) {
            $this->modules = array(
                new CampaignPostType(),
                new PrayerPostType(),
            );
        }
        return $this->modules;
    }

    /**
     * Load translations.
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'tehillim-campaign-manager',
            false,
            dirname(plugin_basename(TCM_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Deactivation cleanup.
     *
     * @return void
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('tcm_cron_tasks');
        flush_rewrite_rules();
    }
}
