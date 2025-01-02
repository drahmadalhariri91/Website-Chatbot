<?php
/**
 * Plugin Name: Website Chatbot
 * Plugin URI: https://studyshoot.com/ 
 * Description: AI-powered chatbot for WordPress websites using OpenAI's GPT
 * Version: 1.0.0
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://studyshoot.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: website-chatbot
 * Domain Path: /languages
 *
 * @package WebsiteChatbot
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WEBSITE_CHATBOT_VERSION', '1.0.0');
define('WEBSITE_CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEBSITE_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WEBSITE_CHATBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Class autoloader
 *
 * @param string $class_name The class name to autoload
 */
function website_chatbot_autoloader($class_name) {
    // Check if the class is in our namespace
    if (strpos($class_name, 'WebsiteChatbot\\') !== 0) {
        return;
    }

    // Remove namespace from class name
    $class_file = str_replace('WebsiteChatbot\\', '', $class_name);
    // Convert class name format to file name format
    $class_file = 'class-' . str_replace('_', '-', strtolower($class_file)) . '.php';
    
    $file = WEBSITE_CHATBOT_PLUGIN_DIR . 'includes/' . $class_file;

    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('website_chatbot_autoloader');

// Load required files
require_once WEBSITE_CHATBOT_PLUGIN_DIR . 'includes/class-website-chatbot.php';
require_once WEBSITE_CHATBOT_PLUGIN_DIR . 'includes/class-admin.php';
require_once WEBSITE_CHATBOT_PLUGIN_DIR . 'includes/class-chat-handler.php';
require_once WEBSITE_CHATBOT_PLUGIN_DIR . 'includes/class-installer.php';

// Initialize the plugin
function website_chatbot_init() {
    // Initialize main plugin class
    WebsiteChatbot\Website_Chatbot::get_instance();
}
add_action('plugins_loaded', 'website_chatbot_init');

// Register activation hook
register_activation_hook(__FILE__, array('WebsiteChatbot\Installer', 'activate'));

// Register deactivation hook
register_deactivation_hook(__FILE__, array('WebsiteChatbot\Installer', 'deactivate'));

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=website-chatbot'),
        __('Settings', 'website-chatbot')
    );
    array_unshift($links, $settings_link);
    return $links;
});