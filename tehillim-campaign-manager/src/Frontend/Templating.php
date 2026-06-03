<?php
/**
 * Template loader.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a PHP template from the plugin's templates/ directory, allowing a
 * theme to override it by placing a file at:
 *   {theme}/tehillim-campaign-manager/{name}.php
 *
 * Templates receive an associative $data array and must escape on output.
 */
final class Templating {

    /**
     * Render a template to a string.
     *
     * @param string              $name Template name without extension (e.g. "partials/reader").
     * @param array<string,mixed> $data Variables exposed to the template.
     * @return string
     */
    public static function render($name, array $data = array()) {
        $file = self::locate($name);
        if (!$file) {
            return '';
        }
        ob_start();
        // $data is intentionally available to the included template.
        ( static function ($__file, $__data) {
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            extract($__data, EXTR_SKIP);
            include $__file;
        } )( $file, $data );
        return (string) ob_get_clean();
    }

    /**
     * Find a template, preferring a theme override.
     *
     * @param string $name Template name.
     * @return string Absolute path or '' when missing.
     */
    private static function locate($name) {
        $name     = ltrim(str_replace('..', '', $name), '/');
        $relative = 'tehillim-campaign-manager/' . $name . '.php';

        $theme = locate_template(array($relative));
        if ($theme) {
            return $theme;
        }

        $plugin = TCM_PLUGIN_DIR . 'templates/' . $name . '.php';
        return file_exists($plugin) ? $plugin : '';
    }
}
