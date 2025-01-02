<?php
namespace WebsiteChatbot;

/**
 * Handles statistics and analytics functionality
 *
 * @package WebsiteChatbot
 */
class Stats {
    /**
     * Initialize stats functionality
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Schedule daily stats aggregation
        if (!wp_next_scheduled('chatbot_daily_stats_tasks')) {
            wp_schedule_event(time(), 'daily', 'chatbot_daily_stats_tasks');
        }

        add_action('chatbot_daily_stats_tasks', array($this, 'aggregate_daily_stats'));
    }

    /**
     * Aggregate daily statistics
     *
     * @return void
     */
    public function aggregate_daily_stats() {
        global $wpdb;
        $date = current_time('Y-m-d');

        // Get daily stats
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT session_id) as total_conversations,
                COUNT(*) as total_messages,
                SUM(LENGTH(message)) as total_chars
            FROM {$wpdb->prefix}chatbot_messages
            WHERE DATE(timestamp) = %s
        ", $date));

        if ($stats) {
            // Estimate tokens (rough estimate based on characters)
            $total_tokens = ceil($stats->total_chars / 4);

            // Insert or update stats
            $wpdb->replace(
                $wpdb->prefix . 'chatbot_stats',
                array(
                    'date' => $date,
                    'total_conversations' => $stats->total_conversations,
                    'total_messages' => $stats->total_messages,
                    'total_tokens' => $total_tokens
                ),
                array('%s', '%d', '%d', '%d')
            );
        }
    }

    /**
     * Get statistics for a specific period
     *
     * @param string $period Period to get stats for (7days, 30days, 90days)
     * @return array
     */
    public function get_period_stats($period = '30days') {
        global $wpdb;

        switch ($period) {
            case '7days':
                $days = 7;
                break;
            case '90days':
                $days = 90;
                break;
            default:
                $days = 30;
        }

        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                date,
                total_conversations,
                total_messages,
                total_tokens
            FROM {$wpdb->prefix}chatbot_stats
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            ORDER BY date DESC
        ", $days));

        return $this->fill_missing_dates($stats, $days);
    }

    /**
     * Fill in missing dates in stats
     *
     * @param array $stats Stats array from database
     * @param int   $days  Number of days to fill
     * @return array
     */
    private function fill_missing_dates($stats, $days) {
        $filled_stats = array();
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date = date('Y-m-d');
        
        $current_date = $start_date;
        while ($current_date <= $end_date) {
            $found = false;
            foreach ($stats as $stat) {
                if ($stat->date === $current_date) {
                    $filled_stats[] = $stat;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $filled_stats[] = (object) array(
                    'date' => $current_date,
                    'total_conversations' => 0,
                    'total_messages' => 0,
                    'total_tokens' => 0
                );
            }
            
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }

        return $filled_stats;
    }

    /**
     * Get total statistics
     *
     * @return object
     */
    public function get_total_stats() {
        global $wpdb;

        return $wpdb->get_row("
            SELECT 
                SUM(total_conversations) as total_conversations,
                SUM(total_messages) as total_messages,
                SUM(total_tokens) as total_tokens,
                AVG(total_messages / NULLIF(total_conversations, 0)) as avg_messages_per_conversation
            FROM {$wpdb->prefix}chatbot_stats
        ");
    }

    /**
     * Record new conversation
     *
     * @param int $messages_count Number of messages in conversation
     * @param int $tokens_count   Number of tokens used
     * @return void
     */
    public function record_conversation($messages_count, $tokens_count) {
        global $wpdb;
        $date = current_time('Y-m-d');

        $wpdb->query($wpdb->prepare("
            INSERT INTO {$wpdb->prefix}chatbot_stats 
                (date, total_conversations, total_messages, total_tokens)
            VALUES 
                (%s, 1, %d, %d)
            ON DUPLICATE KEY UPDATE
                total_conversations = total_conversations + 1,
                total_messages = total_messages + %d,
                total_tokens = total_tokens + %d
        ", $date, $messages_count, $tokens_count, $messages_count, $tokens_count));
    }
}