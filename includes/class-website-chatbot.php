<?php
namespace WebsiteChatbot;

/**
 * Main plugin class
 */
class Website_Chatbot {
    /**
     * Plugin instance.
     *
     * @var Website_Chatbot
     */
    private static $instance = null;

    /**
     * Admin instance
     *
     * @var Admin
     */
    private $admin;

    /**
     * Get plugin instance.
     *
     * @return Website_Chatbot
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_admin();
        $this->init_hooks();
    }

    /**
     * Initialize admin.
     */
    private function init_admin() {
        if (is_admin()) {
            $this->admin = new Admin();
        }
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        
        // Register AJAX handlers
        add_action('wp_ajax_chatbot_message', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_chatbot_message', array($this, 'handle_chat_request'));
        // Add chatbot widget
        add_action('wp_footer', array($this, 'render_chatbot_widget'));



        add_action('wp_ajax_get_chat_messages', array($this, 'get_chat_messages'));
        add_action('wp_ajax_nopriv_get_chat_messages', array($this, 'get_chat_messages'));



        // Add feedback submission handler
        add_action('wp_ajax_submit_chatbot_feedback', array($this, 'submit_chatbot_feedback'));
        add_action('wp_ajax_nopriv_submit_chatbot_feedback', array($this, 'submit_chatbot_feedback'));
 

}

    /**
     * Load plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'website-chatbot',
            false,
            dirname(plugin_basename(WEBSITE_CHATBOT_PLUGIN_DIR)) . '/languages/'
        );
    }

    /**
     * Register and enqueue assets.
     */
 
    public function register_assets() {
        // Register styles
        wp_enqueue_style(
            'website-chatbot',
            WEBSITE_CHATBOT_PLUGIN_URL . 'assets/css/chatbot.css',
            array(),
            WEBSITE_CHATBOT_VERSION
        );

        // Register scripts
        wp_enqueue_script(
            'website-chatbot',
            WEBSITE_CHATBOT_PLUGIN_URL . 'assets/js/chatbot.js',
            array('jquery'),
            WEBSITE_CHATBOT_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'website-chatbot',
            'chatbotSettings',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('chatbot_nonce'),
                'strings' => array(
                    'error' => esc_html__('An error occurred. Please try again.', 'website-chatbot'),
                    'offline' => esc_html__('You are currently offline.', 'website-chatbot'),
                    'welcome' => esc_html__('Hello! How can I help you today?', 'website-chatbot'),
                    'confirmClear' => esc_html__('Are you sure you want to clear the chat history?', 'website-chatbot'),
                    'feedbackSuccess' => __('Thank you for your feedback!', 'website-chatbot'),
                    'feedbackError' => __('Failed to submit feedback. Please try again.', 'website-chatbot'),


                ),
            )
        );
    }

    /**
     * Handle chat AJAX requests.
     */
 
public function handle_chat_request() {
    try {
        // Verify nonce and check if request is AJAX
        if (!check_ajax_referer('chatbot_nonce', 'nonce', false) || !wp_doing_ajax()) {
            wp_send_json_error(array(
                'message' => __('Invalid request', 'website-chatbot')
            ), 403);
            wp_die();
        }

        // Validate and sanitize input
        $question = isset($_POST['question']) ? sanitize_text_field(wp_unslash($_POST['question'])) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';

        if (empty($question)) {
            wp_send_json_error(array(
                'message' => __('Please enter a question', 'website-chatbot')
            ), 400);
            wp_die();
        }

        // Initialize chat handler
        $chat_handler = new Chat_Handler();
        
        // Process request and get response
        $response = $chat_handler->process_request($question, $session_id);

        // Send success response
        wp_send_json_success($response);

    } catch (Exception $e) {
        // Log the error
        error_log('Chatbot Error: ' . $e->getMessage());
        
        // Send error response
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ), 500);
    }

    // Ensure the request ends properly
    wp_die();
}

 



/**
 * Retrieve chat messages for a session
 */
public function get_chat_messages() {
    try {
        // Verify nonce
        if (!check_ajax_referer('chatbot_nonce', 'nonce', false)) {
            throw new \Exception(__('Security check failed', 'website-chatbot'));
        }

        // Validate and sanitize session ID
        $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';

        if (empty($session_id)) {
            throw new \Exception(__('Invalid session', 'website-chatbot'));
        }

        // Retrieve messages
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_messages';
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT message, sender, timestamp 
             FROM $table_name 
             WHERE session_id = %s 
             ORDER BY timestamp ASC 
             LIMIT 100", // Limit to prevent overwhelming retrieval
            $session_id
        ), ARRAY_A);

        wp_send_json_success($messages);

    } catch (\Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}





public function submit_chatbot_feedback() {
    // Verify nonce
    if (!check_ajax_referer('chatbot_nonce', 'nonce', false)) {
        wp_send_json_error('Security check failed');
    }

    // Sanitize inputs
    $feedback = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

    // Validate input
    if (empty($feedback)) {
        wp_send_json_error('Feedback cannot be empty');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_feedback';

    // Insert feedback
    $result = $wpdb->insert(
        $table_name,
        [
            'session_id' => $session_id,
            'user_id' => get_current_user_id() ?: 0,
            'feedback' => $feedback,
            'created_at' => current_time('mysql')
        ],
        ['%s', '%d', '%s', '%s']
    );

    if ($result) {
        wp_send_json_success('Feedback submitted successfully');
    } else {
        wp_send_json_error('Failed to submit feedback');
    }
}

 
    /**
     * Render chatbot widget.
     */
    public function render_chatbot_widget() {
        include WEBSITE_CHATBOT_PLUGIN_DIR . 'templates/chatbot-widget.php';
    }
}