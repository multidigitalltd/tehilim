<?php
/**
 * PHPUnit bootstrap for pure (WordPress-free) unit tests.
 *
 * Defines ABSPATH so the guarded source files load, then pulls in the Composer
 * autoloader (PSR-4 TCM\ => src/).
 *
 * @package Tehillim_Campaign_Manager
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require dirname(__DIR__) . '/vendor/autoload.php';
