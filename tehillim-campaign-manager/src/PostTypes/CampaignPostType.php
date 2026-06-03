<?php
/**
 * Campaign custom post type.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\PostTypes;

use TCM\Contracts\Registerable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the `tcm_campaign` post type — a single Tehillim distribution
 * campaign (a dedication, a goal in books, and its chapter assignments).
 */
final class CampaignPostType implements Registerable {

    const POST_TYPE = 'tcm_campaign';

    /**
     * Hook registration.
     *
     * @return void
     */
    public function register() {
        add_action('init', array($this, 'register_post_type'));
    }

    /**
     * Register the post type.
     *
     * @return void
     */
    public function register_post_type() {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels'       => array(
                    'name'          => __('Tehillim Campaigns', 'tehillim-campaign-manager'),
                    'singular_name' => __('Tehillim Campaign', 'tehillim-campaign-manager'),
                    'add_new_item'  => __('Add Tehillim Campaign', 'tehillim-campaign-manager'),
                    'edit_item'     => __('Edit Tehillim Campaign', 'tehillim-campaign-manager'),
                ),
                'public'       => true,
                'show_ui'      => true,
                'show_in_rest' => true,
                'menu_icon'    => 'dashicons-book-alt',
                'supports'     => array('title', 'editor', 'thumbnail', 'author'),
                'has_archive'  => true,
                'rewrite'      => array(
                    'slug'       => self::base_slug(),
                    'with_front' => false,
                ),
            )
        );
    }

    /**
     * The URL base slug for campaigns (English letters, digits and dashes).
     *
     * @return string
     */
    public static function base_slug() {
        $options = get_option('tcm_options', array());
        $base    = isset($options['link_base']) ? sanitize_title($options['link_base']) : 'tehillim';
        $base    = preg_replace('/[^a-z0-9\-]/', '', strtolower($base));
        return $base ? $base : 'tehillim';
    }
}
