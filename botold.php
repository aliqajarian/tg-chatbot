<?php
// Telegram Bot with OpenRouter LLM Integration
// This bot responds to messages where it is mentioned in group chats

// Include configuration file
require_once 'config.php';

// Get the raw POST data from Telegram
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// Check if this is a message update
if (isset($update['message'])) {
    $message = $update['message'];
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
        
        if ($shouldRespond) {
            // Get response from LLM
            $response = getLLMResponse($question, $openRouterApiKey, $llmModel, $maxTokens, $systemInstructions);
            
            // Reply to the message with the LLM response
            sendReply($telegramBotToken, $chatId, $messageId, $response);
        }
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
    
    $options = [
        'http' => [
            'header' => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'HTTP-Referer: ' . ($_SERVER['HTTP_HOST'] ?? 'tg-chatbot'),
                'X-Title: ' . ($_SERVER['HTTP_HOST'] ?? 'tg-chatbot')
            ],
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return "Sorry, I couldn't process your request at the moment.";
    }
    
    $responseData = json_decode($response, true);
    
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    }
    
    return "Sorry, I couldn't generate a response.";
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
    file_get_contents($url, false, $context);
}
?>