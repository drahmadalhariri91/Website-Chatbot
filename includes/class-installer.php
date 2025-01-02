<?php
namespace WebsiteChatbot;

/**
 * Handles plugin installation and database setup
 *
 * @package WebsiteChatbot
 */
class Installer {
    /**
     * Plugin activation handler
     *
     * @return void
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        
        // Clear any existing caches
        delete_transient('chatbot_training_data');
        
        // Flush rewrite rules
        flush_rewrite_rules();

        // Set version
        update_option('chatbot_version', WEBSITE_CHATBOT_VERSION);
        
        // Schedule cleanup events
        if (!wp_next_scheduled('chatbot_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'chatbot_daily_cleanup');
        }
    }

    /**
     * Plugin deactivation handler
     *
     * @return void
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('chatbot_daily_cleanup');
        wp_clear_scheduled_hook('chatbot_daily_stats_tasks');
        
        // Clear transients
        delete_transient('chatbot_training_data');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Chat sessions table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chatbot_sessions (
            session_id VARCHAR(50) NOT NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            user_ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Chat messages table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chatbot_messages (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            sender ENUM('user', 'bot') NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        // Statistics table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chatbot_stats (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            date DATE NOT NULL,
            total_conversations INT UNSIGNED NOT NULL DEFAULT 0,
            total_messages INT UNSIGNED NOT NULL DEFAULT 0,
            total_tokens INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY date (date)
        ) $charset_collate;";

        // Feedback table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chatbot_feedback (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(50) NOT NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            feedback TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Include WordPress upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create/update tables
        foreach ($sql as $query) {
            dbDelta($query);
        }

        // Store database version
        update_option('chatbot_db_version', WEBSITE_CHATBOT_VERSION);
    }

    /**
     * Set default options
     *
     * @return void
     */
    private static function set_default_options() {
        $default_options = array(
            'chatbot_openai_key' => '',
            'chatbot_model' => 'gpt-3.5-turbo',
            'chatbot_max_tokens' => 500,
            'chatbot_temperature' => 0.7,
            'chatbot_position' => 'bottom-right',
            'chatbot_theme' => 'light',
            'chatbot_welcome_message' => __('Hello! How can I help you today?', 'website-chatbot'),
            'chatbot_placeholder_text' => __('Type your message here...', 'website-chatbot'),
            'chatbot_loading_text' => __('Thinking...', 'website-chatbot'),
            'chatbot_error_message' => __('Sorry, something went wrong. Please try again.', 'website-chatbot'),
            'chatbot_offline_message' => __('You are currently offline.', 'website-chatbot'),
            'chatbot_rate_limit' => 10, // Messages per minute
            'chatbot_session_timeout' => 30, // Minutes
            'chatbot_data_retention' => 30, // Days
            'chatbot_enable_history' => true,
            'chatbot_enable_feedback' => true,
            'chatbot_enable_analytics' => true,
            'chatbot_collect_ip' => false, // GDPR compliance
            'chatbot_retain_data' => false, // Keep data after uninstall
        );

        foreach ($default_options as $option_name => $default_value) {
            add_option($option_name, $default_value);
        }
    }

    /**
     * Plugin upgrade handler
     *
     * @return void
     */
    public static function check_version() {
        if (get_option('chatbot_version') !== WEBSITE_CHATBOT_VERSION) {
            self::create_tables();
            update_option('chatbot_version', WEBSITE_CHATBOT_VERSION);
        }
    }

    /**
     * Clean up old data
     *
     * @return void
     */
    public static function cleanup_old_data() {
        global $wpdb;
        
        $retention_days = get_option('chatbot_data_retention', 30);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        // Use prepared statement for security
        $wpdb->query($wpdb->prepare("
            DELETE s, m 
            FROM {$wpdb->prefix}chatbot_sessions s
            LEFT JOIN {$wpdb->prefix}chatbot_messages m ON s.session_id = m.session_id
            WHERE s.created_at < %s
        ", $cutoff_date));

        // Clean up orphaned messages
        $wpdb->query("
            DELETE m 
            FROM {$wpdb->prefix}chatbot_messages m
            LEFT JOIN {$wpdb->prefix}chatbot_sessions s ON m.session_id = s.session_id
            WHERE s.session_id IS NULL
        ");

        // Clean up old feedback
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}chatbot_feedback
            WHERE created_at < %s
        ", $cutoff_date));
    }
}