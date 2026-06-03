<?php
/**
 * Plugin options accessor.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single source of truth for reading the `tcm_options` array, with defaults.
 */
final class Options {

    const KEY = 'tcm_options';

    /**
     * Default values.
     *
     * @return array<string,mixed>
     */
    public static function defaults() {
        return array(
            'link_base'            => 'tehillim',
            'webhook_url'          => '',
            'webhook_secret'       => '',
            'turnstile_site_key'   => '',
            'turnstile_secret_key' => '',
            'join_title'           => __('Join the reading', 'tehillim-campaign-manager'),
            'join_button_text'     => __('Join the reading', 'tehillim-campaign-manager'),
            'allow_multi_chapters' => '1',
            'multi_chapter_options' => '3,5,10',
            'allow_full_book'      => '1',
            'email_subject'        => __('You received a Tehillim chapter to read', 'tehillim-campaign-manager'),
            'email_body'           => "{name},\n\n" . __('You received chapter {chapter} in the campaign: {campaign_title}', 'tehillim-campaign-manager') . "\n\n{read_url}",
        );
    }

    /**
     * Get the full options array merged over defaults.
     *
     * @return array<string,mixed>
     */
    public static function all() {
        $stored = get_option(self::KEY, array());
        return wp_parse_args(is_array($stored) ? $stored : array(), self::defaults());
    }

    /**
     * Get a single option.
     *
     * @param string $key     Key.
     * @param mixed  $default Fallback when not set.
     * @return mixed
     */
    public static function get($key, $default = '') {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Turn an associative array into {key} => value placeholder replacements.
     *
     * @param array<string,mixed> $data Data.
     * @return array<string,string>
     */
    public static function placeholders(array $data) {
        $out = array();
        foreach ($data as $key => $value) {
            $out['{' . $key . '}'] = (string) $value;
        }
        return $out;
    }
}
