<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeepLit AI | Knobly Cream: Create &bull; Reach &bull; Measure</title>

    <!-- Core dependencies -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css">
    <link rel="icon" type="image/x-icon" href="/img/logo.ico">

    <style>
        :root {
            --primary-color: #10a37f; /* ChatGPT green */
            --primary-hover: #0d8a6c;
            --secondary-color: #333;
            --light-gray: #f7f7f8;
            --darker-gray: #ececf1;
            --border-color: #e5e5e5;
            --shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            --border-radius: 8px;
            --font-main: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            font-family: var(--font-main);
            background-color: #fff;
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
        }

        /* Main layout */
        .chat-container {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: #202123;
            color: white;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #444;
        }

        .new-chat-btn {
            display: flex;
            align-items: center;
            background-color: #343541;
            border: 1px solid #565869;
            border-radius: 6px;
            padding: 10px 15px;
            color: white;
            cursor: pointer;
            margin: 15px;
            width: calc(100% - 30px);
            transition: all 0.2s ease;
        }

        .new-chat-btn:hover {
            background-color: #444654;
        }

        .new-chat-btn i {
            margin-right: 10px;
        }

        .chat-history {
            padding: 10px 15px;
            flex-grow: 1;
            overflow-y: auto;
        }

        .chat-item {
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            cursor: pointer;
            color: #ececf1;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.2s ease;
        }

        .chat-item:hover {
            background-color: #343541;
        }

        .chat-item.active {
            background-color: #343541;
        }

        .chat-item i {
            margin-right: 8px;
            color: #8e8ea0;
        }

        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid #444;
        }

        .user-info {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
        }

        .user-info:hover {
            background-color: #343541;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #10a37f;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        /* Main chat area */
        .chat-main {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            background-color: #fff;
            position: relative;
        }

        .chat-messages {
            flex-grow: 1;
            padding: 0;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        .message-wrapper {
            border-bottom: 1px solid var(--border-color);
        }

        .message {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
            display: flex;
        }

        .message.user {
            background-color: #f7f7f8;
        }

        .message.ai {
            background-color: #fff;
        }

        .message-icon {
            width: 30px;
            height: 30px;
            margin-right: 16px;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user .message-icon {
            background-color: #5436da;
            color: white;
        }

        .ai .message-icon {
            background-color: #10a37f;
            color: white;
        }

        .message-content {
            flex-grow: 1;
            line-height: 1.6;
            overflow-wrap: break-word;
        }

        .message-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
            margin-left: 10px;
        }

        .message:hover .message-actions {
            opacity: 1;
        }

        .action-button {
            background: none;
            border: none;
            color: #8e8ea0;
            cursor: pointer;
            padding: 5px;
            margin-left: 5px;
            border-radius: 4px;
        }

        .action-button:hover {
            background-color: var(--darker-gray);
            color: var(--secondary-color);
        }

        /* Input area */
        .chat-input-container {
            border-top: 1px solid var(--border-color);
            padding: 16px;
            position: sticky;
            bottom: 0;
            background-color: #fff;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        .chat-input-wrapper {
            position: relative;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background-color: #fff;
            box-shadow: var(--shadow);
            padding: 10px;
        }

        .chat-input {
            width: calc(100% - 40px);
            resize: none;
            border: none;
            outline: none;
            padding: 10px;
            max-height: 200px;
            line-height: 1.5;
            font-size: 16px;
        }

        .send-button {
            position: absolute;
            right: 10px;
            bottom: 10px;
            background-color: #10a37f;
            color: white;
            border: none;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .send-button:hover {
            background-color: var(--primary-hover);
        }

        .send-button:disabled {
            background-color: #d9d9e3;
            cursor: not-allowed;
        }

        .input-tools {
            border-top: 1px solid var(--border-color);
            padding-top: 10px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #8e8ea0;
        }

        .model-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .selector-button {
            background: none;
            border: none;
            color: #8e8ea0;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .selector-button:hover {
            background-color: var(--darker-gray);
        }

        .examples-container {
            margin-top: 20vh;
            text-align: center;
            padding: 20px;
        }

        .welcome-text {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--secondary-color);
        }

        .examples-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .example-card {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
        }

        .example-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .example-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary-color);
        }

        .example-desc {
            color: #6e6e80;
            font-size: 0.9rem;
        }

        /* Capabilities section */
        .capabilities {
            margin-top: 40px;
            text-align: center;
        }

        .capabilities-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--secondary-color);
        }

        .capabilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            max-width: 800px;
            margin: 0 auto;
        }

        .capability-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .capability-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .capability-text {
            font-size: 0.9rem;
            color: #6e6e80;
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                left: -260px;
                z-index: 1000;
            }

            .sidebar.open {
                left: 0;
            }

            .toggle-sidebar {
                display: block;
                position: absolute;
                top: 10px;
                left: 10px;
                z-index: 1001;
                background: none;
                border: none;
                color: var(--secondary-color);
                font-size: 1.2rem;
            }

            .chat-main {
                padding-top: 50px;
            }

            .message {
                padding: 16px;
            }

            .examples-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Additional styling for code blocks and markdown */
        pre {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            overflow-x: auto;
            margin: 15px 0;
        }

        code {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        blockquote {
            border-left: 3px solid #10a37f;
            padding-left: 15px;
            color: #6e6e80;
            margin: 15px 0;
        }

        .loader {
            width: 18px;
            height: 18px;
            border: 2px solid #8e8ea0;
            border-bottom-color: transparent;
            border-radius: 50%;
            display: inline-block;
            animation: rotation 1s linear infinite;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .mobile-only {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4>DeepLit AI</h4>
            </div>
            <button class="new-chat-btn">
                <i class="fas fa-plus"></i> New chat
            </button>
            <div class="chat-history">
                <div class="chat-item active">
                    <i class="fas fa-comment"></i> Welcome to DeepLit AI
                </div>
                <div class="chat-item">
                    <i class="fas fa-comment"></i> Marketing strategies
                </div>
                <div class="chat-item">
                    <i class="fas fa-comment"></i> Code optimization help
                </div>
                <div class="chat-item">
                    <i class="fas fa-comment"></i> Content calendar ideas
                </div>
            </div>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">U</div>
                    <div>User Account</div>
                </div>
            </div>
        </div>

        <!-- Main chat area -->
        <div class="chat-main">
            <button class="toggle-sidebar mobile-only">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Messages container -->
            <div class="chat-messages" id="chat-messages">
                <!-- Empty state with examples -->
                <div class="examples-container" id="empty-state">
                    <div class="welcome-text">How can I help you today?</div>
                    
                    <div class="examples-grid">
                        <div class="example-card" data-prompt="Suggest social media marketing strategies for small businesses">
                            <div class="example-title">Social media strategy</div>
                            <div class="example-desc">Suggest effective marketing tactics for small businesses</div>
                        </div>
                        <div class="example-card" data-prompt="Create a weekly content calendar for a fitness blog">
                            <div class="example-title">Content calendar</div>
                            <div class="example-desc">Generate a weekly publishing schedule for fitness content</div>
                        </div>
                        <div class="example-card" data-prompt="Help me optimize this JavaScript code for performance">
                            <div class="example-title">Code optimization</div>
                            <div class="example-desc">Improve performance of JavaScript applications</div>
                        </div>
                        <div class="example-card" data-prompt="Write a professional email requesting a project deadline extension">
                            <div class="example-title">Email drafting</div>
                            <div class="example-desc">Create a professional email for deadline extensions</div>
                        </div>
                    </div>

                    <div class="capabilities">
                        <div class="capabilities-title">Capabilities</div>
                        <div class="capabilities-grid">
                            <div class="capability-item">
                                <div class="capability-icon">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <div class="capability-text">Generate creative content and ideas</div>
                            </div>
                            <div class="capability-item">
                                <div class="capability-icon">
                                    <i class="fas fa-code"></i>
                                </div>
                                <div class="capability-text">Write and explain code in various languages</div>
                            </div>
                            <div class="capability-item">
                                <div class="capability-icon">
                                    <i class="fas fa-table"></i>
                                </div>
                                <div class="capability-text">Structure and format data in tables</div>
                            </div>
                            <div class="capability-item">
                                <div class="capability-icon">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <div class="capability-text">Perform calculations and data analysis</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages will be appended here dynamically -->
            </div>

            <!-- Input area -->
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <textarea class="chat-input" id="chat-input" placeholder="Message DeepLit AI..." rows="1"></textarea>
                    <button class="send-button" id="send-button" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="input-tools">
                    <div class="model-selector">
                        <button class="selector-button" id="model-selector">
                            <i class="fas fa-robot"></i> DeepLit AI
                        </button>
                    </div>
                    <div>
                        DeepLit may produce inaccurate information about people, places, or facts.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const chatInput = document.getElementById('chat-input');
            const sendButton = document.getElementById('send-button');
            const chatMessages = document.getElementById('chat-messages');
            const emptyState = document.getElementById('empty-state');
            
            // Auto-resize textarea
            chatInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
                
                // Enable/disable send button based on input
                sendButton.disabled = this.value.trim() === '';
            });

            // Focus on input when page loads
            chatInput.focus();

            // Mobile sidebar toggle
            $('.toggle-sidebar').click(function() {
                $('.sidebar').toggleClass('open');
            });

            // Add event listener to example cards
            $('.example-card').click(function() {
                const prompt = $(this).data('prompt');
                chatInput.value = prompt;
                chatInput.style.height = 'auto';
                chatInput.style.height = Math.min(chatInput.scrollHeight, 200) + 'px';
                sendButton.disabled = false;
                chatInput.focus();
            });

            // Send message function
            function sendMessage() {
                const message = chatInput.value.trim();
                if (!message) return;

                // Hide empty state
                emptyState.style.display = 'none';

                // Add user message
                appendMessage('user', message);

                // Clear input
                chatInput.value = '';
                chatInput.style.height = 'auto';
                sendButton.disabled = true;
                
                // Simulate typing for AI response
                simulateTyping();
            }

            // Send button click event
            sendButton.addEventListener('click', sendMessage);

            // Enter key press in textarea
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!sendButton.disabled) {
                        sendMessage();
                    }
                }
            });

            // Add a message to the chat
            function appendMessage(sender, content) {
                const messageWrapper = document.createElement('div');
                messageWrapper.className = 'message-wrapper';
                
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}`;
                
                const iconDiv = document.createElement('div');
                iconDiv.className = 'message-icon';
                
                // Different icons for user and AI
                if (sender === 'user') {
                    iconDiv.innerHTML = '<i class="fas fa-user"></i>';
                } else {
                    iconDiv.innerHTML = '<i class="fas fa-robot"></i>';
                }
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.textContent = content;
                
                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'message-actions';
                
                if (sender === 'ai') {
                    const copyButton = document.createElement('button');
                    copyButton.className = 'action-button';
                    copyButton.innerHTML = '<i class="far fa-copy"></i>';
                    copyButton.addEventListener('click', function() {
                        navigator.clipboard.writeText(content)
                            .then(() => {
                                // Show copied feedback
                                copyButton.innerHTML = '<i class="fas fa-check"></i>';
                                setTimeout(() => {
                                    copyButton.innerHTML = '<i class="far fa-copy"></i>';
                                }, 2000);
                            });
                    });
                    
                    actionsDiv.appendChild(copyButton);
                }
                
                messageDiv.appendChild(iconDiv);
                messageDiv.appendChild(contentDiv);
                messageDiv.appendChild(actionsDiv);
                messageWrapper.appendChild(messageDiv);
                
                chatMessages.appendChild(messageWrapper);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            // Simulate AI typing response
            function simulateTyping() {
                // Create loading message
                const loadingWrapper = document.createElement('div');
                loadingWrapper.className = 'message-wrapper';
                loadingWrapper.id = 'loading-message';
                
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'message ai';
                
                const iconDiv = document.createElement('div');
                iconDiv.className = 'message-icon';
                iconDiv.innerHTML = '<i class="fas fa-robot"></i>';
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.innerHTML = '<span class="loader"></span>';
                
                loadingDiv.appendChild(iconDiv);
                loadingDiv.appendChild(contentDiv);
                loadingWrapper.appendChild(loadingDiv);
                
                chatMessages.appendChild(loadingWrapper);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Simulate API call delay
                setTimeout(() => {
                    // Remove loading message
                    document.getElementById('loading-message').remove();
                    
                    // Sample responses based on input
                    const responses = [
                        "I'd be happy to help you with that. Here's what I can offer based on your request...",
                        "Great question! Let me provide some insights on this topic...",
                        "I've analyzed your question and here are my thoughts...",
                        "Based on the latest information available, here's what I can tell you...",
                        "That's an interesting query. Here's what I know about this subject..."
                    ];
                    
                    const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                    appendMessage('ai', randomResponse);
                }, 1500);
            }

            // New chat button
            $('.new-chat-btn').click(function() {
                // Clear chat
                while (chatMessages.firstChild) {
                    chatMessages.removeChild(chatMessages.firstChild);
                }
                
                // Show empty state
                emptyState.style.display = 'block';
                
                // Focus on input
                chatInput.focus();
                
                // On mobile, close sidebar
                if (window.innerWidth <= 768) {
                    $('.sidebar').removeClass('open');
                }
            });

            // Handle chat history clicks
            $('.chat-item').click(function() {
                $('.chat-item').removeClass('active');
                $(this).addClass('active');
                
                // On mobile, close sidebar
                if (window.innerWidth <= 768) {
                    $('.sidebar').removeClass('open');
                }
            });
        });
    </script>
</body>

</html>