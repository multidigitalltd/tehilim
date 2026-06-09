<?php
/**
 * Plugin bootstrap / wiring.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM;

use TCM\Contracts\Registerable;
use TCM\Admin\CampaignMetabox;
use TCM\Admin\ChaptersPage;
use TCM\Admin\CampaignApprovals;
use TCM\Admin\Dashboard;
use TCM\Admin\SettingsPage;
use TCM\Admin\SetupWizard;
use TCM\Admin\SubscribersPage;
use TCM\Services\CampaignAlertService;
use TCM\Blocks\BlocksModule;
use TCM\Frontend\AccessibilityStatement;
use TCM\Frontend\Ads;
use TCM\Frontend\LoginGuard;
use TCM\Frontend\Ambassadors;
use TCM\Frontend\Assets;
use TCM\Frontend\Community;
use TCM\Frontend\Prayers;
use TCM\Frontend\SelfService;
use TCM\Frontend\Shortcodes;
use TCM\Frontend\SiteExtras;
use TCM\Frontend\Subscriptions;
use TCM\Frontend\Widgets;
use TCM\Frontend\Zmanim;
use TCM\PostTypes\AdPostType;
use TCM\PostTypes\CampaignPostType;
use TCM\PostTypes\PrayerPostType;
use TCM\Privacy\PrivacyModule;
use TCM\Rest\RestController;
use TCM\Services\CronService;
use TCM\Services\EmailService;
use TCM\Services\MailService;
use TCM\Services\WebhookService;

if ( ! defined( 'ABSPATH' ) ) {
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
		if ( null === self::$instance ) {
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
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		add_action( 'init', array( $this, 'load_textdomain' ) );

		foreach ( $this->modules() as $module ) {
			$module->register();
		}
	}

	/**
	 * Build the list of feature modules.
	 *
	 * @return Registerable[]
	 */
	private function modules() {
		if ( empty( $this->modules ) ) {
			$this->modules = array(
				new CampaignPostType(),
				new PrayerPostType(),
				new AdPostType(),
				new Assets(),
				new Shortcodes(),
				new Widgets(),
				new Community(),
				new Ambassadors(),
				new Subscriptions(),
				new Prayers(),
				new SelfService(),
				new Zmanim(),
				new AccessibilityStatement(),
				new SiteExtras(),
				new LoginGuard(),
				new Ads(),
				new BlocksModule(),
				new RestController(),
				new MailService(),
				new EmailService(),
				new WebhookService(),
				new CronService(),
				new CampaignAlertService(),
				new PrivacyModule(),
			);

			if ( is_admin() ) {
				$this->modules = array_merge(
					$this->modules,
					array(
						new Dashboard(),
						new CampaignApprovals(),
						new SubscribersPage(),
						new SettingsPage(),
						new ChaptersPage(),
						new CampaignMetabox(),
						new SetupWizard(),
						new \TCM\Admin\Exporter(),
					)
				);
			}
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
			dirname( plugin_basename( TCM_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Deactivation cleanup.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'tcm_cron_tasks' );
		flush_rewrite_rules();
	}
}
