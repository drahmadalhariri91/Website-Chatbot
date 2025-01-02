<?php
namespace WebsiteChatbot;

/**
 * Handles chat processing and OpenAI integration.
 *
 * @package WebsiteChatbot
 */
class Chat_Handler {
    /**
     * OpenAI API endpoint
     *
     * @var string
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Process chat request
     *
     * @param string $question   The user's question
     * @param string $session_id The chat session ID
     * @return array The response data
     * @throws \Exception
     */
    public function process_request($question, $session_id) {
        try {
            // Validate API key
            $api_key = $this->get_api_key();
            if (empty($api_key)) {
                throw new \Exception(__('OpenAI API key not configured', 'website-chatbot'));
            }

            // Rate limiting check
            if ($this->is_rate_limited($session_id)) {
                throw new \Exception(__('Please wait a moment before sending another message', 'website-chatbot'));
            }

            // Create system prompt
            $system_prompt = $this->get_system_prompt();

            // Make API request
            $response = wp_remote_post($this->api_endpoint, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode(array(
                    'model' => get_option('chatbot_model', 'gpt-3.5-turbo'),
                    'messages' => array(
                        array(
                            'role' => 'system',
                            'content' => $system_prompt
                        ),
                        array(
                            'role' => 'user',
                            'content' => $question
                        )
                    ),
                    'max_tokens' => (int) get_option('chatbot_max_tokens', 500),
                    'temperature' => (float) get_option('chatbot_temperature', 0.7)
                )),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!isset($body['choices'][0]['message']['content'])) {
                throw new \Exception(__('Invalid API response', 'website-chatbot'));
            }

            $answer = $body['choices'][0]['message']['content'];

            // Save chat messages
            $this->save_chat_message($session_id, $question, 'user');
            $this->save_chat_message($session_id, $answer, 'bot');

            // Update rate limiting
            $this->update_rate_limit($session_id);

            return array(
                'answer' => $answer,
                'session_id' => $session_id
            );

        } catch (\Exception $e) {
            error_log('Chatbot Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get OpenAI API key
     *
     * @return string
     */
    private function get_api_key() {
        return get_option('chatbot_openai_key');
    }

    /**
     * Get system prompt
     *
     * @return string
     */
private function get_system_prompt() {
    // Get custom prompt from settings
    $custom_prompt = get_option('chatbot_custom_prompt');
    // Add list formatting instructions
    $list_instructions = "\n\nWhen providing lists, please:\n" .
        "1. Start each numbered item with a number and period (1., 2., etc.)\n" .
        "2. Start each bullet point with a dash (-)\n" .
        "3. For nested items, add appropriate indentation\n" .
        "4. Put each list item on its own line\n" .
        "5. Add a brief description after each list item";

    // Replace placeholders
    $custom_prompt = str_replace(
        array('{site_name}'),
        array(get_bloginfo('name')),
        $custom_prompt
    );

    // Get website training data
    $data = $this->$custom_prompt;
    $knowledge_base = array();
    
    foreach ($data['content'] as $item) {
        $content = $item['content'];
        if (strlen($content) > 100) {
            $knowledge_base[] = sprintf(
                "Content: %s\nTitle: %s\nURL: %s\n---",
                $content,
                $item['title'],
                $item['url']
            );
        }
    }
    
    // Combine custom prompt with knowledge base
    return sprintf(
        "%s\n\n%s\n\nAvailable Website Content:\n%s",
        $custom_prompt,
        $list_instructions,
        implode("\n", array_slice($knowledge_base, 0, 15))
    );
}
    /**
     * Save chat message
     *
     * @param string $session_id The session ID
     * @param string $message    The message content
     * @param string $sender     The message sender (user/bot)
     */
    private function save_chat_message($session_id, $message, $sender) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'chatbot_messages',
            array(
                'session_id' => $session_id,
                'message' => $message,
                'sender' => $sender,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Check if user is rate limited
     *
     * @param string $session_id The session ID
     * @return boolean
     */
    private function is_rate_limited($session_id) {
        $rate_limit = get_option('chatbot_rate_limit', 10); // messages per minute
        $rate_key = 'chatbot_rate_' . $session_id;
        $current_count = (int) get_transient($rate_key);
        
        return $current_count >= $rate_limit;
    }

    /**
     * Update rate limit counter
     *
     * @param string $session_id The session ID
     */
    private function update_rate_limit($session_id) {
        $rate_key = 'chatbot_rate_' . $session_id;
        $current_count = (int) get_transient($rate_key);
        
        if ($current_count) {
            set_transient($rate_key, $current_count + 1, 60); // 1 minute expiration
        } else {
            set_transient($rate_key, 1, 60);
        }
    }
}