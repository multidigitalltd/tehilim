<?php
/**
 * Plugin Name: Tehillim Campaign Manager
 * Description: מערכת קמפיינים לחלוקת ספרי תהילים: ארכיון, עמודי קמפיין דינמיים, יעדים, בונוסים, וובהוקים, הודעות מותאמות וטופס יצירת קמפיין.
 * Version: 2.6.0
 * Author: Multi Digital
 * Text Domain: tehillim-campaign-manager
 */

if (!defined('ABSPATH')) exit;

class Tehillim_Campaign_Manager {
    const VERSION = '2.8.1';
    const CPT = 'tcm_campaign';
    const TABLE = 'tcm_assignments';
    const AMB_TABLE = 'tcm_ambassadors';
    const REF_TABLE = 'tcm_referrals';
    const LOG_TABLE = 'tcm_logs';
    const NONCE_ACTION = 'tcm_action';
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(defined('TCM_PLUGIN_FILE') ? TCM_PLUGIN_FILE : __FILE__, [$this, 'activate']);
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_action_routes']);
        add_action('init', [$this, 'capture_ambassador_ref']);
        add_filter('wp_insert_post_data', [$this, 'force_english_campaign_slug'], 20, 2);
        add_filter('post_type_link', [$this, 'campaign_permalink'], 10, 2);
        add_filter('the_content', [$this, 'auto_campaign_content']);
        add_action('add_meta_boxes', [$this, 'add_metaboxes']);
        add_action('save_post_' . self::CPT, [$this, 'save_campaign_meta'], 10, 2);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'maybe_upgrade']);
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_filter('query_vars', [$this, 'query_vars']);
        add_action('template_redirect', [$this, 'handle_pretty_action']);

        add_shortcode('tehillim_campaign', [$this, 'shortcode_campaign']);
        add_shortcode('tehillim_campaigns', [$this, 'shortcode_campaigns']);
        add_shortcode('tehillim_progress', [$this, 'shortcode_progress']);
        add_shortcode('tehillim_join_form', [$this, 'shortcode_join_form']);
        add_shortcode('tehillim_chapters', [$this, 'shortcode_chapters']);
        add_shortcode('tehillim_create_campaign_form', [$this, 'shortcode_create_campaign_form']);
        add_shortcode('tehillim_my_campaigns', [$this, 'shortcode_my_campaigns']);
        add_shortcode('tehillim_progress_percent', [$this, 'shortcode_progress_percent']);
        add_shortcode('tehillim_participants', [$this, 'shortcode_participants']);
        add_shortcode('tehillim_remaining_chapters', [$this, 'shortcode_remaining_chapters']);
        add_shortcode('tehillim_completed_books', [$this, 'shortcode_completed_books']);
        add_shortcode('tehillim_stats', [$this, 'shortcode_stats']);
        add_shortcode('tehillim_join', [$this, 'shortcode_join_form']);
        add_shortcode('tehillim_remaining', [$this, 'shortcode_remaining_chapters']);
        add_shortcode('tehillim_books_done', [$this, 'shortcode_completed_books']);
        add_shortcode('tehillim_stats_box', [$this, 'shortcode_stats']);
        add_shortcode('tehillim_progress_bar', [$this, 'shortcode_progress_bar']);
        add_shortcode('tehillim_cta', [$this, 'shortcode_cta']);
        add_shortcode('tehillim_urgency', [$this, 'shortcode_urgency']);
        add_shortcode('tehillim_data', [$this, 'shortcode_data']);

        add_action('admin_post_nopriv_tcm_join', [$this, 'handle_join']);
        add_action('admin_post_tcm_join', [$this, 'handle_join']);
        add_action('admin_post_nopriv_tcm_done', [$this, 'handle_done']);
        add_action('admin_post_tcm_done', [$this, 'handle_done']);
        add_action('admin_post_nopriv_tcm_take_more', [$this, 'handle_take_more']);
        add_action('admin_post_tcm_take_more', [$this, 'handle_take_more']);
        add_action('admin_post_nopriv_tcm_create_campaign', [$this, 'handle_create_campaign']);
        add_action('admin_post_tcm_create_campaign', [$this, 'handle_create_campaign']);
        add_action('admin_post_tcm_reset_campaign', [$this, 'handle_reset_campaign']);
        add_action('admin_post_tcm_generate_round', [$this, 'handle_generate_round']);
        add_action('admin_post_tcm_save_chapters', [$this, 'handle_save_chapters']);
        add_action('admin_post_tcm_owner_update_campaign', [$this, 'handle_owner_update_campaign']);
        add_action('admin_post_tcm_owner_add_bonus', [$this, 'handle_owner_add_bonus']);
        add_action('admin_post_tcm_owner_send_message', [$this, 'handle_owner_send_message']);
        add_action('admin_post_tcm_admin_approve_message', [$this, 'handle_admin_approve_message']);
        add_action('admin_post_tcm_admin_reject_message', [$this, 'handle_admin_reject_message']);
        add_action('admin_post_nopriv_tcm_release_chapter', [$this, 'handle_release_chapter']);
        add_action('admin_post_tcm_release_chapter', [$this, 'handle_release_chapter']);
        add_action('tcm_cron_tasks', [$this, 'process_cron_tasks']);
        add_shortcode('tehillim_my_activity', [$this, 'shortcode_my_activity']);
        add_shortcode('tehillim_ambassador_dashboard', [$this, 'shortcode_ambassador_dashboard']);
    }

    public function activate() {
        $this->register_cpt();
        $this->create_table();
        $this->create_ambassador_tables();
        $this->create_log_table();
        $this->set_default_options();
        if (!wp_next_scheduled('tcm_cron_tasks')) { wp_schedule_event(time() + 300, 'hourly', 'tcm_cron_tasks'); }
        flush_rewrite_rules();
    }

    private function table_name() { global $wpdb; return $wpdb->prefix . self::TABLE; }
    private function ambassador_table_name() { global $wpdb; return $wpdb->prefix . self::AMB_TABLE; }
    private function referral_table_name() { global $wpdb; return $wpdb->prefix . self::REF_TABLE; }
    private function log_table_name() { global $wpdb; return $wpdb->prefix . self::LOG_TABLE; }
    public function maybe_upgrade() {
        $installed = get_option('tcm_db_version', '0');
        if (version_compare($installed, self::VERSION, '<')) {
            $this->create_table();
            $this->create_ambassador_tables();
            $this->create_log_table();
            $this->ensure_table_columns();
            update_option('tcm_db_version', self::VERSION);
        }
    }

    private function ensure_table_columns() {
        global $wpdb;
        $table = $this->table_name();
        $columns = $wpdb->get_col("DESC $table", 0);
        if (!$columns) return;
        $wanted = [
            'participant_phone' => "ALTER TABLE $table ADD participant_phone VARCHAR(60) NULL AFTER participant_email",
            'dedication' => "ALTER TABLE $table ADD dedication TEXT NULL AFTER participant_phone",
            'token' => "ALTER TABLE $table ADD token VARCHAR(64) NULL AFTER dedication",
            'taken_at' => "ALTER TABLE $table ADD taken_at DATETIME NULL AFTER token",
            'completed_at' => "ALTER TABLE $table ADD completed_at DATETIME NULL AFTER taken_at",
            'reminder_count' => "ALTER TABLE $table ADD reminder_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER completed_at",
            'last_reminder_at' => "ALTER TABLE $table ADD last_reminder_at DATETIME NULL AFTER reminder_count",
            'release_notice_at' => "ALTER TABLE $table ADD release_notice_at DATETIME NULL AFTER last_reminder_at",
            'released_at' => "ALTER TABLE $table ADD released_at DATETIME NULL AFTER release_notice_at",
        ];
        foreach ($wanted as $col => $sql) {
            if (!in_array($col, $columns, true)) {
                $wpdb->query($sql);
            }
        }
    }


    public function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $this->table_name();
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            round_number INT UNSIGNED NOT NULL DEFAULT 1,
            chapter_number INT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'free',
            participant_name VARCHAR(190) NULL,
            participant_email VARCHAR(190) NULL,
            participant_phone VARCHAR(60) NULL,
            dedication TEXT NULL,
            token VARCHAR(64) NULL,
            taken_at DATETIME NULL,
            completed_at DATETIME NULL,
            reminder_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_reminder_at DATETIME NULL,
            release_notice_at DATETIME NULL,
            released_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY campaign_round (campaign_id, round_number),
            KEY campaign_status (campaign_id, status),
            KEY participant_email (participant_email),
            UNIQUE KEY unique_chapter_round (campaign_id, round_number, chapter_number)
        ) $charset;");
    }


    public function create_log_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $this->log_table_name();
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event VARCHAR(80) NOT NULL,
            campaign_id BIGINT UNSIGNED NULL,
            assignment_id BIGINT UNSIGNED NULL,
            user_id BIGINT UNSIGNED NULL,
            ip VARCHAR(80) NULL,
            data LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY event (event),
            KEY campaign_id (campaign_id),
            KEY assignment_id (assignment_id)
        ) $charset;");
    }

    private function log_event($event, $campaign_id = 0, $assignment_id = 0, $data = []) {
        global $wpdb;
        $wpdb->insert($this->log_table_name(), [
            'event' => sanitize_key($event),
            'campaign_id' => absint($campaign_id),
            'assignment_id' => absint($assignment_id),
            'user_id' => get_current_user_id(),
            'ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'data' => wp_json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_at' => current_time('mysql'),
        ], ['%s','%d','%d','%d','%s','%s','%s']);
    }

    public function create_ambassador_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $amb = $this->ambassador_table_name();
        $ref = $this->referral_table_name();
        dbDelta("CREATE TABLE $amb (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            ambassador_name VARCHAR(190) NULL,
            ambassador_email VARCHAR(190) NULL,
            code VARCHAR(64) NOT NULL,
            goal_chapters INT UNSIGNED NOT NULL DEFAULT 10,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY campaign_user (campaign_id, user_id),
            KEY campaign_id (campaign_id)
        ) $charset;");
        dbDelta("CREATE TABLE $ref (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            ambassador_id BIGINT UNSIGNED NOT NULL,
            assignment_id BIGINT UNSIGNED NOT NULL,
            participant_email VARCHAR(190) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY assignment_id (assignment_id),
            KEY campaign_ambassador (campaign_id, ambassador_id)
        ) $charset;");
    }
    public function register_action_routes() {
        $base = $this->campaign_base_slug();
        add_rewrite_rule('^' . preg_quote($base, '/') . '/campaign-([0-9]+)/?$', 'index.php?post_type=' . self::CPT . '&p=$matches[1]', 'top');
        add_rewrite_rule('^' . preg_quote($base, '/') . '/action/(done|take-more|release)/([0-9]+)/([^/]+)/?$', 'index.php?tcm_action=$matches[1]&tcm_assignment=$matches[2]&tcm_token=$matches[3]', 'top');
    }

    public function query_vars($vars) {
        $vars[] = 'tcm_action';
        $vars[] = 'tcm_assignment';
        $vars[] = 'tcm_token';
        return $vars;
    }

    public function handle_pretty_action() {
        $action = get_query_var('tcm_action');
        if (!$action) return;
        $_GET['assignment_id'] = absint(get_query_var('tcm_assignment'));
        $_GET['token'] = sanitize_text_field(get_query_var('tcm_token'));
        if ($action === 'done') {
            $this->handle_done();
        } elseif ($action === 'take-more') {
            $this->handle_take_more();
        } elseif ($action === 'release') {
            $this->handle_release_chapter();
        }
        exit;
    }

    public function register_cpt() {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => 'קמפייני תהילים',
                'singular_name' => 'קמפיין תהילים',
                'add_new_item' => 'הוספת קמפיין תהילים',
                'edit_item' => 'עריכת קמפיין תהילים',
            ],
            'public' => true,
            'show_ui' => true,
            'menu_icon' => 'dashicons-book-alt',
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => true,
            'rewrite' => ['slug' => $this->campaign_base_slug(), 'with_front' => false],
        ]);
    }

    public function force_english_campaign_slug($data, $postarr) {
        if (($data['post_type'] ?? '') !== self::CPT) return $data;
        if (!empty($data['post_name']) && preg_match('/^[a-z0-9\-]+$/', $data['post_name'])) return $data;
        $id_part = !empty($postarr['ID']) ? absint($postarr['ID']) : wp_rand(10000, 99999);
        $data['post_name'] = 'tehillim-campaign-' . $id_part;
        return $data;
    }

    public function add_metaboxes() {
        add_meta_box('tcm_settings', 'הגדרות קמפיין תהילים', [$this, 'campaign_metabox'], self::CPT, 'normal', 'high');
        add_meta_box('tcm_shortcodes', 'שורטקודים ושדות מטה', [$this, 'shortcodes_metabox'], self::CPT, 'side', 'default');
    }

    public function campaign_metabox($post) {
        wp_nonce_field('tcm_save_campaign', 'tcm_meta_nonce');
        $target = max(1, (int)get_post_meta($post->ID, '_tcm_target_books', true));
        $bonus = max(0, (int)get_post_meta($post->ID, '_tcm_bonus_books', true));
        $status = get_post_meta($post->ID, '_tcm_status', true) ?: 'active';
        echo '<p><strong>כותרת הפוסט היא ההקדשה הראשית</strong> — לדוגמה: לרפואת פלוני בן פלונית / לעילוי נשמת פלוני.</p>';
        echo '<p><label><strong>יעד בסיס: כמה ספרי תהילים רוצים לסיים?</strong></label><br><input type="number" name="tcm_target_books" min="1" value="' . esc_attr($target) . '" style="width:120px"></p>';
        echo '<p><label><strong>סטטוס</strong></label><br><select name="tcm_status"><option value="active" ' . selected($status, 'active', false) . '>פעיל</option><option value="completed" ' . selected($status, 'completed', false) . '>הסתיים</option><option value="paused" ' . selected($status, 'paused', false) . '>מושהה</option></select></p>';
        echo '<p><label><strong>ספרי בונוס</strong></label><br><input type="number" name="tcm_bonus_books" min="0" value="' . esc_attr($bonus) . '" style="width:120px"> <span style="color:#666">נספר מעבר ליעד הבסיסי.</span></p>';
    }

    public function shortcodes_metabox($post) {
        echo '<p><strong>המדריך המלא:</strong></p><p><a href="' . esc_url(admin_url('edit.php?post_type=' . self::CPT . '&page=tcm-guide')) . '">מדריך שימוש ושורטקודים</a></p>';
        echo '<p><strong>ארכיון כללי:</strong></p><p><code>[tehillim_campaigns]</code></p>';
        echo '<p><strong>טופס יצירת קמפיין:</strong></p><p><code>[tehillim_create_campaign_form]</code></p>';
        echo '<hr><p><strong>שדות מטה ליצירת קמפיין מטופס:</strong></p>';
        echo '<p><code>post_title</code> — ההקדשה הראשית<br><code>_tcm_target_books</code> — יעד בסיס<br><code>_tcm_bonus_books</code> — ספרי בונוס<br><code>_tcm_status</code> — active / paused / completed</p>';
    }

    public function save_campaign_meta($post_id, $post) {
        if (!isset($_POST['tcm_meta_nonce']) || !wp_verify_nonce($_POST['tcm_meta_nonce'], 'tcm_save_campaign')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        update_post_meta($post_id, '_tcm_target_books', max(1, absint($_POST['tcm_target_books'] ?? 1)));
        update_post_meta($post_id, '_tcm_bonus_books', max(0, absint($_POST['tcm_bonus_books'] ?? 0)));
        update_post_meta($post_id, '_tcm_status', sanitize_text_field($_POST['tcm_status'] ?? 'active'));
        if ($post->post_status === 'publish' && !$this->has_round($post_id, 1)) $this->generate_round($post_id, 1);
    }

    private function has_round($campaign_id, $round) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d", $campaign_id, $round)) > 0;
    }

    private function generate_round($campaign_id, $round) {
        global $wpdb; $now = current_time('mysql');
        for ($i=1; $i<=150; $i++) {
            $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$this->table_name()} (campaign_id, round_number, chapter_number, status, created_at, updated_at) VALUES (%d,%d,%d,'free',%s,%s)", $campaign_id, $round, $i, $now, $now));
        }
    }

    private function current_round($campaign_id) {
        global $wpdb;
        $table = $this->table_name();

        // The active round is the first round that is not fully completed.
        // This keeps the normal campaign view stable even if a user takes a whole future book.
        $round = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT round_number
             FROM $table
             WHERE campaign_id=%d
             GROUP BY round_number
             HAVING SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) < 150
             ORDER BY round_number ASC
             LIMIT 1",
            $campaign_id
        ));

        if ($round < 1) {
            $max_round = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT MAX(round_number) FROM $table WHERE campaign_id=%d",
                $campaign_id
            ));
            $round = max(1, $max_round + 1);
            $this->generate_round($campaign_id, $round);
        }

        return $round;
    }

    private function stats_without_current_round($campaign_id) {
        global $wpdb;
        $target = max(1, (int)get_post_meta($campaign_id, '_tcm_target_books', true));
        $bonus = max(0, (int)get_post_meta($campaign_id, '_tcm_bonus_books', true));
        $goal_total = $target + $bonus;
        $total_done = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND status='done'", $campaign_id));
        $completed_books = intdiv($total_done, 150);
        return compact('target','bonus','goal_total','total_done','completed_books');
    }

    private function find_empty_full_book_round($campaign_id) {
        global $wpdb;
        $table = $this->table_name();
        $s = $this->stats_without_current_round($campaign_id);
        $goal_total = max(1, (int)$s['goal_total']);

        for ($round = 1; $round <= $goal_total; $round++) {
            $total = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d",
                $campaign_id, $round
            ));

            if ($total === 0) {
                return $round;
            }

            $free = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d AND status='free'",
                $campaign_id, $round
            ));
            $taken_or_done = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d AND status IN ('taken','done')",
                $campaign_id, $round
            ));

            if ($free === 150 && $taken_or_done === 0) {
                return $round;
            }
        }

        return 0;
    }

    private function stats($campaign_id) {
        global $wpdb;
        $target = max(1, (int)get_post_meta($campaign_id, '_tcm_target_books', true));
        $bonus = max(0, (int)get_post_meta($campaign_id, '_tcm_bonus_books', true));
        $goal_total = $target + $bonus;
        $total_done = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND status='done'", $campaign_id));
        $completed_books = intdiv($total_done, 150);
        $base_completed = min($completed_books, $target);
        $bonus_completed = max(0, $completed_books - $target);
        $round = $this->current_round($campaign_id);
        $round_done = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status='done'", $campaign_id, $round));
        $round_taken = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status='taken'", $campaign_id, $round));
        $percent = min(100, round(($total_done / ($goal_total * 150)) * 100, 1));
        return compact('target','bonus','goal_total','total_done','completed_books','base_completed','bonus_completed','round','round_done','round_taken','percent');
    }

    public function assets() {
        wp_register_style('tcm-style', false, [], self::VERSION);
        wp_enqueue_style('tcm-style');
        wp_add_inline_style('tcm-style', $this->css());
        wp_register_script('tcm-front', false, [], self::VERSION, true);
        wp_enqueue_script('tcm-front');
        wp_add_inline_script('tcm-front', "document.addEventListener('click',function(e){var btn=e.target.closest('[data-tcm-copy]');if(!btn)return;e.preventDefault();var val=btn.getAttribute('data-tcm-copy')||'';function done(){var old=btn.textContent;btn.textContent='הועתק';setTimeout(function(){btn.textContent=old;},1600);}if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(val).then(done).catch(function(){window.prompt('העתיקו את הקישור:',val);});}else{window.prompt('העתיקו את הקישור:',val);}});");
    }
    public function admin_assets() { $this->assets(); }

    private function css() {
        $o = $this->opts();
        $primary = sanitize_hex_color($o['design_primary_color'] ?? '') ?: '#111111';
        $secondary = sanitize_hex_color($o['design_secondary_color'] ?? '') ?: '#1f9d55';
        $card_bg = sanitize_hex_color($o['design_card_bg'] ?? '') ?: '#ffffff';
        $page_bg = sanitize_text_field($o['design_page_bg'] ?? 'transparent'); if ($page_bg !== 'transparent') $page_bg = sanitize_hex_color($page_bg) ?: 'transparent';
        $text_color = sanitize_hex_color($o['design_text_color'] ?? '') ?: '#111111';
        $muted_color = sanitize_hex_color($o['design_muted_color'] ?? '') ?: '#666666';
        $field_bg = sanitize_hex_color($o['design_field_bg'] ?? '') ?: '#ffffff';
        $field_border = sanitize_hex_color($o['design_field_border'] ?? '') ?: '#dddddd';
        $button_text = sanitize_hex_color($o['design_button_text_color'] ?? '') ?: '#ffffff';
        $dashboard_bg = sanitize_hex_color($o['design_dashboard_bg'] ?? '') ?: '#f7f7f7';
        $button_radius = absint($o['design_button_radius'] ?? 999); if ($button_radius > 999) $button_radius = 999;
        $field_radius = absint($o['design_field_radius'] ?? 12); if ($field_radius > 60) $field_radius = 12;
        $button_padding_y = absint($o['design_button_padding_y'] ?? 12); if ($button_padding_y < 4) $button_padding_y = 12;
        $button_padding_x = absint($o['design_button_padding_x'] ?? 22); if ($button_padding_x < 8) $button_padding_x = 22;
        $page_max = absint($o['design_max_width'] ?? 980); if ($page_max < 320) $page_max = 980;
        $radius = absint($o['design_radius'] ?? 18); if ($radius > 60) $radius = 18;
        $title_size = absint($o['design_title_size'] ?? 28); if ($title_size < 16) $title_size = 28;
        $font = sanitize_text_field($o['design_font_family'] ?? 'inherit');
        $custom = wp_strip_all_tags($o['design_custom_css'] ?? '');
        return ':root{--tcm-primary:'.$primary.';--tcm-secondary:'.$secondary.';--tcm-card-bg:'.$card_bg.';--tcm-page-bg:'.$page_bg.';--tcm-text:'.$text_color.';--tcm-muted:'.$muted_color.';--tcm-field-bg:'.$field_bg.';--tcm-field-border:'.$field_border.';--tcm-button-text:'.$button_text.';--tcm-dashboard-bg:'.$dashboard_bg.';--tcm-button-radius:'.$button_radius.'px;--tcm-field-radius:'.$field_radius.'px;--tcm-radius:'.$radius.'px;--tcm-title-size:'.$title_size.'px}.tcm-wrap{direction:rtl;text-align:right;font-family:'.esc_attr($font).';max-width:'.$page_max.'px;margin:20px auto;background:var(--tcm-page-bg);color:var(--tcm-text)}.tcm-card{background:var(--tcm-card-bg);border:1px solid #e7e7e7;border-radius:var(--tcm-radius);padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.06);margin-bottom:18px;color:var(--tcm-text)}.tcm-title{font-size:var(--tcm-title-size);font-weight:800;margin:0 0 8px}.tcm-progress{height:18px;background:#eee;border-radius:100px;overflow:hidden;margin:14px 0}.tcm-progress span{display:block;height:100%;background:var(--tcm-secondary);border-radius:100px}.tcm-stats{display:flex;gap:12px;flex-wrap:wrap;margin:14px 0}.tcm-stat{background:var(--tcm-dashboard-bg);border-radius:14px;padding:10px 14px;font-weight:700}.tcm-form label{display:block;font-weight:700;margin:12px 0 5px}.tcm-form input,.tcm-form select,.tcm-form textarea{width:100%;padding:12px;border:1px solid var(--tcm-field-border);border-radius:var(--tcm-field-radius);box-sizing:border-box;background:var(--tcm-field-bg);color:var(--tcm-text)}.tcm-btn{display:inline-block;border:0;background:var(--tcm-primary);color:var(--tcm-button-text)!important;border-radius:var(--tcm-button-radius);padding:'.$button_padding_y.'px '.$button_padding_x.'px;font-weight:800;text-decoration:none;cursor:pointer;margin:6px 4px}.tcm-btn.tcm-secondary{background:var(--tcm-secondary)}.tcm-btn:hover{opacity:.88}.tcm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:8px}.tcm-chapter{border-radius:12px;padding:10px;text-align:center;font-weight:800;background:#f1f1f1;color:#333}.tcm-chapter.taken{background:#fff3cd}.tcm-chapter.done{background:#d9f5e5}.tcm-notice{padding:12px 16px;border-radius:12px;margin:12px 0;background:#e9f7ef;border:1px solid #c9ebd6}.tcm-campaign-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}.tcm-muted{color:var(--tcm-muted);font-size:14px}.tcm-admin-table{width:100%;border-collapse:collapse}.tcm-admin-table th,.tcm-admin-table td{padding:10px;border-bottom:1px solid #ddd;text-align:right}.tcm-reader-frame{width:100%;min-height:520px;border:1px solid #ddd;border-radius:14px;background:#fff}.tcm-actions{margin-top:14px;display:flex;gap:8px;flex-wrap:wrap}.tcm-badge{display:inline-block;background:#f1f1f1;border-radius:999px;padding:5px 10px;font-weight:700}.tcm-description{margin:8px 0 14px;color:#333}.tcm-manager{border-top:1px solid #eee;margin-top:14px;padding-top:14px}.tcm-manager textarea{min-height:90px}.tcm-inline-form{margin:8px 0}.tcm-turnstile{margin:14px 0}.tcm-share-box input{direction:ltr;text-align:left;margin:8px 0}.tcm-share-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}.tcm-pending{background:#fff8e1;border-color:#ffe0a3}.tcm-ambassador-link{direction:ltr!important;text-align:left!important}.tcm-mini-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px}.tcm-mini-stat{background:var(--tcm-dashboard-bg);border-radius:14px;padding:12px;text-align:center}.tcm-mini-stat strong{display:block;font-size:22px}.tcm-join-card{overflow:hidden}.tcm-join-form{display:block}.tcm-fields-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;align-items:end}.tcm-field label{margin-top:0}.tcm-select-row{margin-top:0}.tcm-contact-fields{margin-top:16px}.tcm-privacy-note{margin:8px 0 0}.tcm-my-chapters{background:var(--tcm-dashboard-bg);border-radius:12px;padding:10px 12px;margin:10px 0}.tcm-chapter-text{font-size:19px;line-height:1.9;margin-top:18px}.tcm-progress-label{font-weight:800;margin-bottom:6px}.tcm-form input,.tcm-form select{min-height:52px;font-size:16px}.tcm-chapter-select{min-height:62px!important;font-size:20px!important;font-weight:700;background:#fff}.tcm-submit-btn{width:100%;margin:16px 0 0!important;text-align:center;font-size:19px;min-height:56px;box-shadow:0 10px 24px rgba(0,0,0,.12);transition:transform .2s ease,box-shadow .2s ease,opacity .2s ease}.tcm-submit-btn:hover{transform:translateY(-2px);box-shadow:0 14px 32px rgba(0,0,0,.16)}@media(max-width:768px){.tcm-fields-row{grid-template-columns:1fr}.tcm-card{padding:18px}.tcm-chapter-select{font-size:18px!important}.tcm-submit-btn{font-size:17px}}'.$custom;
    }

    public function auto_campaign_content($content) {
        if (!is_singular(self::CPT) || !in_the_loop() || !is_main_query()) return $content;
        return $this->shortcode_campaign(['id' => get_the_ID()]);
    }

    public function shortcode_campaign($atts) {
        $atts = shortcode_atts(['id'=>get_the_ID()], $atts);
        $id = absint($atts['id']);
        if (!$id || get_post_type($id) !== self::CPT) return '';
        ob_start(); echo '<div class="tcm-wrap" id="tcm">';
        if (!empty($_GET['tcm_read']) && !empty($_GET['token'])) echo $this->render_reader(absint($_GET['tcm_read']), sanitize_text_field($_GET['token']));
        $this->render_campaign_header($id); echo $this->shortcode_join_form(['id'=>$id]); echo $this->shortcode_chapters(['id'=>$id]); echo $this->shortcode_ambassador_invite(['id'=>$id]); echo '</div>'; return ob_get_clean();
    }

    private function render_campaign_header($id) {
        $s = $this->stats($id);
        echo '<div class="tcm-card"><h2 class="tcm-title">' . esc_html(get_the_title($id)) . '</h2>';
        $desc = get_post_field('post_content', $id);
        if ($desc) echo '<div class="tcm-description">' . wp_kses_post(wpautop($desc)) . '</div>';
        if (!empty($_GET['tcm_created'])) { echo '<div class="tcm-notice"><strong>הקמפיין נוצר בהצלחה.</strong><br>אפשר לשתף עכשיו את הקישור הישיר לקמפיין.</div>' . $this->share_box($id); }
        echo '<div class="tcm-progress"><span style="width:' . esc_attr($s['percent']) . '%"></span></div><div class="tcm-stats"><div class="tcm-stat">התקדמות כוללת: ' . esc_html($s['percent']) . '%</div><div class="tcm-stat">יעד בסיס: ' . esc_html($s['base_completed']) . ' מתוך ' . esc_html($s['target']) . ' ספרים</div>';
        if ($s['bonus'] > 0 || $s['bonus_completed'] > 0) echo '<div class="tcm-stat">בונוס: ' . esc_html($s['bonus_completed']) . ' מתוך ' . esc_html($s['bonus']) . ' ספרים</div>';
        echo '<div class="tcm-stat">ספר נוכחי: ' . esc_html($s['round']) . '</div><div class="tcm-stat">פרקים שהושלמו בספר הנוכחי: ' . esc_html($s['round_done']) . '/150</div></div></div>';
    }

    public function shortcode_progress($atts) { $atts = shortcode_atts(['id'=>get_the_ID()], $atts); $id = absint($atts['id']); if (!$id) return ''; ob_start(); echo '<div class="tcm-wrap">'; $this->render_campaign_header($id); echo '</div>'; return ob_get_clean(); }

    public function shortcode_join_form($atts) {
        $atts = shortcode_atts(['id'=>get_the_ID()], $atts);
        $id = absint($atts['id']); if (!$id) return '';
        $status = get_post_meta($id, '_tcm_status', true) ?: 'active';
        if ($status !== 'active') return '<div class="tcm-card tcm-wrap">הקמפיין אינו פעיל כרגע.</div>';
        $free = $this->get_free_chapters($id);
        $free_count = is_array($free) ? count($free) : 0;
        $allow_multi = $this->opt('allow_multi_chapters') !== '0';
        $allow_full = $this->opt('allow_full_book') !== '0' && $this->find_empty_full_book_round($id) > 0;
        $multi_options = array_filter(array_map('absint', explode(',', (string)$this->opt('multi_chapter_options'))));
        if (!$multi_options) $multi_options = [3,5,10];
        ob_start();
        if (!empty($_GET['tcm_msg'])) { $key = sanitize_text_field($_GET['tcm_msg']); echo '<div class="tcm-notice">' . esc_html($this->message($key)) . '</div>'; }
        if (!$free) { echo '<div class="tcm-card"><p>כל הפרקים בספר הנוכחי כבר נתפסו. אפשר לרענן בהמשך.</p></div>'; return ob_get_clean(); }
        echo '<div class="tcm-card tcm-join-card"><h3>' . esc_html($this->opt('join_title') ?: 'הצטרפות לקריאה') . '</h3><form class="tcm-form tcm-join-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="tcm_join"><input type="hidden" name="campaign_id" value="' . esc_attr($id) . '">';
        wp_nonce_field(self::NONCE_ACTION, 'tcm_nonce');
        echo '<div class="tcm-select-row"><div class="tcm-field"><label>בחירת פרק / כמה פרקים</label><select class="tcm-chapter-select" name="chapter_number" required><option value="0">בחירה אוטומטית בפרק פנוי</option>';
        foreach ($free as $ch) echo '<option value="' . esc_attr($ch->chapter_number) . '">פרק ' . esc_html($this->chapter_label($ch->chapter_number)) . '</option>';
        if ($allow_multi) {
            echo '<option disabled>────────────</option>';
            foreach ($multi_options as $n) {
                if ($n > 1 && $free_count >= $n) echo '<option value="multi:' . esc_attr($n) . '">קח ' . esc_html($n) . ' פרקים</option>';
            }
        }
        if ($allow_full) {
            echo '<option disabled>────────────</option><option value="book:150">קח ספר שלם (150 פרקים)</option>';
        }
        echo '</select></div></div><div class="tcm-fields-row tcm-contact-fields"><div class="tcm-field"><label>שם <span class="tcm-muted">רשות</span></label><input type="text" name="participant_name" autocomplete="name"></div><div class="tcm-field"><label>אימייל <span class="tcm-muted">רשות</span></label><input type="email" name="participant_email" autocomplete="email"></div><div class="tcm-field"><label>טלפון <span class="tcm-muted">רשות</span></label><input type="tel" name="participant_phone" autocomplete="tel"></div></div><p class="tcm-muted tcm-privacy-note">הפרטים משמשים לקבלת הפרק ותזכורות בלבד, ואינם חובה.</p>' . $this->turnstile_widget() . '<button class="tcm-btn tcm-submit-btn" type="submit">' . esc_html($this->opt('join_button_text') ?: 'הצטרפות לקריאה') . '</button></form></div>';
        return ob_get_clean();
    }

    private function get_free_chapters($campaign_id) { global $wpdb; $round = $this->current_round($campaign_id); return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status='free' ORDER BY chapter_number ASC", $campaign_id, $round)); }

    public function shortcode_chapters($atts) {
        $atts = shortcode_atts(['id'=>get_the_ID()], $atts); $id = absint($atts['id']); if (!$id) return '';
        global $wpdb; $round = $this->current_round($id);
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d ORDER BY chapter_number ASC", $id, $round));
        ob_start(); echo '<div class="tcm-card"><h3>מצב הפרקים בספר הנוכחי</h3><div class="tcm-grid">';
        foreach ($rows as $r) { $title = $r->status === 'done' ? 'הושלם' : ($r->status === 'taken' ? 'נתפס' : 'פנוי'); echo '<div class="tcm-chapter ' . esc_attr($r->status) . '" title="' . esc_attr($title) . '">' . esc_html($this->chapter_label($r->chapter_number)) . '</div>'; }
        echo '</div><p class="tcm-muted">אפור = פנוי, צהוב = נתפס, ירוק = הושלם.</p></div>'; return ob_get_clean();
    }

    public function shortcode_campaigns($atts) {
        $q = new WP_Query(['post_type'=>self::CPT,'post_status'=>'publish','posts_per_page'=>-1]);
        ob_start(); echo '<div class="tcm-wrap"><div class="tcm-campaign-list">';
        while ($q->have_posts()) { $q->the_post(); $id=get_the_ID(); $s=$this->stats($id); echo '<a class="tcm-card" style="display:block;text-decoration:none;color:inherit" href="' . esc_url(get_permalink()) . '"><span class="tcm-badge">' . esc_html(get_the_title()) . '</span><h3>חלוקת תהילים</h3><div class="tcm-progress"><span style="width:' . esc_attr($s['percent']) . '%"></span></div><p>יעד בסיס: ' . esc_html($s['base_completed']) . ' מתוך ' . esc_html($s['target']) . ' ספרים</p>'; if ($s['bonus'] > 0 || $s['bonus_completed'] > 0) echo '<p>בונוס: ' . esc_html($s['bonus_completed']) . ' מתוך ' . esc_html($s['bonus']) . ' ספרים</p>'; echo '<span class="tcm-btn">הצטרפות לקריאה</span></a>'; }
        wp_reset_postdata(); echo '</div></div>'; return ob_get_clean();
    }

    public function shortcode_create_campaign_form($atts) {
        ob_start();
        if (!empty($_GET['tcm_created'])) echo '<div class="tcm-wrap"><div class="tcm-notice">הקמפיין נוצר בהצלחה.</div></div>';
        echo '<div class="tcm-wrap"><div class="tcm-card"><h3>פתיחת קמפיין תהילים</h3><form class="tcm-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="tcm_create_campaign">';
        wp_nonce_field(self::NONCE_ACTION, 'tcm_nonce');
        echo '<label>הקדשה / לרפואה / לעילוי נשמת</label><input type="text" name="campaign_title" required><label>כמה ספרים ביעד הבסיס?</label><input type="number" name="target_books" min="1" value="1" required><label>תיאור קצר <span class="tcm-muted">רשות</span></label><textarea name="campaign_content" rows="4"></textarea><button class="tcm-btn" type="submit">יצירת קמפיין</button></form></div></div>';
        return ob_get_clean();
    }

    public function handle_create_campaign() {
        if (!isset($_POST['tcm_nonce']) || !wp_verify_nonce($_POST['tcm_nonce'], self::NONCE_ACTION)) wp_die('בקשה לא תקינה');
        $title = sanitize_text_field($_POST['campaign_title'] ?? '');
        $target = max(1, absint($_POST['target_books'] ?? 1));
        $content = wp_kses_post($_POST['campaign_content'] ?? '');
        if (!$title) wp_die('חסר שם קמפיין');
        $status = 'publish';
        $post_id = wp_insert_post(['post_type'=>self::CPT,'post_status'=>$status,'post_title'=>$title,'post_content'=>$content,'post_author'=>get_current_user_id(),'post_name'=>'tehillim-campaign-' . time() . '-' . wp_rand(100,999)], true);
        if (is_wp_error($post_id)) wp_die($post_id->get_error_message());
        update_post_meta($post_id, '_tcm_target_books', $target);
        update_post_meta($post_id, '_tcm_bonus_books', 0);
        update_post_meta($post_id, '_tcm_status', 'active');
        $this->generate_round($post_id, 1);
        $this->send_webhook('campaign_created', ['campaign_id'=>$post_id, 'campaign_title'=>$title, 'target_books'=>$target, 'permalink'=>get_permalink($post_id)]);
        $this->send_creator_campaign_created_email($post_id);
        wp_safe_redirect(add_query_arg('tcm_created', '1', get_permalink($post_id))); exit;
    }

    public function handle_join() {
        if (!isset($_POST['tcm_nonce']) || !wp_verify_nonce($_POST['tcm_nonce'], self::NONCE_ACTION)) wp_die('בקשה לא תקינה');
        if (!$this->verify_turnstile()) wp_die('אימות האבטחה נכשל. נסו שוב.');
        $campaign_id = absint($_POST['campaign_id'] ?? 0);
        $raw_choice = sanitize_text_field($_POST['chapter_number'] ?? '0');
        $name = sanitize_text_field($_POST['participant_name'] ?? '');
        $email = sanitize_email($_POST['participant_email'] ?? '');
        $phone = sanitize_text_field($_POST['participant_phone'] ?? '');
        if (!$name) $name = 'משתתף';
        if ($email && !is_email($email)) wp_die('האימייל שהוזן אינו תקין');
        if (!$campaign_id) wp_die('חסר קמפיין');
        $token = wp_generate_password(32, false, false);
        $rows = [];
        if (strpos($raw_choice, 'multi:') === 0) {
            $count = max(2, min(150, absint(substr($raw_choice, 6))));
            $rows = $this->claim_multiple_chapters($campaign_id, $count, $name, $email, $phone, $token);
            $event = 'multi_chapters_taken';
        } elseif (strpos($raw_choice, 'book:') === 0) {
            $rows = $this->claim_full_book($campaign_id, $name, $email, $phone, $token);
            $event = 'full_book_taken';
        } else {
            $chapter = absint($raw_choice);
            $row = $this->claim_free_chapter($campaign_id, $chapter, $name, $email, $phone, $token);
            if ($row) $rows = [$row];
            $event = 'chapter_taken';
        }
        if (!$rows) {
            $msg = (strpos($raw_choice, 'book:') === 0) ? 'no_full_book' : ((absint($raw_choice) > 0) ? 'taken' : 'full');
            wp_safe_redirect(add_query_arg('tcm_msg', $msg, get_permalink($campaign_id)) . '#tcm'); exit;
        }
        $first = $rows[0];
        if (count($rows) === 1) $this->record_ambassador_referral($campaign_id, (int)$first->id, $email);
        $read_url = $this->read_url($campaign_id, $first->id, $token);
        $done_url = $this->done_url($first->id, $token);
        $more_url = $this->take_more_url($first->id, $token);
        if (count($rows) === 1) {
            $this->maybe_send_email($email, $name, $campaign_id, $first->chapter_number, $read_url, $done_url, $more_url);
        } else {
            $this->maybe_send_multi_email($email, $name, $campaign_id, $rows, $read_url, $more_url, $event);
        }
        $payload = $this->assignment_payload((int)$first->id, $token);
        $payload['assigned_count'] = count($rows);
        $payload['chapter_numbers'] = array_map(function($r){ return (int)$r->chapter_number; }, $rows);
        $this->send_webhook($event, $payload);
        $this->log_event($event, $campaign_id, (int)$first->id, $payload);
        wp_safe_redirect($read_url); exit;
    }

    private function claim_full_book($campaign_id, $name, $email, $phone, $token) {
        global $wpdb;
        $table = $this->table_name();
        $round = $this->find_empty_full_book_round($campaign_id);
        if ($round < 1) return false;

        $exists = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d",
            $campaign_id, $round
        ));
        if ($exists === 0) {
            $this->generate_round($campaign_id, $round);
        }

        $free = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d AND status='free'",
            $campaign_id, $round
        ));
        $taken_or_done = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d AND status IN ('taken','done')",
            $campaign_id, $round
        ));

        if ($free !== 150 || $taken_or_done !== 0) return false;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE campaign_id=%d AND round_number=%d AND status='free' ORDER BY chapter_number ASC",
            $campaign_id, $round
        ));
        if (!$rows || count($rows) !== 150) return false;

        $now = current_time('mysql');
        $claimed = [];
        foreach ($rows as $row) {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE $table SET status='taken', participant_name=%s, participant_email=%s, participant_phone=%s, dedication='', token=%s, taken_at=%s, completed_at=NULL, reminder_count=0, last_reminder_at=NULL, release_notice_at=NULL, released_at=NULL, updated_at=%s WHERE id=%d AND status='free'",
                $name, $email, $phone, $token, $now, $now, $row->id
            ));
            if ($updated !== 1) {
                foreach ($claimed as $c) {
                    $wpdb->update(
                        $table,
                        ['status'=>'free','participant_name'=>null,'participant_email'=>null,'participant_phone'=>null,'token'=>null,'taken_at'=>null,'completed_at'=>null,'updated_at'=>$now],
                        ['id'=>(int)$c->id],
                        ['%s','%s','%s','%s','%s','%s','%s','%s'],
                        ['%d']
                    );
                }
                return false;
            }
            $claimed[] = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $row->id));
        }

        return $claimed;
    }

    private function claim_free_chapter($campaign_id, $preferred_chapter, $name, $email, $phone, $token) {
        $rows = $this->claim_multiple_chapters($campaign_id, 1, $name, $email, $phone, $token, false, $preferred_chapter);
        return $rows ? $rows[0] : false;
    }

    private function claim_multiple_chapters($campaign_id, $count, $name, $email, $phone, $token, $require_full = false, $preferred_chapter = 0) {
        global $wpdb;
        $table = $this->table_name();
        $round = $this->current_round($campaign_id);
        $now = current_time('mysql');
        $count = max(1, min(150, absint($count)));
        if ($preferred_chapter > 0 && $count === 1) {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE campaign_id=%d AND round_number=%d AND chapter_number=%d AND status='free' LIMIT 1", $campaign_id, $round, $preferred_chapter));
        } else {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE campaign_id=%d AND round_number=%d AND status='free' ORDER BY chapter_number ASC LIMIT %d", $campaign_id, $round, $count));
        }
        if (!$rows || count($rows) < $count) return false;
        if ($require_full && count($rows) < 150) return false;
        $claimed = [];
        foreach ($rows as $row) {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE $table SET status='taken', participant_name=%s, participant_email=%s, participant_phone=%s, dedication='', token=%s, taken_at=%s, completed_at=NULL, reminder_count=0, last_reminder_at=NULL, release_notice_at=NULL, released_at=NULL, updated_at=%s WHERE id=%d AND status='free'",
                $name, $email, $phone, $token, $now, $now, $row->id
            ));
            if ($updated !== 1) {
                foreach ($claimed as $c) {
                    $wpdb->update($table, ['status'=>'free','participant_name'=>null,'participant_email'=>null,'participant_phone'=>null,'token'=>null,'taken_at'=>null,'updated_at'=>$now], ['id'=>(int)$c->id], ['%s','%s','%s','%s','%s','%s','%s'], ['%d']);
                }
                return false;
            }
            $claimed[] = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $row->id));
        }
        return $claimed;
    }

    private function maybe_send_multi_email($email, $name, $campaign_id, $rows, $read_url, $more_url, $event) {
        if (!$email) return;
        $chapters = implode(', ', array_map(function($r){ return $this->chapter_label($r->chapter_number); }, $rows));
        $subject = $event === 'full_book_taken' ? ($this->opt('full_book_email_subject') ?: 'קיבלת ספר תהילים שלם לקריאה') : ($this->opt('multi_email_subject') ?: 'קיבלת כמה פרקי תהילים לקריאה');
        $body = $event === 'full_book_taken' ? ($this->opt('full_book_email_body') ?: "שלום {name},\n\nקיבלת ספר תהילים שלם בקמפיין {campaign_title}.\n\nלהתחלת הקריאה:\n{read_url}") : ($this->opt('multi_email_body') ?: "שלום {name},\n\nקיבלת את הפרקים: {chapters}\n\nלהתחלת הקריאה:\n{read_url}");
        $replacements = $this->placeholders(['name'=>$name,'campaign_title'=>get_the_title($campaign_id),'chapters'=>$chapters,'count'=>count($rows),'read_url'=>$read_url,'take_more_url'=>$more_url]);
        $this->send_html_email($email, $subject, $body, $replacements, $event . '_email', ['campaign_id'=>$campaign_id,'campaign_title'=>get_the_title($campaign_id),'participant_name'=>$name,'participant_email'=>$email,'chapters'=>$chapters,'count'=>count($rows),'read_url'=>$read_url,'take_more_url'=>$more_url]);
    }

    private function maybe_send_email($email, $name, $campaign_id, $chapter, $read_url, $done_url, $more_url) {
        if (!$email) return;
        $subject = $this->opt('email_subject');
        $body = $this->opt('email_body');
        $replacements = $this->placeholders(['name'=>$name,'campaign_title'=>get_the_title($campaign_id),'chapter'=>$this->chapter_label($chapter),'chapter_number'=>$chapter,'read_url'=>$read_url,'done_url'=>$done_url,'take_more_url'=>$more_url]);
        $this->send_html_email($email, $subject, $body, $replacements, 'chapter_taken_email', ['campaign_id'=>$campaign_id,'campaign_title'=>get_the_title($campaign_id),'chapter_number'=>$chapter,'chapter_label'=>$this->chapter_label($chapter),'participant_name'=>$name,'participant_email'=>$email,'read_url'=>$read_url,'done_url'=>$done_url,'take_more_url'=>$more_url]);
    }

    public function handle_done() {
        $assignment_id = absint($_GET['assignment_id'] ?? $_POST['assignment_id'] ?? 0); $token = sanitize_text_field($_GET['token'] ?? $_POST['token'] ?? '');
        if (!$assignment_id || !$token) wp_die('קישור לא תקין');
        global $wpdb; $table=$this->table_name(); $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND token=%s", $assignment_id, $token));
        if (!$row) wp_die('קישור לא תקין');
        if ($row->status !== 'done') {
            $wpdb->update($table, ['status'=>'done','completed_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')], ['id'=>$assignment_id], ['%s','%s','%s'], ['%d']);
            $this->maybe_complete_round((int)$row->campaign_id, (int)$row->round_number);
            $this->maybe_complete_personal_full_book((int)$row->campaign_id, (int)$row->round_number, $token);
            $payload = $this->assignment_payload($assignment_id, $token);
            $this->send_webhook('chapter_done', $payload);
            $this->log_event('chapter_done', (int)$row->campaign_id, $assignment_id, $payload);
        }
        $next = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE campaign_id=%d AND round_number=%d AND token=%s AND status='taken' ORDER BY chapter_number ASC LIMIT 1", (int)$row->campaign_id, (int)$row->round_number, $token));
        if ($next) { wp_safe_redirect($this->read_url((int)$row->campaign_id, (int)$next->id, $token)); exit; }
        wp_safe_redirect(add_query_arg('tcm_msg','done_all', get_permalink((int)$row->campaign_id)) . '#tcm'); exit;
    }
    public function handle_take_more() {
        $assignment_id = absint($_GET['assignment_id'] ?? 0); $token = sanitize_text_field($_GET['token'] ?? '');
        global $wpdb; $table=$this->table_name(); $old = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND token=%s", $assignment_id, $token));
        if (!$old) wp_die('קישור לא תקין');
        if ($old->status !== 'done') {
            $wpdb->update($table, ['status'=>'done','completed_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')], ['id'=>$assignment_id], ['%s','%s','%s'], ['%d']);
            $this->maybe_complete_round((int)$old->campaign_id, (int)$old->round_number);
            $this->maybe_complete_personal_full_book((int)$old->campaign_id, (int)$old->round_number, $token);
        }
        $new_token = wp_generate_password(32, false, false);
        $row = $this->claim_free_chapter((int)$old->campaign_id, 0, (string)$old->participant_name, (string)$old->participant_email, (string)$old->participant_phone, $new_token);
        if (!$row) { wp_safe_redirect(add_query_arg('tcm_msg','full', get_permalink((int)$old->campaign_id)) . '#tcm'); exit; }
        $read_url = $this->read_url((int)$old->campaign_id, $row->id, $new_token);
        $this->maybe_send_email($row->participant_email, $row->participant_name, (int)$old->campaign_id, (int)$row->chapter_number, $read_url, $this->done_url($row->id, $new_token), $this->take_more_url($row->id, $new_token));
        $this->send_webhook('chapter_taken_more', $this->assignment_payload($row->id, $new_token)); $this->log_event('chapter_taken_more', (int)$old->campaign_id, (int)$row->id, $this->assignment_payload($row->id, $new_token));
        wp_safe_redirect($read_url); exit;
    }

    private function maybe_complete_personal_full_book($campaign_id, $round, $token) {
        if (!$token) return;
        global $wpdb;
        $table = $this->table_name();

        $total = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d AND token=%s",
            $campaign_id, $round, $token
        ));
        if ($total !== 150) return;

        $not_done = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d AND token=%s AND status!='done'",
            $campaign_id, $round, $token
        ));
        if ($not_done !== 0) return;

        $flag = 'tcm_full_book_done_' . md5($campaign_id . '|' . $round . '|' . $token);
        if (get_option($flag)) return;
        add_option($flag, current_time('mysql'), '', false);

        $first = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE campaign_id=%d AND round_number=%d AND token=%s ORDER BY chapter_number ASC LIMIT 1",
            $campaign_id, $round, $token
        ));

        $payload = [
            'campaign_id' => (int)$campaign_id,
            'campaign_title' => get_the_title($campaign_id),
            'round_number' => (int)$round,
            'participant_name' => $first ? (string)$first->participant_name : '',
            'participant_email' => $first ? (string)$first->participant_email : '',
            'participant_phone' => $first ? (string)$first->participant_phone : '',
            'chapters_count' => 150,
            'permalink' => get_permalink($campaign_id),
        ];

        $this->send_webhook('full_book_completed_by_participant', $payload);
        $this->log_event('full_book_completed_by_participant', $campaign_id, $first ? (int)$first->id : 0, $payload);

        if ($first && !empty($first->participant_email)) {
            $subject = $this->opt('full_book_completed_email_subject') ?: 'סיימת ספר תהילים שלם';
            $body = $this->opt('full_book_completed_email_body') ?: "שלום {name},\n\nיישר כוח! סיימת ספר תהילים שלם בקמפיין {campaign_title}.\n\nאפשר לקחת עוד פרק מכאן:\n{take_more_url}";
            $this->send_html_email(
                $first->participant_email,
                $subject,
                $body,
                $this->placeholders([
                    'name' => $first->participant_name,
                    'campaign_title' => get_the_title($campaign_id),
                    'take_more_url' => get_permalink($campaign_id) . '#tcm',
                ]),
                'full_book_completed_by_participant_email',
                $payload
            );
        }
    }

    private function maybe_complete_round($campaign_id, $round) {
        global $wpdb; $table=$this->table_name();
        $not_done = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE campaign_id=%d AND round_number=%d AND status!='done'", $campaign_id, $round));
        if ($not_done === 0) {
            $s = $this->stats($campaign_id);
            $this->send_book_completed_notice($campaign_id, $round, $s);
            $this->send_webhook('book_completed', ['campaign_id'=>$campaign_id,'campaign_title'=>get_the_title($campaign_id),'round_number'=>$round,'completed_books'=>$s['completed_books'],'permalink'=>get_permalink($campaign_id)]);
            if ($s['completed_books'] >= $s['goal_total']) {
                update_post_meta($campaign_id, '_tcm_status', 'completed');
                $this->send_webhook('campaign_completed', ['campaign_id'=>$campaign_id,'campaign_title'=>get_the_title($campaign_id),'permalink'=>get_permalink($campaign_id)]);
            } else {
                $this->generate_round($campaign_id, $round + 1);
            }
        }
    }

    private function render_reader($assignment_id, $token) {
        global $wpdb; $table = $this->table_name();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND token=%s", $assignment_id, $token));
        if (!$row) return '<div class="tcm-card" id="tcm-read">קישור הקריאה אינו תקין.</div>';
        $all = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE campaign_id=%d AND round_number=%d AND token=%s ORDER BY chapter_number ASC", (int)$row->campaign_id, (int)$row->round_number, $token));
        $total = max(1, count($all)); $index = 1; $done_count = 0;
        foreach ($all as $i => $r) { if ((int)$r->id === (int)$row->id) $index = $i + 1; if ($r->status === 'done') $done_count++; }
        $chapter_label = $this->chapter_label($row->chapter_number);
        $chapter_html = $this->get_chapter_content((int)$row->chapter_number);
        ob_start();
        echo '<div class="tcm-card" id="tcm-read"><h3>הפרק שלך: תהילים פרק ' . esc_html($chapter_label) . '</h3>';
        if ($total > 1) echo '<p class="tcm-badge">פרק ' . esc_html($index) . ' מתוך ' . esc_html($total) . '</p><p class="tcm-muted">סיימת כבר ' . esc_html($done_count) . ' מתוך ' . esc_html($total) . ' פרקים שלקחת.</p>';
        echo '<p class="tcm-muted">אפשר לקרוא כאן, ובסיום לסמן שהפרק הושלם. אם לקחת כמה פרקים, אחרי הסימון תעבור/י אוטומטית לפרק הבא.</p>';
        if ($total > 1) { echo '<div class="tcm-my-chapters"><strong>הפרקים שקיבלת:</strong> '; $labels=[]; foreach($all as $r){ $labels[] = ($r->status === 'done' ? '✓ ' : '') . 'פרק ' . $this->chapter_label($r->chapter_number); } echo esc_html(implode(' · ', $labels)) . '</div>'; }
        if ($chapter_html) echo '<div class="tcm-chapter-text">' . $chapter_html . '</div>'; else echo '<div class="tcm-notice">עדיין לא הוזן טקסט לפרק הזה באתר. אפשר להזין אותו דרך: קמפייני תהילים → פרקי תהילים.</div>';
        echo '<div class="tcm-actions"><a class="tcm-btn tcm-secondary" href="' . esc_url($this->done_url($row->id, $token)) . '">סיימתי את הפרק</a><a class="tcm-btn" href="' . esc_url($this->take_more_url($row->id, $token)) . '">סיימתי ורוצה לקחת עוד פרק</a></div></div>';
        return ob_get_clean();
    }
    private function get_chapter_content($chapter_number) {
        $chapter_number = absint($chapter_number);
        if (!$chapter_number || $chapter_number > 150) return '';
        $chapters = get_option('tcm_chapters', []);
        if (!empty($chapters[$chapter_number])) return wp_kses_post(wpautop($chapters[$chapter_number]));
        $upload = wp_upload_dir();
        $file = trailingslashit($upload['basedir']) . 'tcm-tehillim/' . sprintf('%03d.html', $chapter_number);
        if (file_exists($file) && is_readable($file)) return wp_kses_post(file_get_contents($file));
        return '';
    }

    private function read_url($campaign_id, $assignment_id, $token) { return add_query_arg(['tcm_read'=>$assignment_id,'token'=>$token], get_permalink($campaign_id)) . '#tcm-read'; }
    private function action_url($type, $assignment_id, $token) { return home_url('/' . $this->campaign_base_slug() . '/action/' . sanitize_key($type) . '/' . absint($assignment_id) . '/' . rawurlencode($token) . '/'); }
    private function done_url($assignment_id, $token) { return $this->action_url('done', $assignment_id, $token); }
    private function take_more_url($assignment_id, $token) { return $this->action_url('take-more', $assignment_id, $token); }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=' . self::CPT, 'ניהול חלוקות', 'ניהול חלוקות', 'manage_options', 'tcm-dashboard', [$this, 'admin_dashboard']);
        add_submenu_page('edit.php?post_type=' . self::CPT, 'הגדרות וובהוקים', 'הגדרות וובהוקים', 'manage_options', 'tcm-settings', [$this, 'settings_page']);
        add_submenu_page('edit.php?post_type=' . self::CPT, 'פרקי תהילים', 'פרקי תהילים', 'manage_options', 'tcm-chapters', [$this, 'chapters_page']);
        add_submenu_page('edit.php?post_type=' . self::CPT, 'הודעות לאישור', 'הודעות לאישור', 'manage_options', 'tcm-pending-messages', [$this, 'pending_messages_page']);
        add_submenu_page('edit.php?post_type=' . self::CPT, 'מדריך שימוש ושורטקודים', 'מדריך שימוש', 'manage_options', 'tcm-guide', [$this, 'guide_page']);
        add_submenu_page('edit.php?post_type=' . self::CPT, 'לוג פעולות', 'לוג פעולות', 'manage_options', 'tcm-logs', [$this, 'logs_page']);
    }

    public function admin_dashboard() {
        $campaigns = get_posts(['post_type'=>self::CPT,'posts_per_page'=>-1,'post_status'=>'any']);
        echo '<div class="wrap" dir="rtl"><h1>ניהול חלוקות תהילים</h1><p>ארכיון כללי: <code>[tehillim_campaigns]</code> | טופס יצירת קמפיין: <code>[tehillim_create_campaign_form]</code></p><table class="tcm-admin-table"><thead><tr><th>קמפיין / הקדשה</th><th>יעד בסיס</th><th>בונוס</th><th>הושלמו</th><th>ספר נוכחי</th><th>פעולות</th></tr></thead><tbody>';
        foreach ($campaigns as $c) { $s=$this->stats($c->ID); echo '<tr><td><a href="' . esc_url(get_edit_post_link($c->ID)) . '">' . esc_html($c->post_title) . '</a><br><a href="' . esc_url(get_permalink($c->ID)) . '" target="_blank">צפייה בעמוד הקמפיין</a></td><td>' . esc_html($s['target']) . '</td><td>' . esc_html($s['bonus']) . '</td><td>' . esc_html($s['completed_books']) . '</td><td>' . esc_html($s['round']) . '</td><td><a class="button" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=tcm_generate_round&campaign_id='.$c->ID), 'tcm_admin_action')) . '">הוסף ספר בונוס</a> <a class="button" onclick="return confirm(\'לאפס את כל החלוקה?\')" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=tcm_reset_campaign&campaign_id='.$c->ID), 'tcm_admin_action')) . '">איפוס מלא</a></td></tr>'; }
        echo '</tbody></table><h2>שדות מטה ליצירת קמפיין מטופס חיצוני</h2><p><code>post_title</code> = ההקדשה הראשית, <code>post_type</code> = <code>' . esc_html(self::CPT) . '</code>, <code>_tcm_target_books</code> = יעד בסיס, <code>_tcm_bonus_books</code> = בונוס, <code>_tcm_status</code> = active.</p></div>';
    }



    public function logs_page() {
        if (!current_user_can('manage_options')) wp_die('אין הרשאה');
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->log_table_name()} ORDER BY id DESC LIMIT 200");
        echo '<div class="wrap" dir="rtl"><h1>לוג פעולות</h1><p>200 הפעולות האחרונות שבוצעו בתוסף.</p><table class="widefat striped"><thead><tr><th>זמן</th><th>אירוע</th><th>קמפיין</th><th>חלוקה</th><th>IP</th><th>נתונים</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $campaign = $r->campaign_id ? '<a href="' . esc_url(get_permalink((int)$r->campaign_id)) . '" target="_blank">' . esc_html(get_the_title((int)$r->campaign_id)) . '</a>' : '';
            echo '<tr><td>' . esc_html($r->created_at) . '</td><td><code>' . esc_html($r->event) . '</code></td><td>' . $campaign . '</td><td>' . esc_html($r->assignment_id) . '</td><td>' . esc_html($r->ip) . '</td><td><textarea readonly rows="2" class="large-text code" style="direction:ltr;text-align:left">' . esc_textarea($r->data) . '</textarea></td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function shortcode_my_campaigns($atts) {
        if (!is_user_logged_in()) return '<div class="tcm-wrap"><div class="tcm-card">כדי לראות את הקמפיינים שפתחתם צריך להתחבר לאתר.</div></div>';
        $q = new WP_Query(['post_type'=>self::CPT,'post_status'=>['publish','pending','draft'],'posts_per_page'=>-1,'author'=>get_current_user_id()]);
        ob_start();
        echo '<div class="tcm-wrap tcm-my-campaigns"><h2>הקמפיינים שלי</h2>';
        if (!empty($_GET['tcm_owner_msg'])) echo '<div class="tcm-notice">' . esc_html(sanitize_text_field($_GET['tcm_owner_msg'])) . '</div>';
        echo '<div class="tcm-campaign-list">';
        if (!$q->have_posts()) echo '<div class="tcm-card">עדיין לא נפתחו קמפיינים.</div>';
        while ($q->have_posts()) {
            $q->the_post();
            $id=get_the_ID(); $s=$this->stats($id); $link=get_permalink($id); $desc=get_post_field('post_content',$id);
            echo '<div class="tcm-card"><h3>' . esc_html(get_the_title($id)) . '</h3>';
            if ($desc) echo '<div class="tcm-muted">' . wp_kses_post(wp_trim_words(wp_strip_all_tags($desc), 24)) . '</div>';
            echo '<div class="tcm-progress"><span style="width:' . esc_attr($s['percent']) . '%"></span></div><p>יעד בסיס: ' . esc_html($s['base_completed']) . ' מתוך ' . esc_html($s['target']) . ' ספרים</p><p>ספר נוכחי: ' . esc_html($s['round']) . '</p>' . $this->share_box($id) . '<p><a class="tcm-btn" href="' . esc_url($link) . '">כניסה לקמפיין</a></p>';

            echo '<div class="tcm-manager"><h4>ניהול הקמפיין</h4>';
            echo '<form class="tcm-form tcm-inline-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="tcm_owner_update_campaign"><input type="hidden" name="campaign_id" value="' . esc_attr($id) . '">';
            wp_nonce_field(self::NONCE_ACTION, 'tcm_nonce');
            echo '<label>כותרת / הקדשה</label><input type="text" name="campaign_title" value="' . esc_attr(get_the_title($id)) . '" required>';
            echo '<label>תיאור</label><textarea name="campaign_content">' . esc_textarea(get_post_field('post_content',$id)) . '</textarea>';
            echo '<label>יעד בסיס</label><input type="number" min="1" name="target_books" value="' . esc_attr($s['target']) . '">';
            echo '<button class="tcm-btn" type="submit">שמירת שינויים</button></form>';

            echo '<form class="tcm-inline-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="tcm_owner_add_bonus"><input type="hidden" name="campaign_id" value="' . esc_attr($id) . '">';
            wp_nonce_field(self::NONCE_ACTION, 'tcm_nonce');
            echo '<button class="tcm-btn tcm-secondary" type="submit">הוספת ספר בונוס אחד</button></form>';

            echo '<form class="tcm-form tcm-inline-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="tcm_owner_send_message"><input type="hidden" name="campaign_id" value="' . esc_attr($id) . '">';
            wp_nonce_field(self::NONCE_ACTION, 'tcm_nonce');
            echo '<label>הכנת מייל לכל משתתפי הקמפיין</label><p class="tcm-muted">המייל לא נשלח מיד. הוא יישמר לאישור מנהל האתר.</p><input type="text" name="message_subject" placeholder="נושא ההודעה" required><textarea name="message_body" placeholder="תוכן ההודעה" required></textarea><button class="tcm-btn" type="submit">שליחה לאישור מנהל</button></form>';
            echo '</div></div>';
        }
        wp_reset_postdata();
        echo '</div></div>';
        return ob_get_clean();
    }

    private function share_box($campaign_id) {
        $link = get_permalink($campaign_id);
        $title = get_the_title($campaign_id);
        $wa = 'https://wa.me/?text=' . rawurlencode($title . ' - ' . $link);
        $mail = 'mailto:?subject=' . rawurlencode($title) . '&body=' . rawurlencode($title . "\n" . $link);
        return '<div class="tcm-share-box"><label><strong>קישור לשיתוף:</strong></label><input readonly value="' . esc_attr($link) . '"><div class="tcm-share-actions"><button type="button" class="tcm-btn tcm-secondary" data-tcm-copy="' . esc_attr($link) . '">העתקת קישור</button><a class="tcm-btn tcm-secondary" target="_blank" rel="noopener" href="' . esc_url($wa) . '">שיתוף בוואטסאפ</a><a class="tcm-btn" href="' . esc_url($mail) . '">שיתוף במייל</a></div></div>';
    }

    private function get_pending_messages() {
        $messages = get_option('tcm_pending_messages', []);
        return is_array($messages) ? $messages : [];
    }

    private function save_pending_messages($messages) {
        update_option('tcm_pending_messages', array_values($messages), false);
    }
    public function chapters_page() {
        if (!current_user_can('manage_options')) wp_die('אין הרשאה');
        $chapters = get_option('tcm_chapters', []);
        echo '<div class="wrap" dir="rtl"><h1>פרקי תהילים לקריאה באתר</h1><p>אפשר להזין כאן את טקסט הפרקים. כל פרק נשמר מקומית באתר ומשמש בעמוד הקריאה במקום מקור חיצוני.</p><p>אפשרות חלופית: להעלות קבצי HTML לתיקייה <code>wp-content/uploads/tcm-tehillim/</code> בשמות <code>001.html</code> עד <code>150.html</code>.</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="tcm_save_chapters">';
        wp_nonce_field('tcm_save_chapters', 'tcm_chapters_nonce');
        echo '<p><button class="button button-primary">שמירת כל הפרקים</button></p>';
        for ($i=1; $i<=150; $i++) {
            echo '<details style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:10px;margin:8px 0"><summary><strong>תהילים פרק ' . esc_html($this->chapter_label($i)) . '</strong></summary><textarea name="tcm_chapters[' . esc_attr($i) . ']" rows="8" class="large-text" style="direction:rtl;text-align:right;margin-top:10px">' . esc_textarea($chapters[$i] ?? '') . '</textarea></details>';
        }
        echo '<p><button class="button button-primary">שמירת כל הפרקים</button></p></form></div>';
    }

    public function handle_save_chapters() {
        if (!current_user_can('manage_options') || !isset($_POST['tcm_chapters_nonce']) || !wp_verify_nonce($_POST['tcm_chapters_nonce'], 'tcm_save_chapters')) wp_die('אין הרשאה');
        $raw = $_POST['tcm_chapters'] ?? [];
        $clean = [];
        for ($i=1; $i<=150; $i++) {
            if (!empty($raw[$i])) $clean[$i] = wp_kses_post(wp_unslash($raw[$i]));
        }
        update_option('tcm_chapters', $clean, false);
        wp_safe_redirect(admin_url('edit.php?post_type=' . self::CPT . '&page=tcm-chapters&updated=1'));
        exit;
    }
    public function handle_reset_campaign() {
        if (!current_user_can('manage_options') || !check_admin_referer('tcm_admin_action')) wp_die('אין הרשאה');
        $campaign_id=absint($_GET['campaign_id']??0); global $wpdb; $wpdb->delete($this->table_name(), ['campaign_id'=>$campaign_id], ['%d']); $this->generate_round($campaign_id,1); update_post_meta($campaign_id,'_tcm_status','active'); update_post_meta($campaign_id,'_tcm_bonus_books',0); wp_safe_redirect(admin_url('edit.php?post_type='.self::CPT.'&page=tcm-dashboard')); exit;
    }

    public function handle_generate_round() {
        if (!current_user_can('manage_options') || !check_admin_referer('tcm_admin_action')) wp_die('אין הרשאה');
        $campaign_id=absint($_GET['campaign_id']??0); if (!$campaign_id) wp_die('קמפיין לא תקין');
        update_post_meta($campaign_id, '_tcm_bonus_books', max(0, (int)get_post_meta($campaign_id, '_tcm_bonus_books', true)) + 1); update_post_meta($campaign_id,'_tcm_status','active');
        global $wpdb; $round=$this->current_round($campaign_id); $not_done=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status!='done'", $campaign_id, $round));
        if ($not_done === 0) $this->generate_round($campaign_id, $round + 1);
        wp_safe_redirect(admin_url('edit.php?post_type='.self::CPT.'&page=tcm-dashboard')); exit;
    }


    private function user_can_manage_campaign($campaign_id) {
        $campaign_id = absint($campaign_id);
        if (!$campaign_id || get_post_type($campaign_id) !== self::CPT) return false;
        if (current_user_can('manage_options')) return true;
        return is_user_logged_in() && (int)get_post_field('post_author', $campaign_id) === get_current_user_id();
    }

    public function handle_owner_update_campaign() {
        if (!is_user_logged_in() || !isset($_POST['tcm_nonce']) || !wp_verify_nonce($_POST['tcm_nonce'], self::NONCE_ACTION)) wp_die('בקשה לא תקינה');
        $campaign_id = absint($_POST['campaign_id'] ?? 0);
        if (!$this->user_can_manage_campaign($campaign_id)) wp_die('אין הרשאה');
        $title = sanitize_text_field($_POST['campaign_title'] ?? '');
        $content = wp_kses_post(wp_unslash($_POST['campaign_content'] ?? ''));
        $target = max(1, absint($_POST['target_books'] ?? 1));
        if (!$title) wp_die('חסרה כותרת');
        wp_update_post(['ID'=>$campaign_id, 'post_title'=>$title, 'post_content'=>$content]);
        update_post_meta($campaign_id, '_tcm_target_books', $target);
        $this->send_webhook('campaign_updated_by_owner', ['campaign_id'=>$campaign_id, 'campaign_title'=>$title, 'target_books'=>$target, 'permalink'=>get_permalink($campaign_id)]);
        wp_safe_redirect(add_query_arg('tcm_owner_msg', rawurlencode('הקמפיין עודכן בהצלחה'), wp_get_referer() ?: get_permalink($campaign_id)));
        exit;
    }

    public function handle_owner_add_bonus() {
        if (!is_user_logged_in() || !isset($_POST['tcm_nonce']) || !wp_verify_nonce($_POST['tcm_nonce'], self::NONCE_ACTION)) wp_die('בקשה לא תקינה');
        $campaign_id = absint($_POST['campaign_id'] ?? 0);
        if (!$this->user_can_manage_campaign($campaign_id)) wp_die('אין הרשאה');
        update_post_meta($campaign_id, '_tcm_bonus_books', max(0, (int)get_post_meta($campaign_id, '_tcm_bonus_books', true)) + 1);
        update_post_meta($campaign_id, '_tcm_status', 'active');
        global $wpdb;
        $round = $this->current_round($campaign_id);
        $not_done = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status!='done'", $campaign_id, $round));
        if ($not_done === 0) $this->generate_round($campaign_id, $round + 1);
        $this->send_webhook('bonus_book_added_by_owner', ['campaign_id'=>$campaign_id, 'campaign_title'=>get_the_title($campaign_id), 'permalink'=>get_permalink($campaign_id)]);
        wp_safe_redirect(add_query_arg('tcm_owner_msg', rawurlencode('נוסף ספר בונוס אחד'), wp_get_referer() ?: get_permalink($campaign_id)));
        exit;
    }

    public function handle_owner_send_message() {
        if (!is_user_logged_in() || !isset($_POST['tcm_nonce']) || !wp_verify_nonce($_POST['tcm_nonce'], self::NONCE_ACTION)) wp_die('בקשה לא תקינה');
        $campaign_id = absint($_POST['campaign_id'] ?? 0);
        if (!$this->user_can_manage_campaign($campaign_id)) wp_die('אין הרשאה');
        $subject = sanitize_text_field($_POST['message_subject'] ?? '');
        $body_raw = wp_kses_post(wp_unslash($_POST['message_body'] ?? ''));
        if (!$subject || !$body_raw) wp_die('חסר נושא או תוכן');
        global $wpdb;
        $recipients_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT participant_email) FROM {$this->table_name()} WHERE campaign_id=%d AND participant_email IS NOT NULL AND participant_email!=''", $campaign_id));
        $messages = $this->get_pending_messages();
        $message_id = time() . '_' . wp_rand(1000, 9999);
        $messages[$message_id] = [
            'id' => $message_id,
            'campaign_id' => $campaign_id,
            'campaign_title' => get_the_title($campaign_id),
            'subject' => $subject,
            'body' => $body_raw,
            'author_id' => get_current_user_id(),
            'author_name' => wp_get_current_user()->display_name,
            'recipients_count' => $recipients_count,
            'created_at' => current_time('mysql'),
            'status' => 'pending',
        ];
        $this->save_pending_messages($messages);
        $this->send_webhook('owner_message_pending_approval', ['campaign_id'=>$campaign_id, 'campaign_title'=>get_the_title($campaign_id), 'recipients_count'=>$recipients_count, 'permalink'=>get_permalink($campaign_id)]);
        wp_safe_redirect(add_query_arg('tcm_owner_msg', rawurlencode('המייל נשמר וממתין לאישור מנהל האתר. הוא מיועד רק למשתתפי הקמפיין הזה.'), wp_get_referer() ?: get_permalink($campaign_id)));
        exit;
    }

    public function pending_messages_page() {
        if (!current_user_can('manage_options')) wp_die('אין הרשאה');
        $messages = $this->get_pending_messages();
        echo '<div class="wrap" dir="rtl"><h1>הודעות לאישור</h1>';
        if (!empty($_GET['updated'])) echo '<div class="notice notice-success"><p>הפעולה בוצעה.</p></div>';
        $has = false;
        foreach ($messages as $m) { if (($m['status'] ?? 'pending') === 'pending') { $has = true; break; } }
        if (!$has) { echo '<p>אין הודעות שממתינות לאישור.</p></div>'; return; }
        echo '<table class="widefat striped"><thead><tr><th>קמפיין</th><th>נושא</th><th>תוכן</th><th>נמענים</th><th>נוצר על ידי</th><th>פעולות</th></tr></thead><tbody>';
        foreach ($messages as $m) {
            if (($m['status'] ?? 'pending') !== 'pending') continue;
            $approve = wp_nonce_url(admin_url('admin-post.php?action=tcm_admin_approve_message&message_id=' . rawurlencode($m['id'])), 'tcm_admin_message_' . $m['id']);
            $reject = wp_nonce_url(admin_url('admin-post.php?action=tcm_admin_reject_message&message_id=' . rawurlencode($m['id'])), 'tcm_admin_message_' . $m['id']);
            echo '<tr><td><a href="' . esc_url(get_permalink((int)$m['campaign_id'])) . '" target="_blank">' . esc_html($m['campaign_title']) . '</a></td><td>' . esc_html($m['subject']) . '</td><td>' . wp_kses_post(wpautop($m['body'])) . '</td><td>' . esc_html((int)$m['recipients_count']) . '</td><td>' . esc_html($m['author_name']) . '<br><small>' . esc_html($m['created_at']) . '</small></td><td><a class="button button-primary" href="' . esc_url($approve) . '" onclick="return confirm(\'לאשר ולשלוח את המייל למשתתפי הקמפיין הזה?\')">אישור ושליחה</a> <a class="button" href="' . esc_url($reject) . '" onclick="return confirm(\'לדחות את ההודעה?\')">דחייה</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }

    private function send_campaign_message_now($message) {
        $campaign_id = absint($message['campaign_id'] ?? 0);
        if (!$campaign_id) return 0;
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT participant_name, participant_email FROM {$this->table_name()} WHERE campaign_id=%d AND participant_email IS NOT NULL AND participant_email!='' GROUP BY participant_email", $campaign_id));
        $sent = 0;
        foreach ($rows as $r) {
            if (!is_email($r->participant_email)) continue;
            $body = str_replace(['{name}','{campaign_title}','{campaign_url}'], [esc_html($r->participant_name), esc_html(get_the_title($campaign_id)), esc_url(get_permalink($campaign_id))], (string)$message['body']);
            if (stripos($body, '<') === false) $body = nl2br(esc_html($body));
            $html = '<!doctype html><html lang="he" dir="rtl"><body dir="rtl" style="direction:rtl;text-align:right;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#111">' . $body . '</body></html>';
            $sent_mail = wp_mail($r->participant_email, (string)$message['subject'], $html, ['Content-Type: text/html; charset=UTF-8']); if ($sent_mail) $this->send_email_webhook('owner_message', ['campaign_id'=>(int)$message['campaign_id'],'campaign_title'=>$message['campaign_title'],'participant_email'=>$r->participant_email,'subject'=>(string)$message['subject']]);
            $sent++;
        }
        return $sent;
    }

    public function handle_admin_approve_message() {
        if (!current_user_can('manage_options')) wp_die('אין הרשאה');
        $message_id = sanitize_text_field($_GET['message_id'] ?? '');
        if (!$message_id || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'tcm_admin_message_' . $message_id)) wp_die('בקשה לא תקינה');
        $messages = $this->get_pending_messages();
        if (empty($messages[$message_id])) wp_die('הודעה לא נמצאה');
        $sent = $this->send_campaign_message_now($messages[$message_id]);
        $this->send_webhook('owner_message_approved_and_sent', ['campaign_id'=>$messages[$message_id]['campaign_id'], 'campaign_title'=>$messages[$message_id]['campaign_title'], 'sent_count'=>$sent, 'permalink'=>get_permalink((int)$messages[$message_id]['campaign_id'])]);
        unset($messages[$message_id]);
        $this->save_pending_messages($messages);
        wp_safe_redirect(admin_url('edit.php?post_type=' . self::CPT . '&page=tcm-pending-messages&updated=1'));
        exit;
    }

    public function handle_admin_reject_message() {
        if (!current_user_can('manage_options')) wp_die('אין הרשאה');
        $message_id = sanitize_text_field($_GET['message_id'] ?? '');
        if (!$message_id || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'tcm_admin_message_' . $message_id)) wp_die('בקשה לא תקינה');
        $messages = $this->get_pending_messages();
        if (!empty($messages[$message_id])) unset($messages[$message_id]);
        $this->save_pending_messages($messages);
        wp_safe_redirect(admin_url('edit.php?post_type=' . self::CPT . '&page=tcm-pending-messages&updated=1'));
        exit;
    }
    private function turnstile_widget() {
        $site_key = trim((string)$this->opt('turnstile_site_key'));
        if (!$site_key) return '';
        wp_enqueue_script('tcm-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true);
        return '<div class="tcm-turnstile cf-turnstile" data-sitekey="' . esc_attr($site_key) . '"></div>';
    }

    private function verify_turnstile() {
        $secret = trim((string)$this->opt('turnstile_secret_key'));
        $site_key = trim((string)$this->opt('turnstile_site_key'));
        if (!$secret || !$site_key) return true;
        $token = sanitize_text_field($_POST['cf-turnstile-response'] ?? '');
        if (!$token) return false;
        $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'timeout' => 10,
            'body' => [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
        ]);
        if (is_wp_error($response)) return false;
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return !empty($data['success']);
    }

    private function chapter_label($num) {
        $map = [1=>'א',2=>'ב',3=>'ג',4=>'ד',5=>'ה',6=>'ו',7=>'ז',8=>'ח',9=>'ט',10=>'י',11=>'יא',12=>'יב',13=>'יג',14=>'יד',15=>'טו',16=>'טז',17=>'יז',18=>'יח',19=>'יט',20=>'כ',21=>'כא',22=>'כב',23=>'כג',24=>'כד',25=>'כה',26=>'כו',27=>'כז',28=>'כח',29=>'כט',30=>'ל',31=>'לא',32=>'לב',33=>'לג',34=>'לד',35=>'לה',36=>'לו',37=>'לז',38=>'לח',39=>'לט',40=>'מ',41=>'מא',42=>'מב',43=>'מג',44=>'מד',45=>'מה',46=>'מו',47=>'מז',48=>'מח',49=>'מט',50=>'נ',51=>'נא',52=>'נב',53=>'נג',54=>'נד',55=>'נה',56=>'נו',57=>'נז',58=>'נח',59=>'נט',60=>'ס',61=>'סא',62=>'סב',63=>'סג',64=>'סד',65=>'סה',66=>'סו',67=>'סז',68=>'סח',69=>'סט',70=>'ע',71=>'עא',72=>'עב',73=>'עג',74=>'עד',75=>'עה',76=>'עו',77=>'עז',78=>'עח',79=>'עט',80=>'פ',81=>'פא',82=>'פב',83=>'פג',84=>'פד',85=>'פה',86=>'פו',87=>'פז',88=>'פח',89=>'פט',90=>'צ',91=>'צא',92=>'צב',93=>'צג',94=>'צד',95=>'צה',96=>'צו',97=>'צז',98=>'צח',99=>'צט',100=>'ק',101=>'קא',102=>'קב',103=>'קג',104=>'קד',105=>'קה',106=>'קו',107=>'קז',108=>'קח',109=>'קט',110=>'קי',111=>'קיא',112=>'קיב',113=>'קיג',114=>'קיד',115=>'קטו',116=>'קטז',117=>'קיז',118=>'קיח',119=>'קיט',120=>'קכ',121=>'קכא',122=>'קכב',123=>'קכג',124=>'קכד',125=>'קכה',126=>'קכו',127=>'קכז',128=>'קכח',129=>'קכט',130=>'קל',131=>'קלא',132=>'קלב',133=>'קלג',134=>'קלד',135=>'קלה',136=>'קלו',137=>'קלז',138=>'קלח',139=>'קלט',140=>'קמ',141=>'קמא',142=>'קמב',143=>'קמג',144=>'קמד',145=>'קמה',146=>'קמו',147=>'קמז',148=>'קמח',149=>'קמט',150=>'קנ'];
        return $map[(int)$num] ?? (string)$num;
    }

    public function register_settings() { register_setting('tcm_settings', 'tcm_options'); }
    private function set_default_options() {
        if (get_option('tcm_options')) return;
        add_option('tcm_options', [
            'link_base'=>'tehillim', 'webhook_url'=>'', 'webhook_secret'=>'', 'turnstile_site_key'=>'', 'turnstile_secret_key'=>'',
            'msg_joined'=>'הפרק נקלט בהצלחה. תודה על ההשתתפות!',
            'msg_done'=>'תודה רבה! הפרק סומן כהושלם.',
            'msg_taken'=>'הפרק הזה כבר נתפס. בחרו פרק פנוי אחר.',
            'msg_full'=>'כל הפרקים בספר הנוכחי כבר נתפסו. אפשר לרענן בהמשך.',
            'email_subject'=>'קיבלת פרק תהילים לקריאה',
            'email_body'=>"שלום {name},\n\nקיבלת פרק {chapter} בקמפיין: {campaign_title}\n\nלקריאת הפרק באתר:\n{read_url}\n\nלסימון הפרק כהושלם:\n{done_url}\n\nלקחת פרק נוסף:\n{take_more_url}\n\nתודה רבה!",
        ]);
    }
    private function opts() { $this->set_default_options(); return wp_parse_args(get_option('tcm_options', []), ['link_base'=>'tehillim','webhook_url'=>'','webhook_secret'=>'','turnstile_site_key'=>'','turnstile_secret_key'=>'','msg_joined'=>'','msg_done'=>'','msg_taken'=>'הפרק הזה כבר נתפס. בחרו פרק פנוי אחר.','msg_full'=>'כל הפרקים בספר הנוכחי כבר נתפסו. אפשר לרענן בהמשך.','email_webhook_chapter_taken_email'=>'','email_webhook_creator_campaign_created'=>'','email_webhook_chapter_reminder'=>'','email_webhook_chapter_release_warning'=>'','email_webhook_book_completed'=>'','email_webhook_owner_message'=>'','email_subject'=>'','email_body'=>'','creator_email_subject'=>'הקמפיין שלך נפתח בהצלחה','creator_email_body'=>'שלום {name},

הקמפיין נפתח בהצלחה: {campaign_title}

קישור לקמפיין:
{campaign_url}','reminders_enabled'=>'1','reminder_hours'=>'6','reminder_max'=>'2','release_warning_hours'=>'24','release_after_hours'=>'36','reminder_subject'=>'תזכורת לקריאת פרק תהילים','reminder_body'=>'שלום {name},

קיבלת פרק {chapter} בקמפיין: {campaign_title}.

לקריאת הפרק:
{read_url}

לסימון כהושלם:
{done_url}','release_subject'=>'האם לשחרר את הפרק שקיבלת?','release_body'=>'שלום {name},

הפרק שקיבלת עדיין לא סומן כהושלם. אם לא תספיק/י לקרוא אותו, אפשר לשחרר אותו כדי שמישהו אחר יוכל לקחת אותו.

לקריאה:
{read_url}

סיימתי:
{done_url}

שחרור הפרק:
{release_url}','book_completed_subject'=>'ספר תהילים הושלם בקמפיין','book_completed_body'=>'בשורות טובות! הושלם ספר תהילים בקמפיין: {campaign_title}.

אפשר לקחת פרק נוסף ולהמשיך את הזכות:
{campaign_url}',
'join_title'=>'הצטרפות לקריאה','join_button_text'=>'הצטרפות לקריאה','allow_multi_chapters'=>'1','multi_chapter_options'=>'3,5,10','allow_full_book'=>'1','finish_wave_threshold'=>'20','cta_text_default'=>'הצטרפות לקריאה','cta_text_urgent'=>'עזרו לסיים עכשיו','multi_email_subject'=>'קיבלת כמה פרקי תהילים לקריאה','multi_email_body'=>'שלום {name},

קיבלת את הפרקים: {chapters}

להתחלת הקריאה:
{read_url}','full_book_email_subject'=>'קיבלת ספר תהילים שלם לקריאה','full_book_email_body'=>'שלום {name},

קיבלת ספר תהילים שלם בקמפיין: {campaign_title}.

להתחלת הקריאה:
{read_url}']); }
    private function opt($key) { $o=$this->opts(); return $o[$key] ?? ''; }
    private function message($key) { if ($key === 'done_all') return 'תודה רבה! כל הפרקים שקיבלת סומנו כהושלמו.'; if ($key === 'done') return $this->opt('msg_done'); if ($key === 'joined') return $this->opt('msg_joined'); if ($key === 'taken') return $this->opt('msg_taken'); if ($key === 'full') return $this->opt('msg_full'); if ($key === 'choose') return 'בחרו פרק מהרשימה.'; if ($key === 'no_full_book') return 'אין כרגע ספר שלם פנוי. אפשר לבחור פרק או כמה פרקים.'; if ($key === 'released') return 'הפרק שוחרר בהצלחה וחזר לרשימת הפרקים הפנויים.'; return 'הפעולה בוצעה.'; }

    public function settings_page() {
        $o = $this->opts();
        echo '<div class="wrap" dir="rtl"><h1>הגדרות וובהוקים והודעות</h1><form method="post" action="options.php">'; settings_fields('tcm_settings');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th colspan="2"><h2>מבנה קישורים</h2></th></tr>';
        echo '<tr><th>בסיס קישורים לקמפיינים</th><td><input type="text" name="tcm_options[link_base]" value="' . esc_attr($o['link_base'] ?? 'tehillim') . '" class="regular-text" pattern="[A-Za-z0-9\-]+"><p class="description">אותיות אנגליות, מספרים ומקפים בלבד. לדוגמה: tehillim. לאחר שינוי: הגדרות → קישורים קבועים → שמירת שינויים.</p><p><strong>מבנה עמוד קמפיין:</strong> <code>/' . esc_html($this->campaign_base_slug()) . '/campaign-123/</code></p></td></tr>';
        echo '<tr><th colspan="2"><h2>התנהגות וחוויית משתמש</h2></th></tr>';
        echo '<tr><th>כותרת טופס הצטרפות</th><td><input type="text" name="tcm_options[join_title]" value="' . esc_attr($o['join_title'] ?? 'הצטרפות לקריאה') . '" class="large-text"></td></tr>';
        echo '<tr><th>טקסט כפתור הצטרפות</th><td><input type="text" name="tcm_options[join_button_text]" value="' . esc_attr($o['join_button_text'] ?? 'הצטרפות לקריאה') . '" class="large-text"></td></tr>';
        echo '<tr><th>אפשר לקחת כמה פרקים</th><td><label><input type="checkbox" name="tcm_options[allow_multi_chapters]" value="1" ' . checked($o['allow_multi_chapters'] ?? '1', '1', false) . '> פעיל</label><p class="description">מופיע בסוף רשימת הפרקים.</p></td></tr>';
        echo '<tr><th>אפשרויות כמה פרקים</th><td><input type="text" name="tcm_options[multi_chapter_options]" value="' . esc_attr($o['multi_chapter_options'] ?? '3,5,10') . '" class="regular-text"><p class="description">להפריד בפסיקים, למשל: 3,5,10</p></td></tr>';
        echo '<tr><th>אפשר לקחת ספר שלם</th><td><label><input type="checkbox" name="tcm_options[allow_full_book]" value="1" ' . checked($o['allow_full_book'] ?? '1', '1', false) . '> פעיל</label><p class="description">יופיע רק כשיש 150 פרקים פנויים בספר הנוכחי.</p></td></tr>';
        echo '<tr><th>סף גל סיום</th><td><input type="number" min="1" max="150" name="tcm_options[finish_wave_threshold]" value="' . esc_attr($o['finish_wave_threshold'] ?? '20') . '" style="width:90px"> פרקים</td></tr>';
        echo '<tr><th>טקסט CTA רגיל</th><td><input type="text" name="tcm_options[cta_text_default]" value="' . esc_attr($o['cta_text_default'] ?? 'הצטרפות לקריאה') . '" class="large-text"></td></tr>';
        echo '<tr><th>טקסט CTA לקראת סיום</th><td><input type="text" name="tcm_options[cta_text_urgent]" value="' . esc_attr($o['cta_text_urgent'] ?? 'עזרו לסיים עכשיו') . '" class="large-text"></td></tr>';
        echo '<tr><th>Webhook URL</th><td><input type="url" name="tcm_options[webhook_url]" value="' . esc_attr($o['webhook_url']) . '" class="regular-text" placeholder="https://..."><p class="description">נשלח באירועים: campaign_created, chapter_taken, chapter_done, campaign_completed.</p></td></tr>';
        echo '<tr><th>Webhook Secret</th><td><input type="text" name="tcm_options[webhook_secret]" value="' . esc_attr($o['webhook_secret']) . '" class="regular-text"><p class="description">יישלח בכותרת X-TCM-Secret.</p></td></tr>';
        echo '<tr><th colspan="2"><h2>וובהוקים נפרדים לשליחת מיילים</h2><p>אפשר להשאיר ריק. כל שדה מקבל Webhook רק כשסוג המייל הזה נשלח בפועל.</p></th></tr>';
        echo '<tr><th>מייל קבלת פרק</th><td><input type="url" name="tcm_options[email_webhook_chapter_taken_email]" value="' . esc_attr($o['email_webhook_chapter_taken_email'] ?? '') . '" class="regular-text" placeholder="https://..."></td></tr>';
        echo '<tr><th>מייל למקים קמפיין</th><td><input type="url" name="tcm_options[email_webhook_creator_campaign_created]" value="' . esc_attr($o['email_webhook_creator_campaign_created'] ?? '') . '" class="regular-text" placeholder="https://..."></td></tr>';
        echo '<tr><th>מייל תזכורת</th><td><input type="url" name="tcm_options[email_webhook_chapter_reminder]" value="' . esc_attr($o['email_webhook_chapter_reminder'] ?? '') . '" class="regular-text" placeholder="https://..."></td></tr>';
        echo '<tr><th>מייל אזהרת שחרור</th><td><input type="url" name="tcm_options[email_webhook_chapter_release_warning]" value="' . esc_attr($o['email_webhook_chapter_release_warning'] ?? '') . '" class="regular-text" placeholder="https://..."></td></tr>';
        echo '<tr><th>מייל כשספר הושלם</th><td><input type="url" name="tcm_options[email_webhook_book_completed]" value="' . esc_attr($o['email_webhook_book_completed'] ?? '') . '" class="regular-text" placeholder="https://..."></td></tr>';
        echo '<tr><th>מייל הודעת בעל קמפיין</th><td><input type="url" name="tcm_options[email_webhook_owner_message]" value="' . esc_attr($o['email_webhook_owner_message'] ?? '') . '" class="regular-text" placeholder="https://..."></td></tr>';
        echo '<tr><th>מייל קבלת כמה פרקים</th><td><input type="url" name="tcm_options[email_webhook_multi_chapters_taken_email]" value="' . esc_attr($o['email_webhook_multi_chapters_taken_email'] ?? '') . '" class="regular-text" placeholder="https://..."></td></tr>';
        echo '<tr><th>מייל קבלת ספר שלם</th><td><input type="url" name="tcm_options[email_webhook_full_book_taken_email]" value="' . esc_attr($o['email_webhook_full_book_taken_email'] ?? '') . '" class="regular-text" placeholder="https://..."></td></tr>';
        echo '<tr><th>Cloudflare Turnstile Site Key</th><td><input type="text" name="tcm_options[turnstile_site_key]" value="' . esc_attr($o['turnstile_site_key'] ?? '') . '" class="regular-text"><p class="description">אם תמלאו Site Key ו-Secret Key, טופס בחירת פרק יוגן אוטומטית.</p></td></tr>';
        echo '<tr><th>Cloudflare Turnstile Secret Key</th><td><input type="text" name="tcm_options[turnstile_secret_key]" value="' . esc_attr($o['turnstile_secret_key'] ?? '') . '" class="regular-text"></td></tr>';
        echo '<tr><th>הודעת אישור קבלת פרק</th><td><input type="text" name="tcm_options[msg_joined]" value="' . esc_attr($o['msg_joined']) . '" class="large-text"></td></tr>';
        echo '<tr><th>הודעת אישור סיום פרק</th><td><input type="text" name="tcm_options[msg_done]" value="' . esc_attr($o['msg_done']) . '" class="large-text"></td></tr>';
        echo '<tr><th>הודעה כשפרק נתפס</th><td><input type="text" name="tcm_options[msg_taken]" value="' . esc_attr($o['msg_taken']) . '" class="large-text"></td></tr>';
        echo '<tr><th>הודעה כשאין פרקים פנויים</th><td><input type="text" name="tcm_options[msg_full]" value="' . esc_attr($o['msg_full']) . '" class="large-text"></td></tr>';
        echo '<tr><th>נושא אימייל</th><td><input type="text" name="tcm_options[email_subject]" value="' . esc_attr($o['email_subject']) . '" class="large-text"></td></tr>';
        echo '<tr><th>תוכן אימייל</th><td><textarea name="tcm_options[email_body]" rows="10" class="large-text code">' . esc_textarea($o['email_body']) . '</textarea><p class="description">משתנים: {name}, {campaign_title}, {chapter}, {chapter_number}, {read_url}, {done_url}, {take_more_url}</p></td></tr>';
        echo '<tr><th>נושא אימייל כמה פרקים</th><td><input type="text" name="tcm_options[multi_email_subject]" value="' . esc_attr($o['multi_email_subject'] ?? '') . '" class="large-text"></td></tr>';
        echo '<tr><th>תוכן אימייל כמה פרקים</th><td><textarea name="tcm_options[multi_email_body]" rows="6" class="large-text code">' . esc_textarea($o['multi_email_body'] ?? '') . '</textarea><p class="description">משתנים: {name}, {campaign_title}, {chapters}, {count}, {read_url}, {take_more_url}</p></td></tr>';
        echo '<tr><th>נושא אימייל ספר שלם</th><td><input type="text" name="tcm_options[full_book_email_subject]" value="' . esc_attr($o['full_book_email_subject'] ?? '') . '" class="large-text"></td></tr>';
        echo '<tr><th>תוכן אימייל ספר שלם</th><td><textarea name="tcm_options[full_book_email_body]" rows="6" class="large-text code">' . esc_textarea($o['full_book_email_body'] ?? '') . '</textarea><p class="description">משתנים: {name}, {campaign_title}, {read_url}, {take_more_url}</p></td></tr>';
        echo '<tr><th colspan="2"><h2>תזכורות ושחרור פרקים</h2></th></tr>';
        echo '<tr><th>להפעיל תזכורות</th><td><label><input type="checkbox" name="tcm_options[reminders_enabled]" value="1" ' . checked($o['reminders_enabled'], '1', false) . '> פעיל</label></td></tr>';
        echo '<tr><th>תזכורת אחרי שעות</th><td><input type="number" min="1" name="tcm_options[reminder_hours]" value="' . esc_attr($o['reminder_hours']) . '" style="width:90px"></td></tr>';
        echo '<tr><th>מספר תזכורות מקסימלי</th><td><input type="number" min="0" name="tcm_options[reminder_max]" value="' . esc_attr($o['reminder_max']) . '" style="width:90px"></td></tr>';
        echo '<tr><th>אזהרה לפני שחרור אחרי שעות</th><td><input type="number" min="1" name="tcm_options[release_warning_hours]" value="' . esc_attr($o['release_warning_hours']) . '" style="width:90px"></td></tr>';
        echo '<tr><th>שחרור אוטומטי אחרי שעות</th><td><input type="number" min="1" name="tcm_options[release_after_hours]" value="' . esc_attr($o['release_after_hours']) . '" style="width:90px"><p class="description">השחרור האוטומטי קורה רק אחרי שנשלחה אזהרה ולא הייתה תגובה.</p></td></tr>';
        echo '<tr><th>נושא תזכורת</th><td><input type="text" name="tcm_options[reminder_subject]" value="' . esc_attr($o['reminder_subject']) . '" class="large-text"></td></tr>';
        echo '<tr><th>תוכן תזכורת</th><td><textarea name="tcm_options[reminder_body]" rows="5" class="large-text code">' . esc_textarea($o['reminder_body']) . '</textarea></td></tr>';
        echo '<tr><th>נושא אזהרת שחרור</th><td><input type="text" name="tcm_options[release_subject]" value="' . esc_attr($o['release_subject']) . '" class="large-text"></td></tr>';
        echo '<tr><th>תוכן אזהרת שחרור</th><td><textarea name="tcm_options[release_body]" rows="6" class="large-text code">' . esc_textarea($o['release_body']) . '</textarea><p class="description">משתנים: {name}, {campaign_title}, {chapter}, {read_url}, {done_url}, {release_url}</p></td></tr>';
        echo '<tr><th colspan="2"><h2>הודעות אוטומטיות נוספות</h2></th></tr>';
        echo '<tr><th>נושא הודעה למקים הקמפיין</th><td><input type="text" name="tcm_options[creator_email_subject]" value="' . esc_attr($o['creator_email_subject']) . '" class="large-text"></td></tr>';
        echo '<tr><th>תוכן הודעה למקים הקמפיין</th><td><textarea name="tcm_options[creator_email_body]" rows="5" class="large-text code">' . esc_textarea($o['creator_email_body']) . '</textarea><p class="description">משתנים: {name}, {campaign_title}, {campaign_url}</p></td></tr>';
        echo '<tr><th>נושא הודעה כשספר הושלם</th><td><input type="text" name="tcm_options[book_completed_subject]" value="' . esc_attr($o['book_completed_subject']) . '" class="large-text"></td></tr>';
        echo '<tr><th>תוכן הודעה כשספר הושלם</th><td><textarea name="tcm_options[book_completed_body]" rows="5" class="large-text code">' . esc_textarea($o['book_completed_body']) . '</textarea><p class="description">משתנים: {campaign_title}, {campaign_url}, {round_number}</p></td></tr>';
        echo '<tr><th colspan="2"><h2>עיצוב ותצוגה</h2><p>שולט על הצבעים, הפונט, הרוחב והעיצוב של כל התוסף.</p></th></tr>';
        echo '<tr><th>צבע ראשי לכפתורים</th><td><input type="color" name="tcm_options[design_primary_color]" value="' . esc_attr($o['design_primary_color'] ?? '#111111') . '"></td></tr>';
        echo '<tr><th>צבע משני / פס התקדמות</th><td><input type="color" name="tcm_options[design_secondary_color]" value="' . esc_attr($o['design_secondary_color'] ?? '#1f9d55') . '"></td></tr>';
        echo '<tr><th>רקע כרטיסים</th><td><input type="color" name="tcm_options[design_card_bg]" value="' . esc_attr($o['design_card_bg'] ?? '#ffffff') . '"></td></tr>';
        echo '<tr><th>רקע אזורי מידע / דאשבורדים</th><td><input type="color" name="tcm_options[design_dashboard_bg]" value="' . esc_attr($o['design_dashboard_bg'] ?? '#f7f7f7') . '"></td></tr>';
        echo '<tr><th>רקע כללי לאזורי התוסף</th><td><input type="text" name="tcm_options[design_page_bg]" value="' . esc_attr($o['design_page_bg'] ?? 'transparent') . '" class="regular-text"><p class="description">אפשר לכתוב transparent או צבע HEX כמו #ffffff</p></td></tr>';
        echo '<tr><th>צבע טקסט</th><td><input type="color" name="tcm_options[design_text_color]" value="' . esc_attr($o['design_text_color'] ?? '#111111') . '"></td></tr>';
        echo '<tr><th>צבע טקסט משני</th><td><input type="color" name="tcm_options[design_muted_color]" value="' . esc_attr($o['design_muted_color'] ?? '#666666') . '"></td></tr>';
        echo '<tr><th>צבע טקסט בכפתורים</th><td><input type="color" name="tcm_options[design_button_text_color]" value="' . esc_attr($o['design_button_text_color'] ?? '#ffffff') . '"></td></tr>';
        echo '<tr><th>רקע שדות וטפסים</th><td><input type="color" name="tcm_options[design_field_bg]" value="' . esc_attr($o['design_field_bg'] ?? '#ffffff') . '"></td></tr>';
        echo '<tr><th>מסגרת שדות וטפסים</th><td><input type="color" name="tcm_options[design_field_border]" value="' . esc_attr($o['design_field_border'] ?? '#dddddd') . '"></td></tr>';
        echo '<tr><th>רוחב מקסימלי</th><td><input type="number" min="320" max="1600" name="tcm_options[design_max_width]" value="' . esc_attr($o['design_max_width'] ?? '980') . '" style="width:100px"> px</td></tr>';
        echo '<tr><th>עיגול פינות</th><td><input type="number" min="0" max="60" name="tcm_options[design_radius]" value="' . esc_attr($o['design_radius'] ?? '18') . '" style="width:100px"> px</td></tr>';
        echo '<tr><th>עיגול פינות כפתורים</th><td><input type="number" min="0" max="999" name="tcm_options[design_button_radius]" value="' . esc_attr($o['design_button_radius'] ?? '999') . '" style="width:100px"> px</td></tr>';
        echo '<tr><th>עיגול פינות שדות</th><td><input type="number" min="0" max="60" name="tcm_options[design_field_radius]" value="' . esc_attr($o['design_field_radius'] ?? '12') . '" style="width:100px"> px</td></tr>';
        echo '<tr><th>ריווח פנימי בכפתור</th><td>גובה: <input type="number" min="4" max="40" name="tcm_options[design_button_padding_y]" value="' . esc_attr($o['design_button_padding_y'] ?? '12') . '" style="width:80px"> px &nbsp; רוחב: <input type="number" min="8" max="80" name="tcm_options[design_button_padding_x]" value="' . esc_attr($o['design_button_padding_x'] ?? '22') . '" style="width:80px"> px</td></tr>';
        echo '<tr><th>גודל כותרת</th><td><input type="number" min="16" max="64" name="tcm_options[design_title_size]" value="' . esc_attr($o['design_title_size'] ?? '28') . '" style="width:100px"> px</td></tr>';
        echo '<tr><th>פונט</th><td><input type="text" name="tcm_options[design_font_family]" value="' . esc_attr($o['design_font_family'] ?? 'inherit') . '" class="regular-text" placeholder="inherit / Arial / Assistant"><p class="description">אפשר להשאיר inherit כדי לרשת את הפונט של האתר.</p></td></tr>';
        echo '<tr><th>CSS מותאם</th><td><textarea name="tcm_options[design_custom_css]" rows="8" class="large-text code">' . esc_textarea($o['design_custom_css'] ?? '') . '</textarea><p class="description">לשינויים מתקדמים בלבד. לדוגמה: .tcm-title{color:#000}</p></td></tr></table>'; submit_button(); echo '</form></div>';
    }

    private function campaign_base_slug() {
        $o = get_option('tcm_options', []);
        $base = isset($o['link_base']) ? sanitize_title($o['link_base']) : 'tehillim';
        $base = preg_replace('/[^a-z0-9\-]/', '', strtolower($base));
        return $base ?: 'tehillim';
    }

    public function campaign_permalink($permalink, $post) {
        if (!is_object($post) || $post->post_type !== self::CPT) return $permalink;
        return home_url('/' . $this->campaign_base_slug() . '/campaign-' . absint($post->ID) . '/');
    }

    private function resolve_campaign_id($atts = []) {
        $id = 0;
        if (is_array($atts) && !empty($atts['id'])) $id = absint($atts['id']);
        if (!$id && get_post_type(get_the_ID()) === self::CPT) $id = get_the_ID();
        if (!$id && is_singular(self::CPT)) $id = get_the_ID();
        return $id;
    }

    private function participant_count($campaign_id) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT COALESCE(NULLIF(participant_email,''), NULLIF(token,''), id)) FROM {$this->table_name()} WHERE campaign_id=%d AND status IN ('taken','done')", $campaign_id));
    }

    public function shortcode_progress_percent($atts) {
        $id = $this->resolve_campaign_id(shortcode_atts(['id'=>0], $atts));
        if (!$id) return '';
        $s = $this->stats($id);
        return esc_html($s['percent'] . '%');
    }

    public function shortcode_participants($atts) {
        global $wpdb;
        $id = $this->resolve_campaign_id(shortcode_atts(['id'=>0], $atts));
        if (!$id) return '';
        $count = $this->participant_count($id);
        return esc_html($count);
    }

    public function shortcode_remaining_chapters($atts) {
        global $wpdb;
        $id = $this->resolve_campaign_id(shortcode_atts(['id'=>0], $atts));
        if (!$id) return '';
        $round = $this->current_round($id);
        $remaining = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status='free'", $id, $round));
        return esc_html($remaining);
    }

    public function shortcode_completed_books($atts) {
        $id = $this->resolve_campaign_id(shortcode_atts(['id'=>0], $atts));
        if (!$id) return '';
        $s = $this->stats($id);
        return esc_html($s['completed_books']);
    }

    public function shortcode_stats($atts) {
        global $wpdb;
        $id = $this->resolve_campaign_id(shortcode_atts(['id'=>0], $atts));
        if (!$id) return '';
        $s = $this->stats($id);
        $participants = $this->participant_count($id);
        $remaining = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status='free'", $id, $s['round']));
        ob_start();
        echo '<div class="tcm-wrap tcm-stats-box"><div class="tcm-card"><div class="tcm-mini-stats">';
        echo '<div class="tcm-mini-stat"><strong>' . esc_html($s['percent']) . '%</strong>התקדמות</div>';
        echo '<div class="tcm-mini-stat"><strong>' . esc_html($participants) . '</strong>משתתפים</div>';
        echo '<div class="tcm-mini-stat"><strong>' . esc_html($remaining) . '</strong>פרקים פנויים בספר הנוכחי</div>';
        echo '<div class="tcm-mini-stat"><strong>' . esc_html($s['completed_books']) . '</strong>ספרים שהושלמו</div>';
        echo '</div></div></div>';
        return ob_get_clean();
    }


    public function shortcode_cta($atts) {
        $id = $this->resolve_campaign_id(shortcode_atts(['id'=>0], $atts));
        if (!$id) return '';
        $s = $this->stats($id);
        $remaining = (int)$this->shortcode_remaining_chapters(['id'=>$id]);
        $text = $this->opt('cta_text_default') ?: 'הצטרפות לקריאה';
        if ($remaining > 0 && $remaining <= absint($this->opt('finish_wave_threshold') ?: 20)) $text = $this->opt('cta_text_urgent') ?: 'עזרו לסיים עכשיו';
        return '<a class="tcm-btn tcm-cta" href="' . esc_url(get_permalink($id)) . '#tcm">' . esc_html($text) . '</a>';
    }

    public function shortcode_urgency($atts) {
        $id = $this->resolve_campaign_id(shortcode_atts(['id'=>0], $atts));
        if (!$id) return '';
        $remaining = (int)$this->shortcode_remaining_chapters(['id'=>$id]);
        $threshold = absint($this->opt('finish_wave_threshold') ?: 20);
        if ($remaining <= 0) return '<span class="tcm-badge">הספר הנוכחי נתפס במלואו</span>';
        if ($remaining <= $threshold) return '<span class="tcm-badge tcm-urgent">נשארו רק ' . esc_html($remaining) . ' פרקים לסיום!</span>';
        return '<span class="tcm-badge">נותרו ' . esc_html($remaining) . ' פרקים פנויים</span>';
    }

    public function shortcode_data($atts) {
        $atts = shortcode_atts(['id'=>0,'field'=>'progress_percent','prefix'=>'','suffix'=>'','if_less'=>'','text'=>''], $atts);
        $id = $this->resolve_campaign_id($atts);
        if (!$id) return '';
        $field = sanitize_key($atts['field']); $s = $this->stats($id); global $wpdb; $round = $this->current_round($id); $value = '';
        if ($field === 'participants') $value = $this->participant_count($id);
        elseif ($field === 'progress' || $field === 'percent' || $field === 'progress_percent') $value = $s['percent'];
        elseif ($field === 'remaining' || $field === 'available') $value = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status='free'", $id, $round));
        elseif ($field === 'completed' || $field === 'chapters_completed') $value = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status='done'", $id, $round));
        elseif ($field === 'taken' || $field === 'chapters_taken') $value = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name()} WHERE campaign_id=%d AND round_number=%d AND status='taken'", $id, $round));
        elseif ($field === 'total') $value = 150;
        elseif ($field === 'books' || $field === 'completed_books' || $field === 'books_done') $value = $s['completed_books'];
        elseif ($field === 'books_target') $value = $s['target'];
        elseif ($field === 'bonus_books') $value = $s['bonus'];
        elseif ($field === 'round') $value = $s['round'];
        elseif ($field === 'title') $value = get_the_title($id);
        elseif ($field === 'url') $value = get_permalink($id);
        else return '';
        if ($atts['if_less'] !== '' && is_numeric($value) && $value >= (float)$atts['if_less']) return '';
        if ($atts['text'] !== '' && is_numeric($value) && $atts['if_less'] !== '' && $value < (float)$atts['if_less']) return esc_html(str_replace('{value}', (string)$value, $atts['text']));
        return esc_html($atts['prefix'] . $value . $atts['suffix']);
    }

    public function shortcode_progress_bar($atts) {
        $atts = shortcode_atts(['id'=>0,'label'=>'1'], $atts); $id = $this->resolve_campaign_id($atts); if (!$id) return '';
        $s = $this->stats($id); $label = ($atts['label'] ?? '1') !== '0';
        return '<div class="tcm-progress-wrap">' . ($label ? '<div class="tcm-progress-label">התקדמות: ' . esc_html($s['percent']) . '%</div>' : '') . '<div class="tcm-progress"><span style="width:' . esc_attr($s['percent']) . '%"></span></div></div>';
    }
    public function guide_page() {
        $shortcodes = [
            '[tehillim_campaigns]' => 'ארכיון כל הקמפיינים.',
            '[tehillim_campaign]' => 'עמוד קמפיין מלא. בעמוד קמפיין דינמי עובד גם בלי id.',
            '[tehillim_progress]' => 'כותרת, תיאור והתקדמות של קמפיין.',
            '[tehillim_join_form]' => 'טופס בחירת פרק. כולל בחירה אוטומטית, כמה פרקים, ואפשרות ספר שלם רק כשיש ספר ריק מלא בתוך יעד הקמפיין.',
            '[tehillim_join]' => 'קיצור נוסף לטופס הצטרפות לקריאה.',
            '[tehillim_chapters]' => 'תצוגת פרקים וסטטוסים.',
            '[tehillim_create_campaign_form]' => 'טופס פתיחת קמפיין מהאתר / אזור אישי.',
            '[tehillim_my_campaigns]' => 'אזור ניהול למקים הקמפיין.',
            '[tehillim_my_activity]' => 'הפעילות שלי.',
            '[tehillim_ambassador_dashboard]' => 'דאשבורד שגרירים.',
            '[tehillim_progress_percent]' => 'אחוז התקדמות בלבד. מתאים לאלמנטור.',
            '[tehillim_participants]' => 'מספר משתתפים ייחודיים בקמפיין.',
            '[tehillim_remaining_chapters]' => 'כמה פרקים פנויים נשארו בספר הנוכחי.',
            '[tehillim_completed_books]' => 'כמה ספרים הושלמו.',
            '[tehillim_stats]' => 'בלוק סטטיסטיקות מוכן: התקדמות, משתתפים, פרקים פנויים וספרים שהושלמו.',
            '[tehillim_stats_box]' => 'שם נוסף לבלוק הסטטיסטיקות.',
            '[tehillim_remaining]' => 'כמה פרקים פנויים נשארו בספר הנוכחי.',
            '[tehillim_books_done]' => 'כמה ספרים הושלמו.',
            '[tehillim_cta]' => 'כפתור הצטרפות דינמי.',
            '[tehillim_urgency]' => 'טקסט דחיפות דינמי לפי מספר הפרקים שנותרו.',
            '[tehillim_progress_bar]' => 'פס התקדמות מוכן עם אחוזים.',
            '[tehillim_data field="participants"]' => 'נתון בודד לעיצוב חופשי באלמנטור.',
        ];
        echo '<div class="wrap" dir="rtl"><h1>מדריך שימוש ושורטקודים</h1>';
        echo '<div class="notice notice-info"><p><strong>חשוב:</strong> כל קישורי הקמפיינים נוצרים באנגלית ומספרים בלבד, לדוגמה <code>/' . esc_html($this->campaign_base_slug()) . '/campaign-123/</code>. לאחר שינוי בסיס קישורים יש לבצע: הגדרות → קישורים קבועים → שמירת שינויים.</p></div>';
        echo '<h2>הפעלה מהירה</h2><ol><li>יוצרים קמפיין חדש בתפריט קמפייני תהילים.</li><li>מגדירים יעד ספרים ותיאור.</li><li>מטמיעים בעמוד כללי את <code>[tehillim_campaigns]</code>, או בונים ארכיון/עמוד פנימי באלמנטור.</li><li>בעמוד קמפיין באלמנטור אפשר להשתמש בשורטקודים ללא id — התוסף מזהה את הקמפיין הנוכחי.</li></ol>';
        echo '<h2>רשימת שורטקודים</h2><table class="widefat striped"><thead><tr><th>שורטקוד</th><th>מה הוא מציג</th><th>העתקה</th></tr></thead><tbody>';
        foreach ($shortcodes as $code => $desc) {
            echo '<tr><td><code>' . esc_html($code) . '</code></td><td>' . esc_html($desc) . '</td><td><button type="button" class="button" data-tcm-copy="' . esc_attr($code) . '">העתק</button></td></tr>';
        }
        echo '</tbody></table>';
        echo '<h2>פרמטרים זמינים עבור <code>[tehillim_data field=&quot;...&quot;]</code></h2>';
        $data_fields = ['participants'=>'מספר משתתפים ייחודיים בקמפיין','progress_percent'=>'אחוז התקדמות כולל','remaining'=>'כמה פרקים פנויים נשארו בספר הנוכחי','available'=>'שם נוסף ל־remaining','completed'=>'כמה פרקים הושלמו בספר הנוכחי','taken'=>'כמה פרקים תפוסים ועדיין לא הושלמו','total'=>'סך הפרקים בספר תהילים — 150','books_done'=>'כמה ספרים הושלמו בקמפיין','books_target'=>'יעד הספרים הבסיסי של הקמפיין','bonus_books'=>'כמה ספרי בונוס נוספו','round'=>'מספר הסבב/הספר הנוכחי','title'=>'כותרת הקמפיין','url'=>'קישור לקמפיין'];
        echo '<table class="widefat striped"><thead><tr><th>שדה</th><th>מה הוא מציג</th><th>דוגמה</th><th>העתקה</th></tr></thead><tbody>';
        foreach ($data_fields as $field => $desc) { $code = '[tehillim_data field="' . $field . '"]'; echo '<tr><td><code>' . esc_html($field) . '</code></td><td>' . esc_html($desc) . '</td><td><code>' . esc_html($code) . '</code></td><td><button type="button" class="button" data-tcm-copy="' . esc_attr($code) . '">העתק</button></td></tr>'; }
        echo '</tbody></table><p><strong>אפשרויות מתקדמות:</strong> <code>[tehillim_data field="remaining" prefix="נשארו " suffix=" פרקים"]</code> וגם <code>[tehillim_data field="remaining" if_less="20" text="🔥 נשארו רק {value} פרקים!"]</code></p>';
        echo '<h2>שדות מטה לקמפיין</h2><p><code>post_title</code> — ההקדשה הראשית / כותרת הקמפיין<br><code>post_content</code> — תיאור הקמפיין<br><code>_tcm_target_books</code> — יעד בסיס<br><code>_tcm_bonus_books</code> — ספרי בונוס<br><code>_tcm_status</code> — active / paused / completed</p>';
        echo '<h2>בדיקת מערכת</h2><ul>';
        echo '<li>בסיס קישורים נוכחי: <code>' . esc_html($this->campaign_base_slug()) . '</code></li>';
        echo '<li>Turnstile: ' . ($this->opt('turnstile_site_key') && $this->opt('turnstile_secret_key') ? '<strong style="color:green">פעיל</strong>' : '<strong style="color:#b36b00">לא מוגדר</strong>') . '</li>';
        echo '<li>Webhook כללי: ' . ($this->opt('webhook_url') ? '<strong style="color:green">מוגדר</strong>' : '<strong style="color:#b36b00">לא מוגדר</strong>') . '</li>';
        echo '</ul></div>';
    }

    private function send_html_email($to, $subject, $body, $replacements = [], $email_type = '', $payload = []) {
        if (!$to || !is_email($to)) return false;
        $subject = strtr((string)$subject, $replacements);
        $body = strtr((string)$body, $replacements);
        if (stripos($body, '<') === false) $body = nl2br(esc_html($body));
        $html = '<!doctype html><html lang="he" dir="rtl"><body dir="rtl" style="direction:rtl;text-align:right;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#111">' . $body . '</body></html>';
        $sent = wp_mail($to, $subject, $html, ['Content-Type: text/html; charset=UTF-8']);
        if ($sent && $email_type) { $payload['to'] = $to; $payload['subject'] = $subject; $this->send_email_webhook($email_type, $payload); }
        return $sent;
    }

    private function release_url($assignment_id, $token) {
        return $this->action_url('release', absint($assignment_id), $token);
    }

    private function send_creator_campaign_created_email($campaign_id) {
        $author_id = (int)get_post_field('post_author', $campaign_id);
        $user = $author_id ? get_user_by('id', $author_id) : false;
        if (!$user || !is_email($user->user_email)) return;
        $replacements = $this->placeholders(['name'=>$user->display_name ?: $user->user_login,'campaign_title'=>get_the_title($campaign_id),'campaign_url'=>get_permalink($campaign_id),'my_campaigns_url'=>home_url('/')]);
        $this->send_html_email($user->user_email, $this->opt('creator_email_subject'), $this->opt('creator_email_body'), $replacements, 'creator_campaign_created', ['campaign_id'=>$campaign_id,'campaign_title'=>get_the_title($campaign_id),'campaign_url'=>get_permalink($campaign_id)]);
    }

    private function send_assignment_notice($row, $type) {
        if (!$row || !is_email($row->participant_email)) return;
        $read_url = $this->read_url((int)$row->campaign_id, (int)$row->id, (string)$row->token);
        $done_url = $this->done_url((int)$row->id, (string)$row->token);
        $more_url = $this->take_more_url((int)$row->id, (string)$row->token);
        $release_url = $this->release_url((int)$row->id, (string)$row->token);
        $replacements = $this->placeholders(['name'=>$row->participant_name,'campaign_title'=>get_the_title($row->campaign_id),'chapter'=>$this->chapter_label($row->chapter_number),'chapter_number'=>$row->chapter_number,'read_url'=>$read_url,'done_url'=>$done_url,'take_more_url'=>$more_url,'release_url'=>$release_url]);
        if ($type === 'reminder') $this->send_html_email($row->participant_email, $this->opt('reminder_subject'), $this->opt('reminder_body'), $replacements, 'chapter_reminder', $this->assignment_payload($row->id, $row->token));
        if ($type === 'release_warning') $this->send_html_email($row->participant_email, $this->opt('release_subject'), $this->opt('release_body'), $replacements, 'chapter_release_warning', $this->assignment_payload($row->id, $row->token));
    }

    public function process_cron_tasks() {
        if ($this->opt('reminders_enabled') !== '1') return;
        global $wpdb; $table = $this->table_name();
        $now_ts = current_time('timestamp');
        $reminder_hours = max(1, (int)$this->opt('reminder_hours'));
        $reminder_max = max(0, (int)$this->opt('reminder_max'));
        $warning_hours = max(1, (int)$this->opt('release_warning_hours'));
        $release_hours = max($warning_hours + 1, (int)$this->opt('release_after_hours'));
        $rows = $wpdb->get_results("SELECT * FROM $table WHERE status='taken' AND token IS NOT NULL AND token!='' LIMIT 200");
        foreach ($rows as $row) {
            $taken_ts = strtotime($row->taken_at ?: $row->updated_at);
            if (!$taken_ts) continue;
            $hours = ($now_ts - $taken_ts) / HOUR_IN_SECONDS;
            if ($reminder_max > 0 && (int)$row->reminder_count < $reminder_max) {
                $last = $row->last_reminder_at ? strtotime($row->last_reminder_at) : $taken_ts;
                if (($now_ts - $last) >= $reminder_hours * HOUR_IN_SECONDS) {
                    $this->send_assignment_notice($row, 'reminder');
                    $wpdb->update($table, ['reminder_count'=>(int)$row->reminder_count + 1, 'last_reminder_at'=>current_time('mysql'), 'updated_at'=>current_time('mysql')], ['id'=>$row->id], ['%d','%s','%s'], ['%d']);
                    $this->send_webhook('chapter_reminder_sent', $this->assignment_payload($row->id, $row->token));
                    continue;
                }
            }
            if ($hours >= $warning_hours && empty($row->release_notice_at)) {
                $this->send_assignment_notice($row, 'release_warning');
                $wpdb->update($table, ['release_notice_at'=>current_time('mysql'), 'updated_at'=>current_time('mysql')], ['id'=>$row->id], ['%s','%s'], ['%d']);
                $this->send_webhook('chapter_release_warning_sent', $this->assignment_payload($row->id, $row->token));
                continue;
            }
            if ($hours >= $release_hours && !empty($row->release_notice_at)) {
                $payload = $this->assignment_payload($row->id, $row->token);
                $wpdb->update($table, ['status'=>'free','participant_name'=>null,'participant_email'=>null,'participant_phone'=>null,'token'=>null,'taken_at'=>null,'completed_at'=>null,'reminder_count'=>0,'last_reminder_at'=>null,'release_notice_at'=>null,'released_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')], ['id'=>$row->id]);
                $this->send_webhook('chapter_auto_released', $payload);
            }
        }
    }

    public function handle_release_chapter() {
        $assignment_id = absint($_GET['assignment_id'] ?? 0); $token = sanitize_text_field($_GET['token'] ?? '');
        if (!$assignment_id || !$token) wp_die('קישור לא תקין');
        global $wpdb; $table = $this->table_name();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND token=%s AND status='taken'", $assignment_id, $token));
        if (!$row) wp_die('הפרק כבר שוחרר או שהקישור אינו תקין');
        $payload = $this->assignment_payload($assignment_id, $token);
        $wpdb->update($table, ['status'=>'free','participant_name'=>null,'participant_email'=>null,'participant_phone'=>null,'token'=>null,'taken_at'=>null,'completed_at'=>null,'reminder_count'=>0,'last_reminder_at'=>null,'release_notice_at'=>null,'released_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')], ['id'=>$assignment_id]);
        $this->send_webhook('chapter_released_by_participant', $payload);
        wp_safe_redirect(add_query_arg('tcm_msg','released', get_permalink((int)$row->campaign_id)) . '#tcm'); exit;
    }

    private function send_book_completed_notice($campaign_id, $round, $stats) {
        global $wpdb; $rows = $wpdb->get_results($wpdb->prepare("SELECT participant_name, participant_email FROM {$this->table_name()} WHERE campaign_id=%d AND participant_email IS NOT NULL AND participant_email!='' GROUP BY participant_email", $campaign_id));
        $replacements = $this->placeholders(['campaign_title'=>get_the_title($campaign_id),'campaign_url'=>get_permalink($campaign_id),'round_number'=>$round]);
        $sent = 0;
        foreach ($rows as $r) {
            if (!is_email($r->participant_email)) continue;
            $this->send_html_email($r->participant_email, $this->opt('book_completed_subject'), $this->opt('book_completed_body'), $replacements, 'book_completed', ['campaign_id'=>$campaign_id,'campaign_title'=>get_the_title($campaign_id),'round_number'=>$round,'campaign_url'=>get_permalink($campaign_id)]);
            $sent++;
        }
        $this->send_webhook('book_completed_notice_sent', ['campaign_id'=>$campaign_id,'campaign_title'=>get_the_title($campaign_id),'round_number'=>$round,'sent_count'=>$sent,'permalink'=>get_permalink($campaign_id)]);
    }

    public function shortcode_my_activity($atts) {
        if (!is_user_logged_in()) return '<div class="tcm-wrap"><div class="tcm-card">כדי לראות פעילות אישית צריך להתחבר לאתר.</div></div>';
        $user = wp_get_current_user();
        if (!is_email($user->user_email)) return '';
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE participant_email=%s ORDER BY updated_at DESC LIMIT 50", $user->user_email));
        ob_start(); echo '<div class="tcm-wrap tcm-my-activity"><div class="tcm-card"><h3>הפעילות שלי בתהילים</h3>';
        if (!$rows) { echo '<p>עדיין אין פעילות להצגה.</p></div></div>'; return ob_get_clean(); }
        echo '<table class="tcm-admin-table"><thead><tr><th>קמפיין</th><th>פרק</th><th>סטטוס</th><th>פעולה</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $status = $r->status === 'done' ? 'הושלם' : ($r->status === 'taken' ? 'ממתין לסיום' : 'פנוי');
            $action = $r->status === 'taken' ? '<a class="tcm-btn tcm-secondary" href="'.esc_url($this->read_url($r->campaign_id,$r->id,$r->token)).'">קריאה / סיום</a>' : '<a class="tcm-btn" href="'.esc_url(get_permalink($r->campaign_id)).'">כניסה</a>';
            echo '<tr><td>'.esc_html(get_the_title($r->campaign_id)).'</td><td>'.esc_html($this->chapter_label($r->chapter_number)).'</td><td>'.esc_html($status).'</td><td>'.$action.'</td></tr>';
        }
        echo '</tbody></table></div></div>'; return ob_get_clean();
    }

    public function capture_ambassador_ref() {
        if (empty($_GET['tcm_ref'])) return;
        $code = sanitize_key($_GET['tcm_ref']);
        if (!$code) return;
        setcookie('tcm_ref', $code, time() + 30 * DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE['tcm_ref'] = $code;
    }

    private function get_or_create_ambassador($campaign_id, $user_id = 0, $name = '', $email = '') {
        global $wpdb;
        $table = $this->ambassador_table_name();
        $campaign_id = absint($campaign_id);
        $user_id = absint($user_id);
        if ($user_id) {
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE campaign_id=%d AND user_id=%d LIMIT 1", $campaign_id, $user_id));
            if ($existing) return $existing;
        }
        if (!$name && $user_id) { $u = get_user_by('id', $user_id); if ($u) { $name = $u->display_name ?: $u->user_login; $email = $u->user_email; } }
        $now = current_time('mysql');
        do { $code = 'a' . wp_generate_password(10, false, false); } while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE code=%s", $code)));
        $wpdb->insert($table, ['campaign_id'=>$campaign_id,'user_id'=>$user_id,'ambassador_name'=>sanitize_text_field($name),'ambassador_email'=>sanitize_email($email),'code'=>$code,'goal_chapters'=>10,'created_at'=>$now,'updated_at'=>$now], ['%d','%d','%s','%s','%s','%d','%s','%s']);
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $wpdb->insert_id));
    }

    private function record_ambassador_referral($campaign_id, $assignment_id, $participant_email = '') {
        if (empty($_COOKIE['tcm_ref'])) return;
        global $wpdb;
        $code = sanitize_key($_COOKIE['tcm_ref']);
        $amb = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->ambassador_table_name()} WHERE code=%s AND campaign_id=%d", $code, $campaign_id));
        if (!$amb) return;
        $wpdb->insert($this->referral_table_name(), ['campaign_id'=>$campaign_id,'ambassador_id'=>(int)$amb->id,'assignment_id'=>$assignment_id,'participant_email'=>sanitize_email($participant_email),'created_at'=>current_time('mysql')], ['%d','%d','%d','%s','%s']);
        $this->send_webhook('ambassador_referral_recorded', ['campaign_id'=>$campaign_id,'campaign_title'=>get_the_title($campaign_id),'ambassador_id'=>(int)$amb->id,'ambassador_name'=>$amb->ambassador_name,'assignment_id'=>$assignment_id,'participant_email'=>$participant_email,'permalink'=>get_permalink($campaign_id)]);
    }

    public function shortcode_ambassador_invite($atts) {
        $atts = shortcode_atts(['id'=>get_the_ID()], $atts);
        $id = absint($atts['id']);
        if (!$id) return '';
        if (!is_user_logged_in()) return '<div class="tcm-card"><h3>רוצים לעזור להפיץ?</h3><p>משתמשים רשומים יכולים לקבל קישור שגריר אישי ולעקוב אחרי ההתקדמות שהביאו.</p></div>';
        $amb = $this->get_or_create_ambassador($id, get_current_user_id());
        if (!$amb) return '';
        $link = add_query_arg('tcm_ref', $amb->code, get_permalink($id));
        return '<div class="tcm-card"><h3>קישור שגריר אישי</h3><p class="tcm-muted">כל מי שייקח פרק דרך הקישור הזה ייספר בהתקדמות שלך.</p><input class="tcm-ambassador-link" readonly value="'.esc_attr($link).'"><div class="tcm-share-actions"><button type="button" class="tcm-btn tcm-secondary" data-tcm-copy="'.esc_attr($link).'">העתקת קישור שגריר</button><a class="tcm-btn" href="'.esc_url('https://wa.me/?text=' . rawurlencode(get_the_title($id) . "\n" . $link)).'" target="_blank" rel="noopener">שיתוף בוואטסאפ</a></div></div>';
    }

    public function shortcode_ambassador_dashboard($atts) {
        $atts = shortcode_atts(['id'=>0], $atts);
        if (!is_user_logged_in()) return '<div class="tcm-wrap"><div class="tcm-card">כדי לראות דאשבורד שגריר צריך להתחבר לאתר.</div></div>';
        global $wpdb;
        $campaign_id = absint($atts['id']);
        if (!$campaign_id && is_singular(self::CPT)) $campaign_id = get_the_ID();
        $where = $campaign_id ? $wpdb->prepare('campaign_id=%d AND user_id=%d', $campaign_id, get_current_user_id()) : $wpdb->prepare('user_id=%d', get_current_user_id());
        $ambassadors = $wpdb->get_results("SELECT * FROM {$this->ambassador_table_name()} WHERE $where ORDER BY created_at DESC");
        ob_start(); echo '<div class="tcm-wrap tcm-ambassador-dashboard"><div class="tcm-card"><h2>דאשבורד שגרירים</h2>';
        if (!$ambassadors) { echo '<p>עדיין אין קישורי שגריר.</p></div></div>'; return ob_get_clean(); }
        foreach ($ambassadors as $a) {
            $total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->referral_table_name()} WHERE ambassador_id=%d", $a->id));
            $done = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->referral_table_name()} r INNER JOIN {$this->table_name()} t ON t.id=r.assignment_id WHERE r.ambassador_id=%d AND t.status='done'", $a->id));
            $books = intdiv($done, 150);
            $link = add_query_arg('tcm_ref', $a->code, get_permalink((int)$a->campaign_id));
            echo '<div class="tcm-card"><h3>'.esc_html(get_the_title($a->campaign_id)).'</h3><div class="tcm-mini-stats"><div class="tcm-mini-stat"><strong>'.esc_html($total).'</strong>פרקים שנלקחו</div><div class="tcm-mini-stat"><strong>'.esc_html($done).'</strong>פרקים שהושלמו</div><div class="tcm-mini-stat"><strong>'.esc_html($books).'</strong>ספרים בזכותך</div></div><p><input class="tcm-ambassador-link" readonly value="'.esc_attr($link).'"></p><button type="button" class="tcm-btn tcm-secondary" data-tcm-copy="'.esc_attr($link).'">העתקת קישור</button></div>';
        }
        echo '</div></div>'; return ob_get_clean();
    }

    private function send_email_webhook($email_type, $payload) {
        $key = 'email_webhook_' . sanitize_key($email_type);
        $url = $this->opt($key);
        if (!$url) return;
        $payload['email_type'] = $email_type;
        $payload['site_url'] = home_url();
        $payload['sent_at'] = current_time('mysql');
        $headers = ['Content-Type'=>'application/json']; $secret = $this->opt('webhook_secret'); if ($secret) $headers['X-TCM-Secret'] = $secret;
        wp_remote_post($url, ['timeout'=>12, 'headers'=>$headers, 'body'=>wp_json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }
    private function placeholders($data) { $out=[]; foreach ($data as $k=>$v) $out['{'.$k.'}'] = (string)$v; return $out; }

    private function send_webhook($event, $payload) {
        $url = $this->opt('webhook_url'); if (!$url) return;
        $payload['event'] = $event; $payload['site_url'] = home_url(); $payload['sent_at'] = current_time('mysql');
        $headers = ['Content-Type'=>'application/json']; $secret = $this->opt('webhook_secret'); if ($secret) $headers['X-TCM-Secret'] = $secret;
        wp_remote_post($url, ['timeout'=>12, 'headers'=>$headers, 'body'=>wp_json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }

    private function assignment_payload($assignment_id, $token='') {
        global $wpdb; $r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name()} WHERE id=%d", $assignment_id)); if (!$r) return [];
        return ['assignment_id'=>(int)$r->id,'campaign_id'=>(int)$r->campaign_id,'campaign_title'=>get_the_title($r->campaign_id),'round_number'=>(int)$r->round_number,'chapter_number'=>(int)$r->chapter_number,'chapter_label'=>$this->chapter_label($r->chapter_number),'status'=>$r->status,'participant_name'=>$r->participant_name,'participant_email'=>$r->participant_email,'participant_phone'=>$r->participant_phone,'read_url'=>$token ? $this->read_url($r->campaign_id, $r->id, $token) : '', 'done_url'=>$token ? $this->done_url($r->id, $token) : '', 'take_more_url'=>$token ? $this->take_more_url($r->id, $token) : '', 'permalink'=>get_permalink($r->campaign_id)];
    }
}