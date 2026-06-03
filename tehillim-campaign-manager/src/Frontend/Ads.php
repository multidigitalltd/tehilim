<?php
/**
 * Ads front-end module.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Services\AdService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exposes the [tehillim_ad slot="..."] shortcode and handles click tracking.
 */
final class Ads implements Registerable {

    /**
     * @var AdService
     */
    private $service;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->service = new AdService();
    }

    /**
     * {@inheritDoc}
     */
    public function register() {
        add_shortcode('tehillim_ad', array($this, 'render'));
        add_action('template_redirect', array($this, 'handle_click'));
    }

    /**
     * Render an ad zone.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function render($atts) {
        $atts = shortcode_atts(array('slot' => ''), $atts);
        return $this->service->render_zone((string) $atts['slot']);
    }

    /**
     * Record a click and redirect to the destination.
     *
     * @return void
     */
    public function handle_click() {
        if (empty($_GET['tcm_ad_click'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        $ad_id  = absint($_GET['tcm_ad_click']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $zone   = isset($_GET['zone']) ? sanitize_key(wp_unslash($_GET['zone'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $target = $this->service->record_click($ad_id, $zone);

        if ($target) {
            wp_redirect($target); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- intentional external destination.
            exit;
        }
        wp_safe_redirect(home_url('/'));
        exit;
    }
}
