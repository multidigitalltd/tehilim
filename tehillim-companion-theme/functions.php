<?php
/**
 * Tehillim Companion theme.
 *
 * @package Tehillim_Companion
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action(
    'after_setup_theme',
    static function () {
        add_theme_support('wp-block-styles');
        add_theme_support('responsive-embeds');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        load_theme_textdomain('tehillim-companion', get_template_directory() . '/languages');
    }
);

add_action(
    'wp_enqueue_scripts',
    static function () {
        wp_enqueue_style(
            'tehillim-companion-fonts',
            'https://fonts.googleapis.com/css2?family=Frank+Ruhl+Libre:wght@500;700;800&family=Heebo:wght@400;500;700;800&display=swap',
            array(),
            null
        );
        wp_enqueue_style('tehillim-companion', get_stylesheet_uri(), array('tehillim-companion-fonts'), '1.2.0');
    }
);

/**
 * Register the "Tehillim" block-pattern category. Individual patterns live as
 * auto-loaded files under /patterns and reference it.
 */
add_action(
    'init',
    static function () {
        if (function_exists('register_block_pattern_category')) {
            register_block_pattern_category(
                'tehillim',
                array('label' => __('Tehillim', 'tehillim-companion'))
            );
        }
    }
);
