<?php
// templates/admin/statistics.php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="chatbot-statistics-wrapper">
        <!-- Overview Cards -->
        <div class="statistics-overview">
            <div class="stat-card">
                <h3><?php _e('Total Conversations', 'website-chatbot'); ?></h3>
                <div class="stat-value"><?php echo number_format($total_conversations); ?></div>
            </div>
            
            <div class="stat-card">
                <h3><?php _e('Total Messages', 'website-chatbot'); ?></h3>
                <div class="stat-value"><?php echo number_format($total_messages); ?></div>
            </div>
            
            <div class="stat-card">
                <h3><?php _e('Average Messages/Conversation', 'website-chatbot'); ?></h3>
                <div class="stat-value"><?php echo number_format($avg_messages_per_conversation, 1); ?></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="statistics-charts">
            <div class="chart-container">
                <h3><?php _e('Daily Activity', 'website-chatbot'); ?></h3>
                <canvas id="dailyActivityChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity Table -->
        <div class="statistics-table">
            <h3><?php _e('Recent Activity', 'website-chatbot'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'website-chatbot'); ?></th>
                        <th><?php _e('Conversations', 'website-chatbot'); ?></th>
                        <th><?php _e('Messages', 'website-chatbot'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daily_stats as $stat): ?>
                    <tr>
                        <td><?php echo esc_html(wp_date(get_option('date_format'), strtotime($stat->date))); ?></td>
                        <td><?php echo number_format($stat->conversations); ?></td>
                        <td><?php echo number_format($stat->messages); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for chart
    const dates = <?php echo json_encode(array_column(array_reverse($daily_stats), 'date')); ?>;
    const conversations = <?php echo json_encode(array_column(array_reverse($daily_stats), 'conversations')); ?>;
    const messages = <?php echo json_encode(array_column(array_reverse($daily_stats), 'messages')); ?>;

    // Create chart
    const ctx = document.getElementById('dailyActivityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Conversations',
                    data: conversations,
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    fill: true
                },
                {
                    label: 'Messages',
                    data: messages,
                    borderColor: '#46b450',
                    backgroundColor: 'rgba(70, 180, 80, 0.1)',
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>