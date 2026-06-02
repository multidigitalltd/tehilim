<?php
/**
 * Plugin Name: Tehillim Campaign Manager
 * Description: מערכת קמפיינים לחלוקת ספרי תהילים: ארכיון, עמודי קמפיין דינמיים, יעדים, בונוסים, שגרירים, וובהוקים, הודעות מותאמות וטופס יצירת קמפיין.
 * Version: 2.8.0
 * Author: Multi Digital
 * Text Domain: tehillim-campaign-manager
 */

if (!defined('ABSPATH')) exit;

define('TCM_PLUGIN_FILE', __FILE__);
define('TCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TCM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TCM_PLUGIN_DIR . 'includes/class-tehillim-campaign-manager.php';

Tehillim_Campaign_Manager::instance();
