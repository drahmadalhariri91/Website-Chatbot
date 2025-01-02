<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

$position = get_option('chatbot_position', 'bottom-right');
$theme = get_option('chatbot_theme', 'light');
?>

<!-- Chat Toggle Button -->
<button id="chat-toggle-btn" 
        class="chat-button <?php echo esc_attr($position); ?>" 
        aria-label="<?php esc_attr_e('Toggle Chat', 'website-chatbot'); ?>">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </svg>
</button>

<!-- Chat Interface -->
<div id="wp-chatbot" class="<?php echo esc_attr($position . ' ' . $theme); ?>" style="display: none;">
    <div class="chat-container">
        <!-- Chat Header -->
        <div class="chat-header">
            <h3><?php echo esc_html(get_option('chatbot_title', __('Website Assistant', 'website-chatbot'))); ?></h3>
            <div class="chat-controls">
                <!-- Fullscreen Toggle Button -->
                <button class="toggle-fullscreen" 
                        title="<?php esc_attr_e('Toggle Fullscreen', 'website-chatbot'); ?>">
                    <svg class="expand-icon" width="16" height="16" viewBox="0 0 16 16">
                        <path d="M1.5 1h4v1.5h-2.5v2.5h-1.5v-4zm13 0v4h-1.5v-2.5h-2.5v-1.5h4zm-13 13v-4h1.5v2.5h2.5v1.5h-4zm13 0h-4v-1.5h2.5v-2.5h1.5v4z"/>
                    </svg>
                    <svg class="minimize-icon" width="16" height="16" viewBox="0 0 16 16" style="display: none;">
                        <path d="M5.5 1h-4v1.5h2.5v2.5h1.5v-4zm5 0v4h1.5v-2.5h2.5v-1.5h-4zm-5 13v-4h-1.5v2.5h-2.5v1.5h4zm5 0h4v-1.5h-2.5v-2.5h-1.5v4z"/>
                    </svg>
                </button>

                <!-- Menu Dropdown -->
                <div class="chat-menu">
                    <button class="menu-toggle" 
                            title="<?php esc_attr_e('Menu', 'website-chatbot'); ?>">
                        <svg width="16" height="16" viewBox="0 0 16 16">
                            <circle cx="8" cy="2" r="2"/>
                            <circle cx="8" cy="8" r="2"/>
                            <circle cx="8" cy="14" r="2"/>
                        </svg>
                    </button>
                    <div class="menu-dropdown" style="display: none;">
                        <ul>
                            <li>
                                <a href="#" class="menu-clear">
                                    <?php _e('Clear Chat', 'website-chatbot'); ?>
                                </a>
                            </li>
                            <?php if (get_option('chatbot_enable_feedback', true)): ?>
                            <li>
                                <a href="#" class="menu-feedback">
                                    <?php _e('Send Feedback', 'website-chatbot'); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Close Button -->
                <button class="chat-close" 
                        title="<?php esc_attr_e('Close Chat', 'website-chatbot'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Messages Container -->
        <div class="chat-messages" id="chatMessages">
            <div class="message bot-message">
                <div class="message-content">
                    <?php echo wp_kses_post(get_option('chatbot_welcome_message', __('Hello! How can I help you today?', 'website-chatbot'))); ?>
                </div>
                <div class="message-time">
                    <?php echo esc_html(wp_date(get_option('time_format'))); ?>
                </div>
            </div>
        </div>

        <!-- Chat Input Area -->
        <div class="chat-input-container">
            <textarea 
                class="chat-input" 
                id="userInput" 
                placeholder="<?php echo esc_attr(get_option('chatbot_placeholder_text', __('Type your message here...', 'website-chatbot'))); ?>"
                rows="1"
                aria-label="<?php esc_attr_e('Chat message', 'website-chatbot'); ?>"
            ></textarea>
            <button class="send-button" 
                    id="sendButton"
                    aria-label="<?php esc_attr_e('Send message', 'website-chatbot'); ?>">
                <?php _e('Send', 'website-chatbot'); ?>
            </button>
        </div>

        <!-- Feedback Modal -->
        <?php if (get_option('chatbot_enable_feedback', true)): ?>
                <div id="feedback-modal" class="chatbot-modal" style="display: none;">
                    <div class="chatbot-modal-content">
                        <div class="chatbot-modal-header">
                            <h4><?php _e('Send Feedback', 'website-chatbot'); ?></h4>
                            <button class="feedback-close">&times;</button>
                        </div>
                        <div class="chatbot-modal-body">
                            <textarea 
                                id="feedback-text" 
                                placeholder="<?php esc_attr_e('Please share your feedback...', 'website-chatbot'); ?>" 
                                rows="4"
                            ></textarea>
                            <button class="submit-feedback">
                                <?php _e('Submit', 'website-chatbot'); ?>
                            </button>
                        </div>
                    </div>
                </div>
        <?php endif; ?>
    </div>

    <!-- Powered By -->
    <div class="chatbot-footer">
        <small>
            <?php 
            printf(
                __('Powered by %s', 'website-chatbot'),
                '<a href="https://studyshoot.com/" target="_blank">studyshoot </a>'
            );
            ?>
        </small>
    </div>
</div>