<?php
/**
 * Ambassadors front-end module.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\AmbassadorService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Captures the ?tcm_ref code into a cookie, attributes referrals on claim, and
 * provides the ambassador invite + dashboard shortcodes.
 */
final class Ambassadors implements Registerable {

    const COOKIE = 'tcm_ref';

    /**
     * @var AmbassadorService
     */
    private $service;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->service = new AmbassadorService();
    }

    /**
     * {@inheritDoc}
     */
    public function register() {
        add_action('init', array($this, 'capture_ref'));
        add_action('tcm_chapter_claimed', array($this, 'on_claimed'), 30, 4);
        add_shortcode('tehillim_ambassador_invite', array($this, 'invite'));
        add_shortcode('tehillim_ambassador_dashboard', array($this, 'dashboard'));
    }

    /**
     * Store the referral code in a cookie for 30 days.
     *
     * @return void
     */
    public function capture_ref() {
        if (empty($_GET['tcm_ref'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        $code = sanitize_key(wp_unslash($_GET['tcm_ref'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!$code) {
            return;
        }
        setcookie(self::COOKIE, $code, time() + 30 * DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE[self::COOKIE] = $code;
    }

    /**
     * Attribute the (first) claimed chapter to a referring ambassador.
     *
     * @param int                  $campaign_id Campaign.
     * @param array<int,object>    $rows        Claimed rows.
     * @param string               $token       Token (unused).
     * @param array<string,string> $participant Participant.
     * @return void
     */
    public function on_claimed($campaign_id, $rows, $token, $participant) {
        if (empty($rows)) {
            return;
        }
        $this->service->record_from_cookie(
            (int) $campaign_id,
            (int) $rows[0]->id,
            isset($participant['email']) ? $participant['email'] : ''
        );
    }

    /**
     * Ambassador invite card (for logged-in users).
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function invite($atts) {
        $id = $this->resolve_id($atts);
        if (!$id) {
            return '';
        }
        if (!is_user_logged_in()) {
            return Templating::render('partials/ambassador-invite', array('logged_in' => false));
        }
        $ambassador = $this->service->get_or_create($id, get_current_user_id());
        if (!$ambassador) {
            return '';
        }
        return Templating::render(
            'partials/ambassador-invite',
            array(
                'logged_in' => true,
                'title'     => get_the_title($id),
                'link'      => add_query_arg(self::COOKIE, $ambassador->code, get_permalink($id)),
            )
        );
    }

    /**
     * Ambassador dashboard.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function dashboard($atts) {
        if (!is_user_logged_in()) {
            return Templating::render('partials/ambassador-dashboard', array('logged_in' => false, 'rows' => array()));
        }
        $atts        = shortcode_atts(array('id' => 0), $atts);
        $campaign_id = absint($atts['id']);
        if (!$campaign_id && is_singular(CampaignPostType::POST_TYPE)) {
            $campaign_id = (int) get_the_ID();
        }

        $ambassadors = ( new \TCM\Database\AmbassadorsRepository() )->for_user(get_current_user_id(), $campaign_id);
        $rows        = array();
        foreach ($ambassadors as $ambassador) {
            $stats  = $this->service->stats($ambassador);
            $rows[] = array(
                'campaign_title' => get_the_title((int) $ambassador->campaign_id),
                'link'           => add_query_arg(self::COOKIE, $ambassador->code, get_permalink((int) $ambassador->campaign_id)),
                'stats'          => $stats,
            );
        }
        return Templating::render('partials/ambassador-dashboard', array('logged_in' => true, 'rows' => $rows));
    }

    /**
     * Resolve the campaign id from attributes/context.
     *
     * @param array $atts Attributes.
     * @return int
     */
    private function resolve_id($atts) {
        $id = is_array($atts) && !empty($atts['id']) ? absint($atts['id']) : 0;
        if (!$id && CampaignPostType::POST_TYPE === get_post_type(get_the_ID())) {
            $id = (int) get_the_ID();
        }
        return $id;
    }
}
