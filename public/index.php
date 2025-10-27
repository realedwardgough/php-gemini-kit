<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Gemini Assistant</title>

    <style>
        html {
            height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto !important;
            padding: 20px;
            height: -webkit-fill-available;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #101316;
            outline: 1px solid #212e3b;
            border-radius: 8px;
        }

        #messages {
            overflow-y: auto;
            padding: 10px;
            margin-bottom: 10px;
        }

        .message {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 4px;
            max-width: 60%;
            word-wrap: break-word;
        }

        input {
            width: calc(100% - 110px) !important;
            padding: 10px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #80808033;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
            border: 1px solid #80808033;
        }

        hr {
            border: none;
            border-top: 1px solid #212e3b;
            margin: 10px 0;
            width: 100%;
        }

        #status {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: .5rem;
            display: inline-block;
        }

        .connected .status-indicator {
            background: #28a745;
            animation: pulseGreen 2s infinite;
        }

        .disconnected .status-indicator {
            background: #dc3545;
            animation: pulseRed 2s infinite;
        }

        @keyframes pulseGreen {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        @keyframes pulseRed {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }

        .user {
            background: #d1ecf1;
            color: #0c5460;
            margin-left: auto;
        }

        .bot {
            background: #202632;
        }

        /* Markdown formatting styles */
        .message h1 {
            font-size: 1.5em;
            margin: 10px 0 5px 0;
        }

        .message h2 {
            font-size: 1.3em;
            margin: 10px 0 5px 0;
        }

        .message h3 {
            font-size: 1.1em;
            margin: 10px 0 5px 0;
        }

        .message p {
            margin: 5px 0;
            line-height: 1.5;
            color: white;
        }

        .message ul,
        .message ol {
            margin: 5px 0;
            padding-left: 25px;
        }

        .message li {
            margin: 3px 0;
        }

        .message code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .message pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 5px 0;
        }

        .message pre code {
            background: none;
            padding: 0;
        }

        .message strong {
            font-weight: bold;
        }

        .message em {
            font-style: italic;
        }

        .message blockquote {
            border-left: 3px solid #666;
            padding-left: 10px;
            margin: 5px 0;
            color: #555;
        }

        .message a {
            color: #0066cc;
            text-decoration: none;
        }

        .message a:hover {
            text-decoration: underline;
        }

        #input-container {
            display: flex;
            flex-direction: row;
            gap: 10px;
        }

        .typing-dots {
            display: inline-block;
            margin-left: 5px;
        }

        .typing-dots span {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #fff;
            margin: 0 1px;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }

        .typing-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes typingAnimation {
            0%, 80%, 100% {
                opacity: 0.3;
                transform: scale(0.8);
            }
            40% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>

<body>
    <div id="status" class="disconnected">
        <div style="background: rgb(19, 22.5, 30.5); padding: .2rem 0.7rem; border-radius: 999rem;">
            <span class="status-indicator"></span>
            <span id="status-text">Trying to connect...</span>
        </div>
        <hr>
    </div>
    <div id="messages"></div>

    <div class="input-container">
        <input type="text" id="messageInput" placeholder="Type a message...">
        <button onclick="sendMessage()">Send</button>
    </div>


    <script>
        const ws = new WebSocket('ws://localhost.co.uk:8080');
        const messagesDiv = document.getElementById('messages');
        const statusDiv = document.getElementById('status');
        const statusText = document.getElementById('status-text');
        const input = document.getElementById('messageInput');

        ws.onopen = () => {
            statusText.textContent = 'Assistant is waiting to assist you!';
            statusDiv.className = 'connected';
            console.log('Connected to WebSocket');
        };

        ws.onmessage = (event) => {
            
            // Remove typing indicator if it exists
            hideTypingIndicator();
            
            const msgDiv = document.createElement('div');
            msgDiv.className = 'message bot';
            msgDiv.innerHTML = formatMarkdown(event.data);
            messagesDiv.appendChild(msgDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        };

        ws.onclose = () => {
            statusText.textContent = 'Disconnected';
            statusDiv.className = 'disconnected';
            console.log('Disconnected');
        };

        ws.onerror = (error) => {
            console.error('WebSocket error:', error);
        };

        function formatMarkdown(text) {
            let html = text;

            // Escape HTML first to prevent XSS
            html = html.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Code blocks (must come before inline code)
            html = html.replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');

            // Inline code
            html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Bold
            html = html.replace(/\*\*([^\*]+)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/__([^_]+)__/g, '<strong>$1</strong>');

            // Italic
            html = html.replace(/\*([^\*]+)\*/g, '<em>$1</em>');
            html = html.replace(/_([^_]+)_/g, '<em>$1</em>');

            // Headers
            html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
            html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
            html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');

            // Links
            html = html.replace(/\[([^\]]+)\]\(([^\)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

            // Unordered lists
            html = html.replace(/^\* (.+)$/gm, '<li>$1</li>');
            html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
            html = html.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');

            // Ordered lists
            html = html.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');

            // Blockquotes
            html = html.replace(/^&gt; (.+)$/gm, '<blockquote>$1</blockquote>');

            // Line breaks to paragraphs
            html = html.split('\n\n').map(para => {
                if (para.trim() && !para.match(/^<(h[1-3]|ul|ol|pre|blockquote)/)) {
                    return '<p>' + para.trim() + '</p>';
                }
                return para;
            }).join('');

            // Single line breaks
            html = html.replace(/\n/g, '<br>');

            return html;
        }

        function showTypingIndicator() {
            hideTypingIndicator();
            
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot typing';
            typingDiv.id = 'typing';
            typingDiv.innerHTML = 'Assistant is typing<span class="typing-dots"><span></span><span></span><span></span></span>';

            messagesDiv.appendChild(typingDiv);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function hideTypingIndicator() {
            const existingIndicator = document.getElementById('typing');
            if (existingIndicator) {
                existingIndicator.remove();
            }
        }

        function sendMessage() {
            const message = input.value;
            if (message && ws.readyState === WebSocket.OPEN) {
                const msgDiv = document.createElement('div');
                
                msgDiv.className = 'message user';
                msgDiv.textContent = message;
                messagesDiv.appendChild(msgDiv);
                messagesDiv.scrollTop = messagesDiv.scrollHeight;

                showTypingIndicator();

                ws.send(message);
                input.value = '';
            }
        }

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

    </script>
</body>

</html>