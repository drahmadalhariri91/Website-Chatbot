document.addEventListener('DOMContentLoaded', function() {
    // Global variables for chart management
    let conversationsChart = null;
    let messagesChart = null;
    let isLoadingAnalytics = false;

    // Tab Switching
    const tabLinks = document.querySelectorAll('.nav-tab');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('nav-tab-active');
            });
            this.classList.add('nav-tab-active');
            
            // Show corresponding content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            document.querySelector(this.getAttribute('href')).style.display = 'block';

            // Save active tab to localStorage
            localStorage.setItem('chatbotActiveTab', this.getAttribute('href'));
        });
    });

    // Restore active tab
    const activeTab = localStorage.getItem('chatbotActiveTab');
    if (activeTab) {
        const tab = document.querySelector(`[href="${activeTab}"]`);
        if (tab) tab.click();
    }

    // API Key Toggle
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const input = document.querySelector('#chatbot_openai_key');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🔒';
        });
    }

    // Analytics Period Change
    const analyticsPeriod = document.querySelector('#analytics-period');
    if (analyticsPeriod) {
        analyticsPeriod.addEventListener('change', function() {
            loadAnalytics(this.value);
        });
    }

    // Chat History View
    const viewButtons = document.querySelectorAll('.view-conversation');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sessionId = this.dataset.session;
            loadConversation(sessionId);
        });
    });

    // Delete Conversation
    const deleteButtons = document.querySelectorAll('.delete-conversation');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this conversation?')) {
                deleteConversation(this.dataset.session);
            }
        });
    });

    // Modal Close
    const closeModals = document.querySelectorAll('.close-modal');
    closeModals.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.chatbot-modal').style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('chatbot-modal')) {
            e.target.style.display = 'none';
        }
    });

    // Initialize Charts
    function initializeCharts() {
        const conversationsChart = document.getElementById('conversations-chart');
        const messagesChart = document.getElementById('messages-chart');
        
        if (conversationsChart && messagesChart) {
            loadAnalytics('30days');
        }
    }

    // Load Analytics with Improved Error Handling
    function loadAnalytics(period) {
        // Prevent multiple simultaneous loads
        if (isLoadingAnalytics) return;
        
        isLoadingAnalytics = true;

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading';
        loadingDiv.textContent = chatbotAdmin.strings.loading;
        
        const chartsContainer = document.querySelector('.analytics-charts');
        if (chartsContainer) {
            // Remove any existing loading or error messages
            const existingLoading = chartsContainer.querySelector('.loading');
            const existingError = chartsContainer.querySelector('.notice-error');
            if (existingLoading) existingLoading.remove();
            if (existingError) existingError.remove();

            chartsContainer.appendChild(loadingDiv);
        }

        // Set a timeout to prevent indefinite loading
        const ajaxTimeout = setTimeout(() => {
            isLoadingAnalytics = false;
            if (loadingDiv) loadingDiv.remove();
            showError('Analytics loading timed out. Please try again.');
            
            // Destroy existing charts
            if (conversationsChart) conversationsChart.destroy();
            if (messagesChart) messagesChart.destroy();
        }, 15000); // 15 second timeout

        jQuery.ajax({
            url: chatbotAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_chat_analytics',
                period: period,
                nonce: chatbotAdmin.nonce
            },
            success: function(response) {
                clearTimeout(ajaxTimeout);

                // Robust error checking
                if (response && response.success && response.data && response.data.length > 0) {
                    updateCharts(response.data);
                    updateStats(response.data);
                } else {
                    console.error('No data or invalid response:', response);
                    showError('No analytics data available');
                    
                    // Destroy existing charts if no data
                    if (conversationsChart) conversationsChart.destroy();
                    if (messagesChart) messagesChart.destroy();
                }
            },
            error: function(xhr, status, error) {
                clearTimeout(ajaxTimeout);
                console.error('Analytics error:', error);
                showError(chatbotAdmin.strings.error);
                
                // Destroy existing charts on error
                if (conversationsChart) conversationsChart.destroy();
                if (messagesChart) messagesChart.destroy();
            },
            complete: function() {
                isLoadingAnalytics = false;
                if (loadingDiv) {
                    loadingDiv.remove();
                }
            }
        });
    }

    // Load Conversation
    async function loadConversation(sessionId) {
        const modal = document.getElementById('conversation-modal');
        const messagesContainer = modal.querySelector('.conversation-messages');
        
        modal.style.display = 'block';
        messagesContainer.innerHTML = '<div class="loading">Loading conversation...</div>';

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'get_chat_history',
                    session_id: sessionId,
                    nonce: chatbotAdmin.nonce
                })
            });

            const data = await response.json();

            if (data.success) {
                displayConversation(data.data, messagesContainer);
            } else {
                messagesContainer.innerHTML = '<div class="error">Failed to load conversation</div>';
            }
        } catch (error) {
            console.error('Conversation error:', error);
            messagesContainer.innerHTML = '<div class="error">Error loading conversation</div>';
        }
    }

    // Delete Conversation
    async function deleteConversation(sessionId) {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'delete_chat_history',
                    session_id: sessionId,
                    nonce: chatbotAdmin.nonce
                })
            });

            const data = await response.json();

            if (data.success) {
                const row = document.querySelector(`[data-session="${sessionId}"]`).closest('tr');
                row.style.animation = 'fadeOut 0.3s';
                setTimeout(() => row.remove(), 300);
            } else {
                showError('Failed to delete conversation');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showError('Error deleting conversation');
        }
    }

    // Update Charts
    function updateCharts(data) {
        const dates = data.map(item => formatDate(item.date));
        const conversations = data.map(item => parseInt(item.conversations));
        const messages = data.map(item => parseInt(item.messages));

        // Update conversations chart
        const conversationsCtx = document.getElementById('conversations-chart');
        if (conversationsCtx) {
            if (conversationsChart) {
                conversationsChart.destroy();
            }

            conversationsChart = new Chart(conversationsCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Conversations',
                        data: conversations,
                        borderColor: chatbotAdmin.colors.primary,
                        backgroundColor: hexToRGBA(chatbotAdmin.colors.primary, 0.1),
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2
                    }]
                },
                options: getChartOptions('Conversations')
            });
        }

        // Update messages chart
        const messagesCtx = document.getElementById('messages-chart');
        if (messagesCtx) {
            if (messagesChart) {
                messagesChart.destroy();
            }

            messagesChart = new Chart(messagesCtx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Messages',
                        data: messages,
                        backgroundColor: chatbotAdmin.colors.primary,
                        borderRadius: 4
                    }]
                },
                options: getChartOptions('Messages')
            });
        }
    }

    // Chart Options
    function getChartOptions(label) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: '#fff',
                    titleColor: '#000',
                    bodyColor: '#666',
                    borderColor: '#e1e4e8',
                    borderWidth: 1,
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 600
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        title: function(tooltipItems) {
                            return formatDate(tooltipItems[0].label, true);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 8
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        };
    }

    // Update Stats
    function updateStats(data) {
        const totals = data.reduce((acc, item) => {
            acc.conversations += parseInt(item.conversations);
            acc.messages += parseInt(item.messages);
            return acc;
        }, { conversations: 0, messages: 0 });

        document.getElementById('total-conversations').textContent = totals.conversations.toLocaleString();
        document.getElementById('total-messages').textContent = totals.messages.toLocaleString();
        
        const avgMessages = totals.conversations ? (totals.messages / totals.conversations).toFixed(1) : '0';
        document.getElementById('avg-messages').textContent = avgMessages;
    }

    // Utility: Format Date
    function formatDate(dateString, includeYear = false) {
        const date = new Date(dateString);
        const options = {
            month: 'short',
            day: 'numeric'
        };
        
        if (includeYear) {
            options.year = 'numeric';
        }
        
        return date.toLocaleDateString('en-US', options);
    }

    // Utility: Hex to RGBA
    function hexToRGBA(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    // Display Conversation
    function displayConversation(messages, container) {
        container.innerHTML = '';

        messages.forEach(message => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${message.sender}-message`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.textContent = message.message;
            
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            timeDiv.textContent = new Date(message.timestamp).toLocaleString();
            
            messageDiv.appendChild(contentDiv);
            messageDiv.appendChild(timeDiv);
            container.appendChild(messageDiv);
        });
    }

    // Show Error Message
    function showError(message) {
        const wrapper = document.querySelector('.wrap');
        if (!wrapper) return;

        // Remove any existing error notices first
        const existingNotices = wrapper.querySelectorAll('.notice-error');
        existingNotices.forEach(notice => notice.remove());

        const notice = document.createElement('div');
        notice.className = 'notice notice-error';
        notice.textContent = message;

        wrapper.insertBefore(notice, wrapper.firstChild);

        setTimeout(() => {
            notice.remove();
        }, 5000);
    }

    // Export functionality
    const exportButton = document.getElementById('export-data');
    if (exportButton) {
        exportButton.addEventListener('click', async function() {
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'export_chat_data',
                        nonce: chatbotAdmin.nonce
                    })
                });

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'chatbot-data.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
            } catch (error) {
                console.error('Export error:', error);
                showError('Error exporting data');
            }
        });
    }

    // Settings validation
    const settingsForm = document.querySelector('form.chatbot-settings');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            const apiKey = document.getElementById('chatbot_openai_key').value.trim();
            if (!apiKey) {
                e.preventDefault();
                showError('OpenAI API key is required');
                return false;
            }

            const maxTokens = parseInt(document.getElementById('chatbot_max_tokens').value);
            if (isNaN(maxTokens) || maxTokens < 100 || maxTokens > 4000) {
                e.preventDefault();
                showError('Max tokens must be between 100 and 4000');
                return false;
            }
        });
    }

    // Initialize tooltips
    const tooltips = document.querySelectorAll('.tooltip');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseover', function() {
            const text = this.getAttribute('data-tooltip');
            const tooltipText = document.createElement('span');
            tooltipText.className = 'tooltip-text';
            tooltipText.textContent = text;
            this.appendChild(tooltipText);
        });

        tooltip.addEventListener('mouseout', function() {
            const tooltipText = this.querySelector('.tooltip-text');
            if (tooltipText) {
                tooltipText.remove();
            }
        });
    });

    // Initialize page-specific functionality
    function initializePage() {
        const currentPage = window.location.href;

        if (currentPage.includes('page=chatbot-analytics')) {
            loadAnalytics('30days');
        }

        // Add any other page-specific initializations here
    }

    // jQuery document ready for additional initializations
    jQuery(document).ready(function($) {
        // Analytics period change handler
        $('#analytics-period').on('change', function() {
            loadAnalytics($(this).val());
        });

        // Initialize charts if we're on the analytics page
        initializeCharts();
        initializePage();
    });
});