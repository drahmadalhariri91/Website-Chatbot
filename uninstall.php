<?php
/**
 * Uninstall functionality for Website Chatbot.
 *
 * @package WebsiteChatbot
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get retention settings
$retain_data = get_option('chatbot_retain_data', false);

if (!$retain_data) {
    global $wpdb;

    // Delete options
    $options = array(
        'chatbot_openai_key',
        'chatbot_model',
        'chatbot_max_tokens',
        'chatbot_temperature',
        'chatbot_position',
        'chatbot_theme',
        'chatbot_welcome_message',
        'chatbot_placeholder_text',
        'chatbot_loading_text',
        'chatbot_error_message',
        'chatbot_offline_message',
        'chatbot_rate_limit',
        'chatbot_session_timeout',
        'chatbot_delete_data_after',
        'chatbot_enable_history',
        'chatbot_enable_feedback',
        'chatbot_enable_analytics',
        'chatbot_excluded_pages',
        'chatbot_excluded_post_types',
        'chatbot_version',
        'chatbot_db_version',
        'chatbot_retain_data'
    );

    // Delete all options
    foreach ($options as $option) {
        delete_option($option);
    }

    // Define tables to remove
    $tables = array(
        $wpdb->prefix . 'chatbot_sessions',
        $wpdb->prefix . 'chatbot_messages',
        $wpdb->prefix . 'chatbot_stats',
        $wpdb->prefix . 'chatbot_feedback'
    );

    // Remove tables
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Clear any scheduled hooks
    wp_clear_scheduled_hook('chatbot_daily_cleanup');
    wp_clear_scheduled_hook('chatbot_daily_stats_tasks');

    // Clear any transients
    delete_transient('chatbot_training_data');
}