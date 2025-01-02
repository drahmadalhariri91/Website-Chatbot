<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('chatbot_messages'); ?>

    <div class="chatbot-admin-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'website-chatbot'); ?></a>
            <a href="#appearance" class="nav-tab"><?php _e('Appearance', 'website-chatbot'); ?></a>
            <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'website-chatbot'); ?></a>
            <a href="#extra" class="nav-tab"><?php _e('Extra', 'website-chatbot'); ?></a>
        </nav>

        <form action="options.php" method="post">
            <?php
            settings_fields('chatbot_settings');
            ?>

            <!-- General Settings Tab -->
            <div id="general" class="tab-content active">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chatbot_openai_key"><?php _e('OpenAI API Key', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="chatbot_openai_key" 
                                   name="chatbot_openai_key" 
                                   class="regular-text"
                                   value="<?php echo esc_attr(get_option('chatbot_openai_key')); ?>"
                            />
                            <p class="description">
                                <?php _e('Enter your OpenAI API key. Get one from <a href="https://platform.openai.com" target="_blank">OpenAI Platform</a>', 'website-chatbot'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chatbot_model"><?php _e('AI Model', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <select id="chatbot_model" name="chatbot_model">
                                <option value="gpt-3.5-turbo" <?php selected(get_option('chatbot_model'), 'gpt-3.5-turbo'); ?>>
                                    GPT-3.5 Turbo
                                </option>
                                <option value="gpt-4" <?php selected(get_option('chatbot_model'), 'gpt-4'); ?>>
                                    GPT-4 (if available)
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chatbot_welcome_message"><?php _e('Welcome Message', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <textarea id="chatbot_welcome_message" 
                                      name="chatbot_welcome_message" 
                                      class="large-text" 
                                      rows="3"><?php echo esc_textarea(get_option('chatbot_welcome_message')); ?></textarea>
                        </td>
                    </tr>



                        <tr>
                            <th scope="row">
                                <label for="chatbot_custom_prompt"><?php _e('Custom Response Prompt', 'website-chatbot'); ?></label>
                            </th>
                            <td>
                                <textarea 
                                    id="chatbot_custom_prompt" 
                                    name="chatbot_custom_prompt" 
                                    class="large-text code" 
                                    rows="10"
                                    placeholder="<?php echo esc_attr__('Example:
                        You are a helpful assistant for {site_name}. Your role is to:
                        1. Provide clear and friendly responses
                        2. Stay focused on website-related information
                        3. Be concise but thorough
                        4. Use a professional tone
                        5. Include relevant links when appropriate', 'website-chatbot'); ?>"
                                ><?php echo esc_textarea(get_option('chatbot_custom_prompt')); ?></textarea>
                                <p class="description">
                                    <?php _e('Customize how the chatbot should respond to visitors. Use these placeholders:', 'website-chatbot'); ?>
                                    <br>
                                    <code>{site_name}</code> - <?php _e('Your website name', 'website-chatbot'); ?>
                                    <br>
                                    <code>{current_page}</code> - <?php _e('The current page title', 'website-chatbot'); ?>
                                </p>
                                <p class="description highlight">
                                    <?php _e('This prompt will be combined with your website content to guide the AI responses.', 'website-chatbot'); ?>
                                </p>
                            </td>
                        </tr>











                    
                </table>
            </div>

            <!-- Appearance Settings Tab -->
            <div id="appearance" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Position', 'website-chatbot'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" 
                                           name="chatbot_position" 
                                           value="bottom-right" 
                                           <?php checked(get_option('chatbot_position'), 'bottom-right'); ?>
                                    />
                                    <?php _e('Bottom Right', 'website-chatbot'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" 
                                           name="chatbot_position" 
                                           value="bottom-left" 
                                           <?php checked(get_option('chatbot_position'), 'bottom-left'); ?>
                                    />
                                    <?php _e('Bottom Left', 'website-chatbot'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Theme', 'website-chatbot'); ?></th>
                        <td>
                            <select name="chatbot_theme" id="chatbot_theme">
                                <option value="light" <?php selected(get_option('chatbot_theme'), 'light'); ?>>
                                    <?php _e('Light', 'website-chatbot'); ?>
                                </option>
                                <option value="dark" <?php selected(get_option('chatbot_theme'), 'dark'); ?>>
                                    <?php _e('Dark', 'website-chatbot'); ?>
                                </option>
                                <option value="auto" <?php selected(get_option('chatbot_theme'), 'auto'); ?>>
                                    <?php _e('Auto (System)', 'website-chatbot'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Advanced Settings Tab -->
            <div id="advanced" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chatbot_max_tokens"><?php _e('Max Tokens', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="chatbot_max_tokens" 
                                   name="chatbot_max_tokens" 
                                   value="<?php echo esc_attr(get_option('chatbot_max_tokens', 500)); ?>"
                                   min="100" 
                                   max="4000" 
                            />
                            <p class="description">
                                <?php _e('Maximum number of tokens for each response. Higher values may result in more detailed answers but also higher API costs.', 'website-chatbot'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chatbot_temperature"><?php _e('Temperature', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="chatbot_temperature" 
                                   name="chatbot_temperature" 
                                   value="<?php echo esc_attr(get_option('chatbot_temperature', 0.2)); ?>"
                                   min="0" 
                                   max="2" 
                                   step="0.1" 
                            />
                            <p class="description">
                                <?php _e('Controls randomness in responses. Lower values make responses more focused and deterministic.', 'website-chatbot'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chatbot_delete_data_after"><?php _e('Data Retention', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="chatbot_delete_data_after" 
                                   name="chatbot_delete_data_after" 
                                   value="<?php echo esc_attr(get_option('chatbot_delete_data_after', 30)); ?>"
                                   min="1" 
                            />
                            <span><?php _e('days', 'website-chatbot'); ?></span>
                            <p class="description">
                                <?php _e('Chat history will be automatically deleted after this many days.', 'website-chatbot'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

          <!-- New Extra Tab -->
            <div id="extra" class="tab-content" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chatbot_title"><?php _e('Chatbot Title', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="chatbot_title" 
                                   name="chatbot_title" 
                                   class="regular-text"
                                   value="<?php echo esc_attr(get_option('chatbot_title', __('Website Assistant', 'website-chatbot'))); ?>"
                            />
                            <p class="description">
                                <?php _e('Title displayed in the chat header', 'website-chatbot'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chatbot_placeholder_text"><?php _e('Placeholder Text', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="chatbot_placeholder_text" 
                                   name="chatbot_placeholder_text" 
                                   class="regular-text"
                                   value="<?php echo esc_attr(get_option('chatbot_placeholder_text', __('Type your message here...', 'website-chatbot'))); ?>"
                            />
                            <p class="description">
                                <?php _e('Placeholder text for the chat input field', 'website-chatbot'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chatbot_enable_feedback"><?php _e('Enable Feedback', 'website-chatbot'); ?></label>
                        </th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" 
                                       id="chatbot_enable_feedback"
                                       name="chatbot_enable_feedback" 
                                       value="1" 
                                       <?php checked(1, get_option('chatbot_enable_feedback', 1), true); ?> 
                                />
                                <span class="slider round"></span>
                            </label>
                            <p class="description">
                                <?php _e('Allow users to send feedback through the chatbot', 'website-chatbot'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(); ?>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching functionality
    $('.chatbot-admin-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });

    // Optional: Add some CSS for the toggle switch
    var style = `
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }
    input:checked + .slider {
        background-color: #2196F3;
    }
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    .slider.round {
        border-radius: 24px;
    }
    .slider.round:before {
        border-radius: 50%;
    }`;
    
    $('<style>').prop('type', 'text/css').html(style).appendTo('head');
});
</script>