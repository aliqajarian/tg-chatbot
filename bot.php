<?php
// Telegram Bot with OpenRouter LLM Integration
// This bot responds to messages where it is mentioned in group chats

// Include configuration file
require_once 'config.php';

// Get the raw POST data from Telegram
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// Handle different types of updates
if (isset($update['message'])) {
    handleMessage($update['message']);
} else if (isset($update['my_chat_member'])) {
    handleChatMemberUpdate($update['my_chat_member']);
}

// Function to handle message updates
function handleMessage($message) {
    global $telegramBotToken, $openRouterApiKey, $llmModel, $maxTokens, $systemInstructions;
    
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $messageId = $message['message_id'];
    
    // Get bot info to know its username/ID
    $botInfo = getBotInfo($telegramBotToken);
    $botUsername = $botInfo['username'] ?? '';
    $botId = $botInfo['id'] ?? '';
    
    // Check if message is in a group chat
    if (isset($message['chat']['type']) && in_array($message['chat']['type'], ['group', 'supergroup'])) {
        // Check if bot should respond:
        // 1. Bot is mentioned in the message, OR
        // 2. This is a reply to one of the bot's previous messages
        $shouldRespond = false;
        $question = '';
        
        // Check if bot is mentioned in the message
        if (isBotMentioned($text, $botUsername, $botId)) {
            // Extract the question
            // If this message is a reply to another message, use the original message as the question
            if (isset($message['reply_to_message']['text'])) {
                $question = $message['reply_to_message']['text'];
            } else {
                // Otherwise, use the current message (remove the bot mention)
                $question = extractQuestion($text, $botUsername);
            }
            $shouldRespond = true;
        }
        // Check if this is a reply to one of the bot's previous messages
        else if (isset($message['reply_to_message']['from']['id']) && $message['reply_to_message']['from']['id'] == $botId) {
            // Use the current message as the question
            $question = $text;
            $shouldRespond = true;
        }
        
        // Check if the group is in the allowed list (if list is not empty)
        $allowedGroups = getAllowedGroups();
        if (empty($allowedGroups) || in_array($chatId, $allowedGroups)) {
            if ($shouldRespond) {
                // Send initial "processing" message
                $processingMessageId = sendReply($telegramBotToken, $chatId, $messageId, "🧠 Processing your request...");
                
                // Get response from LLM
                $response = getLLMResponse($question, $openRouterApiKey, $llmModel, $maxTokens, $systemInstructions);
                
                // Edit the processing message with the LLM response
                if ($processingMessageId) {
                    editMessage($telegramBotToken, $chatId, $processingMessageId, $response);
                } else {
                    // Fallback to sending a new message if editing fails
                    sendReply($telegramBotToken, $chatId, $messageId, $response);
                }
            }
        }
    }
}

// Function to handle chat member updates (when bot is added/removed from chats)
function handleChatMemberUpdate($chatMemberUpdate) {
    $chatId = $chatMemberUpdate['chat']['id'];
    $newStatus = $chatMemberUpdate['new_chat_member']['status'];
    
    // Check if this is a group chat
    if (in_array($chatMemberUpdate['chat']['type'], ['group', 'supergroup'])) {
        // Check if bot was added to the chat
        if ($newStatus == 'member' || $newStatus == 'administrator') {
            // Store the group ID
            addAllowedGroup($chatId);
        }
    }
}

// Function to get allowed groups from file
function getAllowedGroups() {
    $groupsFile = 'allowed_groups.txt';
    if (!file_exists($groupsFile)) {
        return [];
    }
    
    $groups = file($groupsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_map('intval', $groups);
}

// Function to add a group to the allowed list
function addAllowedGroup($chatId) {
    $groupsFile = 'allowed_groups.txt';
    $groups = getAllowedGroups();
    
    // Check if group is already in the list
    if (!in_array($chatId, $groups)) {
        // Add the group ID to the file
        file_put_contents($groupsFile, $chatId . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// Function to get bot information
function getBotInfo($token) {
    $url = "https://api.telegram.org/bot{$token}/getMe";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    return $data['result'] ?? [];
}

// Function to check if bot is mentioned in the message
function isBotMentioned($text, $username, $id) {
    if (empty($text)) return false;
    
    // Check for username mention (@username)
    if (!empty($username) && strpos($text, "@{$username}") !== false) {
        return true;
    }
    
    // Check for ID mention (less common but possible)
    if (!empty($id) && strpos($text, "@{$id}") !== false) {
        return true;
    }
    
    return false;
}

// Function to extract question from message (remove bot mention)
function extractQuestion($text, $username) {
    if (empty($username)) return $text;
    
    // Remove the bot mention from the text
    $question = str_replace("@{$username}", "", $text);
    return trim($question);
}

// Function to get response from OpenRouter LLM
function getLLMResponse($prompt, $apiKey, $model, $maxTokens, $systemInstructions = "") {
    $url = 'https://openrouter.ai/api/v1/chat/completions';
    
    // Validate inputs
    if (empty($apiKey) || $apiKey === 'YOUR_OPENROUTER_API_KEY') {
        return "⚠️ OpenRouter API key is not configured. Please check your config.php file.";
    }
    
    if (empty($model)) {
        return "⚠️ LLM model is not configured.";
    }
    
    if (empty($prompt)) {
        return "⚠️ No question provided.";
    }
    
    // Prepare messages array
    $messages = [];
    
    // Add system instructions if provided
    if (!empty($systemInstructions)) {
        $messages[] = ['role' => 'system', 'content' => $systemInstructions];
    }
    
    // Add user prompt
    $messages[] = ['role' => 'user', 'content' => $prompt];
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => $maxTokens
    ];
    
    // Create context with error handling
    $options = [
        'http' => [
            'header' => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'HTTP-Referer: ' . ($_SERVER['HTTP_HOST'] ?? 'tg-chatbot'),
                'X-Title: ' . ($_SERVER['HTTP_HOST'] ?? 'tg-chatbot')
            ],
            'method' => 'POST',
            'content' => json_encode($data),
            'timeout' => 30  // 30 second timeout
        ]
    ];
    
    // Create context
    $context = stream_context_create($options);
    
    // Capture error information
    $response = @file_get_contents($url, false, $context);
    
    // Check for HTTP errors
    if ($response === false) {
        $error = error_get_last();
        if ($error) {
            return "❌ API Connection Error: " . $error['message'];
        } else {
            return "❌ Failed to connect to OpenRouter API. Check your internet connection and firewall settings.";
        }
    }
    
    // Check HTTP response code
    $http_response_header_array = $http_response_header ?? [];
    if (!empty($http_response_header_array)) {
        $status_line = $http_response_header_array[0];
        preg_match('/^HTTP\/\d\.\d (\d+)/', $status_line, $matches);
        $http_code = isset($matches[1]) ? (int)$matches[1] : 0;
        
        if ($http_code >= 400) {
            return "❌ API Error (HTTP $http_code): " . substr($response, 0, 200) . "...";
        }
    }
    
    // Parse JSON response
    $responseData = json_decode($response, true);
    
    // Check for JSON parsing errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        return "❌ JSON Parse Error: " . json_last_error_msg() . " - Response: " . substr($response, 0, 200) . "...";
    }
    
    // Check for API errors in response
    if (isset($responseData['error'])) {
        $errorMsg = $responseData['error']['message'] ?? 'Unknown API error';
        $errorCode = $responseData['error']['code'] ?? 'Unknown';
        return "❌ OpenRouter API Error (Code: $errorCode): $errorMsg";
    }
    
    // Check for successful response
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    }
    
    // Unexpected response format
    return "❌ Unexpected API Response Format: " . substr(json_encode($responseData), 0, 200) . "...";
}

// Function to send a reply to a message
function sendReply($token, $chatId, $messageId, $text) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'reply_to_message_id' => $messageId
    ];
    
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    // Parse response to get message ID
    if ($response !== false) {
        $responseData = json_decode($response, true);
        if (isset($responseData['result']['message_id'])) {
            return $responseData['result']['message_id'];
        }
    }
    
    return false;
}

// Function to edit a message
function editMessage($token, $chatId, $messageId, $text) {
    $url = "https://api.telegram.org/bot{$token}/editMessageText";
    
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text
    ];
    
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

?>
