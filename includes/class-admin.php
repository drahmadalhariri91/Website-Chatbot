<?php
namespace WebsiteChatbot;

/**
 * Admin class
 */
class Admin {
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add AJAX handlers
        add_action('wp_ajax_get_chat_history', array($this, 'get_chat_history'));
        add_action('wp_ajax_get_chat_analytics', array($this, 'get_chat_analytics'));
        add_action('wp_ajax_delete_chat_history', array($this, 'delete_chat_history'));

        add_action('wp_ajax_delete_chatbot_feedback', array($this, 'delete_feedback'));








    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Website Chatbot', 'website-chatbot'),
            __('Chatbot', 'website-chatbot'),
            'manage_options',
            'website-chatbot',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'website-chatbot',
            __('Settings', 'website-chatbot'),
            __('Settings', 'website-chatbot'),
            'manage_options',
            'website-chatbot',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'website-chatbot',
            __('Chat History', 'website-chatbot'),
            __('Chat History', 'website-chatbot'),
            'manage_options',
            'chatbot-history',
            array($this, 'render_history_page')
        );

        add_submenu_page(
            'website-chatbot',
            __('Analytics', 'website-chatbot'),
            __('Analytics', 'website-chatbot'),
            'manage_options',
            'chatbot-analytics',
            array($this, 'render_analytics_page')
        );

    add_submenu_page(
        'website-chatbot',
        __('Statistics', 'website-chatbot'),
        __('Statistics', 'website-chatbot'),
        'manage_options',
        'chatbot-statistics',
        array($this, 'render_statistics_page')
    );
    
    // Add Feedback submenu
    add_submenu_page(
        'website-chatbot',
        __('Feedback', 'website-chatbot'),
        __('Feedback', 'website-chatbot'),
        'manage_options',
        'chatbot-feedback',
        array($this, 'render_feedback_page')
    );









    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Register settings sections
        add_settings_section(
            'chatbot_general_settings',
            __('General Settings', 'website-chatbot'),
            array($this, 'render_general_section'),
            'chatbot_settings'
        );

        add_settings_section(
            'chatbot_appearance_settings',
            __('Appearance Settings', 'website-chatbot'),
            array($this, 'render_appearance_section'),
            'chatbot_settings'
        );

        add_settings_section(
            'chatbot_advanced_settings',
            __('Advanced Settings', 'website-chatbot'),
            array($this, 'render_advanced_section'),
            'chatbot_settings'
        );











        // Register General Settings
        register_setting('chatbot_settings', 'chatbot_openai_key');
        register_setting('chatbot_settings', 'chatbot_model');

        // Register Appearance Settings
        register_setting('chatbot_settings', 'chatbot_position');
        register_setting('chatbot_settings', 'chatbot_theme');
        register_setting('chatbot_settings', 'chatbot_title');
        register_setting('chatbot_settings', 'chatbot_welcome_message');
        register_setting('chatbot_settings', 'chatbot_placeholder_text');

        // Register Advanced Settings
        register_setting('chatbot_settings', 'chatbot_max_tokens', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 500
        ));
        register_setting('chatbot_settings', 'chatbot_temperature', array(
            'type' => 'number',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => 0.2
        ));






    // Add additional settings fields for welcome message, placeholder, etc.
    register_setting('chatbot_settings', 'chatbot_title', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => __('Website Assistant', 'website-chatbot')
    ));

    register_setting('chatbot_settings', 'chatbot_welcome_message', array(
        'type' => 'string',
        'sanitize_callback' => 'wp_kses_post',
        'default' => __('Hello! How can I help you today?', 'website-chatbot')
    ));

    register_setting('chatbot_settings', 'chatbot_placeholder_text', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => __('Type your message here...', 'website-chatbot')
    ));

    register_setting('chatbot_settings', 'chatbot_enable_feedback', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true
    ));


    // Add Custom Prompt Setting
    register_setting('chatbot_settings', 'chatbot_custom_prompt', array(
        'type' => 'string',
        'sanitize_callback' => 'wp_kses_post',
        'default' => 'You are a helpful assistant for {site_name}. Your role is to:
1. Provide clear and friendly responses
2. Stay focused on website-related information
3. Be concise but thorough
4. Use a professional tone
5. Include relevant links when appropriate'
    ));

    add_settings_field(
        'chatbot_custom_prompt',
        __('Custom Response Prompt', 'website-chatbot'),
        array($this, 'render_textarea_field'),
        'chatbot_settings',
        'chatbot_advanced_settings',
        array(
            'label_for' => 'chatbot_custom_prompt',
            'description' => __('Customize how the chatbot should respond to visitors. Use {site_name} as a placeholder for your website name.', 'website-chatbot')
        )
    );










    // Add settings fields for the new options
    add_settings_field(
        'chatbot_title',
        __('Chatbot Title', 'website-chatbot'),
        array($this, 'render_text_field'),
        'chatbot_settings',
        'chatbot_appearance_settings',
        array(
            'label_for' => 'chatbot_title',
            'description' => __('Title displayed in the chat header', 'website-chatbot')
        )
    );

    add_settings_field(
        'chatbot_placeholder_text',
        __('Placeholder Text', 'website-chatbot'),
        array($this, 'render_text_field'),
        'chatbot_settings',
        'chatbot_appearance_settings',
        array(
            'label_for' => 'chatbot_placeholder_text',
            'description' => __('Placeholder text for the chat input', 'website-chatbot')
        )
    );

    add_settings_field(
        'chatbot_enable_feedback',
        __('Enable Feedback', 'website-chatbot'),
        array($this, 'render_toggle_field'),
        'chatbot_settings',
        'chatbot_appearance_settings',
        array(
            'label_for' => 'chatbot_enable_feedback',
            'description' => __('Allow users to send feedback', 'website-chatbot')
        )
    );

 

        // Add Settings Fields - General
        add_settings_field(
            'chatbot_openai_key',
            __('OpenAI API Key', 'website-chatbot'),
            array($this, 'render_text_field'),
            'chatbot_settings',
            'chatbot_general_settings',
            array(
                'label_for' => 'chatbot_openai_key',
                'type' => 'password'
            )
        );

        add_settings_field(
            'chatbot_model',
            __('AI Model', 'website-chatbot'),
            array($this, 'render_select_field'),
            'chatbot_settings',
            'chatbot_general_settings',
            array(
                'label_for' => 'chatbot_model',
                'options' => array(
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4' => 'GPT-4'
                )
            )
        );

        // Add Settings Fields - Appearance
        add_settings_field(
            'chatbot_position',
            __('Position', 'website-chatbot'),
            array($this, 'render_radio_field'),
            'chatbot_settings',
            'chatbot_appearance_settings',
            array(
                'label_for' => 'chatbot_position',
                'options' => array(
                    'bottom-right' => __('Bottom Right', 'website-chatbot'),
                    'bottom-left' => __('Bottom Left', 'website-chatbot')
                )
            )
        );

        add_settings_field(
            'chatbot_theme',
            __('Theme', 'website-chatbot'),
            array($this, 'render_select_field'),
            'chatbot_settings',
            'chatbot_appearance_settings',
            array(
                'label_for' => 'chatbot_theme',
                'options' => array(
                    'light' => __('Light', 'website-chatbot'),
                    'dark' => __('Dark', 'website-chatbot'),
                    'auto' => __('Auto (System)', 'website-chatbot')
                )
            )
        );

        // Add the rest of your settings fields here
    }





public function render_toggle_field($args) {
    $option_name = $args['label_for'];
    $value = get_option($option_name, true);
    ?>
    <label class="switch">
        <input type="checkbox" 
               id="<?php echo esc_attr($option_name); ?>" 
               name="<?php echo esc_attr($option_name); ?>" 
               value="1" 
               <?php checked(1, $value, true); ?> 
        />
        <span class="slider"></span>
    </label>
    <?php
    if (isset($args['description'])) {
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }
}







    /**
     * Render text field
     */
    public function render_text_field($args) {
        $option_name = $args['label_for'];
        $type = isset($args['type']) ? $args['type'] : 'text';
        $value = get_option($option_name);
        ?>
        <input type="<?php echo esc_attr($type); ?>"
               id="<?php echo esc_attr($option_name); ?>"
               name="<?php echo esc_attr($option_name); ?>"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
        />
        <?php
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Render textArea field
     */

    public function render_textarea_field($args) {
        $option_name = $args['label_for'];
        $value = get_option($option_name);
        ?>
        <textarea
            id="<?php echo esc_attr($option_name); ?>"
            name="<?php echo esc_attr($option_name); ?>"
            class="large-text code"
            rows="10"
        ><?php echo esc_textarea($value); ?></textarea>
        <?php
        if (isset($args['description'])) {
            echo '<p class="description">' . wp_kses_post($args['description']) . '</p>';
        }
    }
    /**
     * Render select field
     */
    public function render_select_field($args) {
        $option_name = $args['label_for'];
        $value = get_option($option_name);
        ?>
        <select id="<?php echo esc_attr($option_name); ?>"
                name="<?php echo esc_attr($option_name); ?>">
            <?php foreach ($args['options'] as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>"
                        <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Render radio field
     */
    public function render_radio_field($args) {
        $option_name = $args['label_for'];
        $value = get_option($option_name);
        ?>
        <fieldset>
            <?php foreach ($args['options'] as $key => $label): ?>
                <label>
                    <input type="radio"
                           name="<?php echo esc_attr($option_name); ?>"
                           value="<?php echo esc_attr($key); ?>"
                           <?php checked($value, $key); ?>
                    />
                    <?php echo esc_html($label); ?>
                </label>
                <br>
            <?php endforeach; ?>
        </fieldset>
        <?php
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Render section descriptions
     */
    public function render_general_section() {
        echo '<p>' . esc_html__('Configure your OpenAI API settings here.', 'website-chatbot') . '</p>';
    }

    public function render_appearance_section() {
        echo '<p>' . esc_html__('Customize how the chatbot looks and behaves.', 'website-chatbot') . '</p>';
    }

    public function render_advanced_section() {
        echo '<p>' . esc_html__('Advanced settings for fine-tuning the chatbot behavior.', 'website-chatbot') . '</p>';
    }


    /**
     * Add privacy policy content.
     */
    public function add_privacy_policy_content() {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = sprintf(
            __('When you use the Website Chatbot, we collect and store the following data:

            * Chat messages and conversations
            * Session information
            * User feedback (if provided)
            * IP addresses (optional, can be disabled in settings)

            This data is stored in your WordPress database for %d days (configurable in settings).
            
            Users can request their data to be exported or deleted through WordPress\'s built-in privacy tools.
            
            Third-party service used:
            * OpenAI API - Chat messages are processed through OpenAI\'s API. See OpenAI\'s privacy policy for details.', 'website-chatbot'),
            get_option('chatbot_data_retention', 30)
        );

        wp_add_privacy_policy_content('Website Chatbot', wp_kses_post(wpautop($content)));
    }








    /**
     * Enqueue admin assets
     */
 
 
    public function enqueue_admin_assets($hook) {
        $chatbot_pages = array(
            'website-chatbot',
            'chatbot-history',
            'chatbot-analytics',
            'chatbot-statistics', 
            'chatbot-feedback' 
        );

        // Check if the current page is a chatbot admin page
        $is_chatbot_page = false;
        if (isset($_GET['page']) && in_array($_GET['page'], $chatbot_pages)) {
            $is_chatbot_page = true;
        }

        // Only proceed if it's a chatbot page
        if (!$is_chatbot_page) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'website-chatbot-admin',
            WEBSITE_CHATBOT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WEBSITE_CHATBOT_VERSION
        );

        // Load Chart.js only on analytics page
        if (isset($_GET['page']) && $_GET['page'] === 'chatbot-analytics') {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                array('jquery'),
                '3.9.1',
                true
            );
        }

        // Enqueue admin script
            wp_enqueue_script(
                'website-chatbot-admin',
                WEBSITE_CHATBOT_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'), // Remove 'chartjs' from dependencies
                WEBSITE_CHATBOT_VERSION,
                true
            );

        // Localize admin script
        wp_localize_script(
            'website-chatbot-admin',
            'chatbotAdmin',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('chatbot_admin_nonce'),
                'strings' => array(
                    'confirmDelete' => __('Are you sure you want to delete this conversation?', 'website-chatbot'),
                    'loading' => __('Loading...', 'website-chatbot'),
                    'error' => __('An error occurred', 'website-chatbot'),
                    'success' => __('Operation completed successfully', 'website-chatbot'),
                ),
                'colors' => array(
                    'primary' => '#0084ff',
                    'secondary' => '#e9ecef',
                    'text' => '#666666'
                )
            )
        );

        // Add inline script for page-specific initialization
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        $inline_script = "var chatbotCurrentPage = '" . esc_js($current_page) . "';";
        wp_add_inline_script('website-chatbot-admin', $inline_script, 'before');
    }
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        include WEBSITE_CHATBOT_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Render history page
     */
    public function render_history_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;
        
        // Setup pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        // Get total sessions count
        $total_sessions = $wpdb->get_var("
            SELECT COUNT(DISTINCT session_id) 
            FROM {$wpdb->prefix}chatbot_sessions
        ");

        // Get sessions with latest message
        $sessions = $wpdb->get_results($wpdb->prepare("
            SELECT 
                s.*,
                COUNT(m.id) as message_count,
                MAX(m.timestamp) as last_message,
                MAX(CASE WHEN m.sender = 'user' THEN m.message END) as last_user_message
            FROM {$wpdb->prefix}chatbot_sessions s
            LEFT JOIN {$wpdb->prefix}chatbot_messages m ON s.session_id = m.session_id
            GROUP BY s.session_id
            ORDER BY last_message DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));

        // Calculate total pages
        $total_pages = ceil($total_sessions / $per_page);

        // Include template
        include WEBSITE_CHATBOT_PLUGIN_DIR . 'templates/admin/history.php';
    }

    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        include WEBSITE_CHATBOT_PLUGIN_DIR . 'templates/admin/analytics.php';
    }





public function render_statistics_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
    global $wpdb;
    
    // Get overall stats
    $total_conversations = $wpdb->get_var("
        SELECT COUNT(DISTINCT session_id) 
        FROM {$wpdb->prefix}chatbot_messages
    ");
    
    $total_messages = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->prefix}chatbot_messages
    ");
    
    $avg_messages_per_conversation = $wpdb->get_var("
        SELECT AVG(message_count) 
        FROM (
            SELECT session_id, COUNT(*) as message_count 
            FROM {$wpdb->prefix}chatbot_messages 
            GROUP BY session_id
        ) as counts
    ");
    
    // Get daily stats for the last 30 days
    $daily_stats = $wpdb->get_results("
        SELECT 
            DATE(timestamp) as date,
            COUNT(DISTINCT session_id) as conversations,
            COUNT(*) as messages
        FROM {$wpdb->prefix}chatbot_messages
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(timestamp)
        ORDER BY date DESC
    ");

    // Include template
    include WEBSITE_CHATBOT_PLUGIN_DIR . 'templates/admin/statistics.php';
}

public function render_feedback_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

    global $wpdb;
    
    // Setup pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Get total feedback count
    $total_items = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->prefix}chatbot_feedback
    ");
    
    // Get feedback items
    $feedback_items = $wpdb->get_results($wpdb->prepare("
        SELECT f.*, s.user_id, s.user_ip, s.created_at as session_created
        FROM {$wpdb->prefix}chatbot_feedback f
        LEFT JOIN {$wpdb->prefix}chatbot_sessions s ON f.session_id = s.session_id
        ORDER BY f.created_at DESC
        LIMIT %d OFFSET %d
    ", $per_page, $offset));
    
    // Calculate total pages
    $total_pages = ceil($total_items / $per_page);
    
    // Include template
    include WEBSITE_CHATBOT_PLUGIN_DIR . 'templates/admin/feedback.php';
}






 

    /**
     * Get chat history
     */
    public function get_chat_history() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'website-chatbot'));
        }

        $session_id = sanitize_text_field($_POST['session_id']);

        global $wpdb;
        $messages = $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}chatbot_messages
            WHERE session_id = %s
            ORDER BY timestamp ASC
        ", $session_id));

        wp_send_json_success($messages);
    }

    /**
     * Get chat analytics
     */
    public function get_chat_analytics() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'website-chatbot'));
        }

        global $wpdb;
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';
        
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

        $data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(timestamp) as date,
                COUNT(DISTINCT session_id) as conversations,
                COUNT(*) as messages
            FROM {$wpdb->prefix}chatbot_messages
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC
        ", $days));

        // Fill in missing dates with zero values
        $complete_data = array();
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date = date('Y-m-d');
        
        $current_date = $start_date;
        while ($current_date <= $end_date) {
            $found = false;
            foreach ($data as $row) {
                if ($row->date === $current_date) {
                    $complete_data[] = $row;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $complete_data[] = (object) array(
                    'date' => $current_date,
                    'conversations' => 0,
                    'messages' => 0
                );
            }
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }

        wp_send_json_success($complete_data);
    }

    /**
     * Delete chat history
     */
    public function delete_chat_history() {
        check_ajax_referer('chatbot_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'website-chatbot'));
        }

        $session_id = sanitize_text_field($_POST['session_id']);

        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'chatbot_messages',
            array('session_id' => $session_id),
            array('%s')
        );
        
        $wpdb->delete(
            $wpdb->prefix . 'chatbot_sessions',
            array('session_id' => $session_id),
            array('%s')
        );

        wp_send_json_success();
    }

    /**
     * Get analytics data
     */
    private function get_analytics_data($period) {
        global $wpdb;
        
        switch ($period) {
            case '7days':
                $days = 7;
                break;
            case '30days':
                $days = 30;
                break;
            case '90days':
                $days = 90;
                break;
            default:
                $days = 30;
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(timestamp) as date,
                COUNT(DISTINCT session_id) as conversations,
                COUNT(*) as messages
            FROM {$wpdb->prefix}chatbot_messages
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC
        ", $days));
    }

    /**
     * Sanitize float values
     */
    public function sanitize_float($value) {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
  





    public function delete_feedback() {
        try {
            // Verify nonce
            if (!check_ajax_referer('chatbot_admin_nonce', 'nonce', false)) {
                wp_send_json_error(__('Security check failed', 'website-chatbot'));
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Unauthorized access', 'website-chatbot'));
            }

            $feedback_id = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;
            
            if (!$feedback_id) {
                wp_send_json_error(__('Invalid feedback ID', 'website-chatbot'));
            }

            global $wpdb;
            
            $result = $wpdb->delete(
                $wpdb->prefix . 'chatbot_feedback',
                array('id' => $feedback_id),
                array('%d')
            );

            if ($result === false) {
                wp_send_json_error(__('Database error', 'website-chatbot'));
            }

            wp_send_json_success();

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }


}