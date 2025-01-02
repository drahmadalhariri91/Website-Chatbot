<?php
// templates/admin/feedback.php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="chatbot-feedback-wrapper">
        <!-- Feedback Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'website-chatbot'); ?></th>
                    <th><?php _e('User', 'website-chatbot'); ?></th>
                    <th><?php _e('Feedback', 'website-chatbot'); ?></th>
                    <th><?php _e('Session', 'website-chatbot'); ?></th>
                    <th><?php _e('Actions', 'website-chatbot'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($feedback_items): ?>
                    <?php foreach ($feedback_items as $item): ?>
                        <tr>
                            <td>
                                <?php echo esc_html(wp_date(
                                    get_option('date_format') . ' ' . get_option('time_format'),
                                    strtotime($item->created_at)
                                )); ?>
                            </td>
                            <td>
                                <?php
                                if ($item->user_id) {
                                    $user = get_userdata($item->user_id);
                                    echo esc_html($user ? $user->display_name : __('Deleted User', 'website-chatbot'));
                                } else {
                                    echo esc_html__('Guest', 'website-chatbot');
                                }
                                ?>
                                <br>
                                <small><?php echo esc_html($item->user_ip); ?></small>
                            </td>
                            <td>
                                <?php echo wp_kses_post(nl2br($item->feedback)); ?>
                            </td>
                            <td>
                                <?php echo esc_html(substr($item->session_id, 0, 8) . '...'); ?>
                                <br>
                                <small>
                                    <?php echo esc_html(wp_date(
                                        get_option('date_format'),
                                        strtotime($item->session_created)
                                    )); ?>
                                </small>
                            </td>
                            <td>
                                <button class="button view-conversation" 
                                        data-session="<?php echo esc_attr($item->session_id); ?>">
                                    <?php _e('View Conversation', 'website-chatbot'); ?>
                                </button>
                                <button class="button delete-feedback" 
                                        data-feedback-id="<?php echo esc_attr($item->id); ?>">
                                    <?php _e('Delete', 'website-chatbot'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"><?php _e('No feedback found.', 'website-chatbot'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
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
        <?php endif; ?>
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
        
        messagesContainer.html('<p class="loading">Loading conversation...</p>');
        modal.show();
        
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

    // Delete feedback
    $('.delete-feedback').on('click', function() {
        if (!confirm('Are you sure you want to delete this feedback?')) {
            return;
        }

        const button = $(this);
        const feedbackId = button.data('feedback-id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_chatbot_feedback',
                feedback_id: feedbackId,
                nonce: chatbotAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                button.closest('tr').fadeOut(400, function() {
                                        $(this).remove();
                                        // Update empty state if no more items
                                        if ($('tbody tr').length === 0) {
                                            $('tbody').append('<tr><td colspan="5"><?php _e('No feedback found.', 'website-chatbot'); ?></td></tr>');
                                        }
                                    });
                                } else {
                                    alert('Error deleting feedback.');
                                }
                            },
                            error: function() {
                                alert('Error deleting feedback.');
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
                });
 </script>