<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="chatbot-history-wrapper">
        <!-- Search and Filter -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select id="filter-date" name="filter_date">
                    <option value=""><?php _e('All Dates', 'website-chatbot'); ?></option>
                    <option value="today"><?php _e('Today', 'website-chatbot'); ?></option>
                    <option value="yesterday"><?php _e('Yesterday', 'website-chatbot'); ?></option>
                    <option value="week"><?php _e('This Week', 'website-chatbot'); ?></option>
                    <option value="month"><?php _e('This Month', 'website-chatbot'); ?></option>
                </select>
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'website-chatbot'); ?>">
            </div>
            <div class="tablenav-pages">
                <?php
                $total_pages = ceil($total_sessions / $per_page);
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
        </div>

        <!-- Chat Sessions Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Session ID', 'website-chatbot'); ?></th>
                    <th scope="col"><?php _e('User', 'website-chatbot'); ?></th>
                    <th scope="col"><?php _e('Messages', 'website-chatbot'); ?></th>
                    <th scope="col"><?php _e('Started', 'website-chatbot'); ?></th>
                    <th scope="col"><?php _e('Last Message', 'website-chatbot'); ?></th>
                    <th scope="col"><?php _e('Actions', 'website-chatbot'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sessions): ?>
                    <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td><?php echo esc_html(substr($session->session_id, 0, 8) . '...'); ?></td>
                            <td>
                                <?php
                                if ($session->user_id) {
                                    $user = get_userdata($session->user_id);
                                    echo esc_html($user ? $user->display_name : __('Deleted User', 'website-chatbot'));
                                } else {
                                    echo esc_html__('Guest', 'website-chatbot');
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($session->message_count); ?></td>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session->created_at))); ?></td>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session->last_message))); ?></td>
                            <td>
                                <button class="button view-conversation" 
                                        data-session="<?php echo esc_attr($session->session_id); ?>">
                                    <?php _e('View', 'website-chatbot'); ?>
                                </button>
                                <button class="button delete-conversation" 
                                        data-session="<?php echo esc_attr($session->session_id); ?>">
                                    <?php _e('Delete', 'website-chatbot'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"><?php _e('No chat sessions found.', 'website-chatbot'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Conversation Modal -->
    <div id="conversation-modal" class="chatbot-modal" style="display: none;">
        <div class="chatbot-modal-content">
            <div class="chatbot-modal-header">
                <h2><?php _e('Conversation Details', 'website-chatbot'); ?></h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="chatbot-modal-body">
                <div class="conversation-messages"></div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // View conversation
    $('.view-conversation').on('click', function() {
        const sessionId = $(this).data('session');
        const modal = $('#conversation-modal');
        const messagesContainer = modal.find('.conversation-messages');
        
        messagesContainer.empty();
        
        // Show loading
        messagesContainer.html('<p class="loading">Loading conversation...</p>');
        modal.show();
        
        // Fetch conversation
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_chat_history',
                session_id: sessionId,
                nonce: chatbotAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    messagesContainer.empty();
                    response.data.forEach(function(message) {
                        const messageClass = message.sender === 'user' ? 'user-message' : 'bot-message';
                        const time = new Date(message.timestamp).toLocaleString();
                        messagesContainer.append(`
                            <div class="message ${messageClass}">
                                <div class="message-content">${message.message}</div>
                                <div class="message-time">${time}</div>
                            </div>
                        `);
                    });
                } else {
                    messagesContainer.html('<p class="error">Error loading conversation.</p>');
                }
            },
            error: function() {
                messagesContainer.html('<p class="error">Error loading conversation.</p>');
            }
        });
    });

    // Delete conversation
    $('.delete-conversation').on('click', function() {
        if (!confirm('Are you sure you want to delete this conversation?')) {
            return;
        }

        const sessionId = $(this).data('session');
        const row = $(this).closest('tr');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_chat_history',
                session_id: sessionId,
                nonce: chatbotAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error deleting conversation.');
                }
            },
            error: function() {
                alert('Error deleting conversation.');
            }
        });
    });

    // Close modal
    $('.close-modal').on('click', function() {
        $('#conversation-modal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('chatbot-modal')) {
            $('.chatbot-modal').hide();
        }
    });

    // Date filter
    $('#filter-date').on('change', function() {
        const value = $(this).val();
        if (value) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date_filter', value);
            window.location.href = currentUrl.toString();
        }
    });
});
</script>