document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const chatbot = document.getElementById('wp-chatbot');
    const toggleButton = document.getElementById('chat-toggle-btn');
    const chatMessages = document.getElementById('chatMessages');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');
    const closeButton = document.querySelector('.chat-close');
    const toggleFullscreen = document.querySelector('.toggle-fullscreen');
    const menuToggle = document.querySelector('.menu-toggle');
    const menuDropdown = document.querySelector('.menu-dropdown');
    const clearChatButton = document.querySelector('.menu-clear');
    
    // Feedback Modal Elements
    const feedbackButton = document.querySelector('.menu-feedback'); // Add this line
    const feedbackModal = document.getElementById('feedback-modal');
    const feedbackCloseButton = document.querySelector('.feedback-close');
    const submitFeedbackButton = document.querySelector('.submit-feedback');
    const feedbackTextArea = document.getElementById('feedback-text');



    // Session Management
    let sessionId = getCookie('chatbot_session');
    if (!sessionId) {
        sessionId = generateUUID();
        setCookie('chatbot_session', sessionId, 30); // 30 days
    }

    // Event Listeners
    toggleButton.addEventListener('click', toggleChat);
    closeButton.addEventListener('click', closeChat);
    sendButton.addEventListener('click', sendMessage);
    userInput.addEventListener('keydown', handleInput);
    toggleFullscreen.addEventListener('click', toggleFullscreenMode);
    menuToggle.addEventListener('click', toggleMenu);
    clearChatButton?.addEventListener('click', clearChat);
 



    if (feedbackButton) {
        feedbackButton.addEventListener('click', showFeedbackModal);
    }

    if (feedbackCloseButton) {
        feedbackCloseButton.addEventListener('click', closeFeedbackModal);
    }

    if (submitFeedbackButton) {
        submitFeedbackButton.addEventListener('click', submitFeedback);
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.chat-menu')) {
            menuDropdown.style.display = 'none';
        }
    });

    // Handle textarea auto-resize
    userInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight < 100) ? this.scrollHeight + 'px' : '100px';
    });

function restoreChatState() {
    // Check if chat was previously open
    const wasChatOpen = localStorage.getItem('chatbot_open') === 'true';
    
    // Retrieve stored session ID
    const storedSessionId = localStorage.getItem('chatbot_session') || getCookie('chatbot_session');
    
    if (storedSessionId) {
        sessionId = storedSessionId;
        setCookie('chatbot_session', sessionId, 30);
    }

    if (wasChatOpen) {
        chatbot.style.display = 'block';
        toggleButton.style.display = 'none';
        
        // Always try to load chat history when restoring state
        loadChatHistory();
    } else {
        // If chat was not open, still try to load history if session exists
        if (storedSessionId) {
            addMessage(chatbotSettings.strings.welcome, 'bot');
        }
    }
}
    // Core Functions
    function toggleChat() {
        const isVisible = chatbot.style.display === 'block';
        chatbot.style.display = isVisible ? 'none' : 'block';
        toggleButton.style.display = isVisible ? 'block' : 'none';

        // Store chat state in localStorage
        localStorage.setItem('chatbot_open', !isVisible);

        if (!isVisible) {
            userInput.focus();
            loadChatHistory();
        }
    }

    function closeChat() {
        chatbot.style.display = 'none';
        toggleButton.style.display = 'block';
        
        // Clear open state in localStorage
        localStorage.removeItem('chatbot_open');
    }

    async function sendMessage() {
        const message = userInput.value.trim();
        if (!message) return;

        // Disable input
        userInput.disabled = true;
        sendButton.disabled = true;

        // Add user message
        addMessage(message, 'user');

        // Show typing indicator
        const typingIndicator = addTypingIndicator();

        try {
            const response = await fetch(chatbotSettings.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'chatbot_message',
                    nonce: chatbotSettings.nonce,
                    question: message,
                    session_id: sessionId
                })
            });

            const data = await response.json();

            // Remove typing indicator
            typingIndicator.remove();

            if (data.success) {
                addMessage(data.data.answer, 'bot');
                sessionId = data.data.session_id;
                setCookie('chatbot_session', sessionId, 30);
            } else {
                addMessage(chatbotSettings.strings.error, 'bot');
            }
        } catch (error) {
            console.error('Chat error:', error);
            typingIndicator.remove();
            addMessage(chatbotSettings.strings.error, 'bot');
        }

        // Reset and re-enable input
        userInput.value = '';
        userInput.style.height = 'auto';
        userInput.disabled = false;
        sendButton.disabled = false;
        userInput.focus();
    }

    function handleInput(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function addMessage(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        // Handle URLs and make them clickable
        const urlRegex = /(https?:\/\/[^\s<>"']+)/g;
        contentDiv.innerHTML = text.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date().toLocaleTimeString();
        
        messageDiv.appendChild(contentDiv);
        messageDiv.appendChild(timeDiv);
        chatMessages.appendChild(messageDiv);
        
        scrollToBottom();
    }

    function addTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        for (let i = 0; i < 3; i++) {
            const dot = document.createElement('div');
            dot.className = 'typing-dot';
            indicator.appendChild(dot);
        }
        chatMessages.appendChild(indicator);
        scrollToBottom();
        return indicator;
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function toggleFullscreenMode() {
        chatbot.classList.toggle('fullscreen');
        const isFullscreen = chatbot.classList.contains('fullscreen');
        document.querySelector('.expand-icon').style.display = isFullscreen ? 'none' : 'block';
        document.querySelector('.minimize-icon').style.display = isFullscreen ? 'block' : 'none';
    }

    function toggleMenu(e) {
        e.stopPropagation();
        const isVisible = menuDropdown.style.display === 'block';
        menuDropdown.style.display = isVisible ? 'none' : 'block';
    }

async function loadChatHistory() {
    try {
        const response = await fetch(chatbotSettings.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_chat_messages',
                session_id: sessionId,
                nonce: chatbotSettings.nonce
            })
        });

        const data = await response.json();

        // Clear existing messages
        chatMessages.innerHTML = '';

        if (data.success && data.data && data.data.length > 0) {
            // Load existing messages
            data.data.forEach(message => {
                addMessage(message.message, message.sender);
            });
        } else {
            // Show welcome message if no history
            addMessage(chatbotSettings.strings.welcome, 'bot');
        }
    } catch (error) {
        console.error('Error loading chat history:', error);
        // Show welcome message on error
        addMessage(chatbotSettings.strings.welcome, 'bot');
    }
}

    function clearChat() {
        if (confirm(chatbotSettings.strings.confirmClear)) {
            chatMessages.innerHTML = '';
            addMessage(chatbotSettings.strings.welcome, 'bot');
            
            // Generate new session ID
            sessionId = generateUUID();
            setCookie('chatbot_session', sessionId, 30);
            menuDropdown.style.display = 'none';
        }
    }

function showFeedbackModal() {
    if (feedbackModal) {
        feedbackModal.style.display = 'block';
        menuDropdown.style.display = 'none';
        feedbackTextArea.focus();
    }
}

function closeFeedbackModal() {
    if (feedbackModal) {
        feedbackModal.style.display = 'none';
        feedbackTextArea.value = ''; // Clear textarea
    }
}

async function submitFeedback() {
    const feedbackText = feedbackTextArea.value.trim();
    
    if (!feedbackText) {
        alert('Please enter your feedback');
        return;
    }

    try {
        const response = await fetch(chatbotSettings.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'submit_chatbot_feedback',
                nonce: chatbotSettings.nonce,
                feedback: feedbackText,
                session_id: sessionId
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(chatbotSettings.strings.feedbackSuccess || 'Feedback submitted successfully');
            closeFeedbackModal();
        } else {
            alert(data.data || 'Failed to submit feedback');
        }
    } catch (error) {
        console.error('Feedback submission error:', error);
        alert('An error occurred while submitting feedback');
    }
}

    // Utility Functions
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/;SameSite=Strict`;
    }

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    // Handle window focus
    window.addEventListener('focus', loadChatHistory);

    // Handle network status
    window.addEventListener('online', () => {
        addMessage(chatbotSettings.strings.online, 'bot');
    });

    window.addEventListener('offline', () => {
        addMessage(chatbotSettings.strings.offline, 'bot');
    });

    // Initial restoration of chat state
    restoreChatState();
});