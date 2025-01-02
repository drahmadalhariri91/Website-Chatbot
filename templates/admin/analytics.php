<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="chatbot-analytics-wrapper">
        <!-- Period Selector -->
        <div class="analytics-controls">
            <select id="analytics-period">
                <option value="7days"><?php _e('Last 7 Days', 'website-chatbot'); ?></option>
                <option value="30days" selected><?php _e('Last 30 Days', 'website-chatbot'); ?></option>
                <option value="90days"><?php _e('Last 90 Days', 'website-chatbot'); ?></option>
            </select>
        </div>

        <!-- Stats Overview -->
        <div class="analytics-overview">
            <div class="stat-box">
                <h3><?php _e('Total Conversations', 'website-chatbot'); ?></h3>
                <div class="stat-value" id="total-conversations">-</div>
            </div>
            <div class="stat-box">
                <h3><?php _e('Total Messages', 'website-chatbot'); ?></h3>
                <div class="stat-value" id="total-messages">-</div>
            </div>
            <div class="stat-box">
                <h3><?php _e('Average Messages/Conversation', 'website-chatbot'); ?></h3>
                <div class="stat-value" id="avg-messages">-</div>
            </div>
            <div class="stat-box">
                <h3><?php _e('Active Users', 'website-chatbot'); ?></h3>
                <div class="stat-value" id="active-users">-</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="analytics-charts">
            <div class="chart-container">
                <h3><?php _e('Conversations Over Time', 'website-chatbot'); ?></h3>
                <canvas id="conversations-chart"></canvas>
            </div>
            <div class="chart-container">
                <h3><?php _e('Messages Per Day', 'website-chatbot'); ?></h3>
                <canvas id="messages-chart"></canvas>
            </div>
        </div>

        <!-- Popular Topics -->
        <div class="analytics-topics">
            <h3><?php _e('Popular Topics', 'website-chatbot'); ?></h3>
            <div id="topics-list"></div>
        </div>
    </div>
</div>
 