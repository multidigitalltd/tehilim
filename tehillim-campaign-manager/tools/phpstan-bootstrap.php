<?php
/**
 * PHPStan bootstrap: define runtime constants the analyser can't see.
 *
 * @package Tehillim_Campaign_Manager
 */

define('TCM_VERSION', '3.0.0');
define('TCM_PLUGIN_FILE', __DIR__ . '/tehillim-campaign-manager.php');
define('TCM_PLUGIN_DIR', __DIR__ . '/');
define('TCM_PLUGIN_URL', 'https://example.test/wp-content/plugins/tehillim-campaign-manager/');

if (!defined('COOKIEPATH')) {
    define('COOKIEPATH', '/');
}
if (!defined('COOKIE_DOMAIN')) {
    define('COOKIE_DOMAIN', '');
}
