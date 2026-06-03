<?php
/**
 * Registerable contract.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Contracts;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A feature module that wires its own WordPress hooks.
 */
interface Registerable {

    /**
     * Register hooks (actions/filters/shortcodes/etc.).
     *
     * @return void
     */
    public function register();
}
