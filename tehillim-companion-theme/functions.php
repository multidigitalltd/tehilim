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
        wp_enqueue_style('tehillim-companion', get_stylesheet_uri(), array(), '1.0.0');
    }
);
