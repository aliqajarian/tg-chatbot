<?php
// Debugging script for Telegram LLM Bot
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Telegram Bot Debugging</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin-bottom: 30px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background-color: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <h1>Telegram LLM Bot Debugging</h1>";

// Check 1: Configuration file
echo "<div class='section'>
    <h2>1. Configuration Check</h2>";

if (isset($telegramBotToken) && $telegramBotToken !== 'YOUR_TELEGRAM_BOT_TOKEN' && !empty($telegramBotToken)) {
    echo "<p class='success'>✓ Telegram Bot Token is set</p>";
} else {
    echo "<p class='error'>✗ Telegram Bot Token is not set or is still the default value</p>";
}

if (isset($openRouterApiKey) && $openRouterApiKey !== 'YOUR_OPENROUTER_API_KEY' && !empty($openRouterApiKey)) {
    echo "<p class='success'>✓ OpenRouter API Key is set</p>";
} else {
    echo "<p class='error'>✗ OpenRouter API Key is not set or is still the default value</p>";
}

echo "</div>";

// Check 2: Telegram Bot Token Validity
echo "<div class='section'>
    <h2>2. Telegram Bot Token Validation</h2>";

if (isset($telegramBotToken) && $telegramBotToken !== 'YOUR_TELEGRAM_BOT_TOKEN' && !empty($telegramBotToken)) {
    $telegramUrl = "https://api.telegram.org/bot{$telegramBotToken}/getMe";
    $telegramResponse = @file_get_contents($telegramUrl);
    
    if ($telegramResponse !== false) {
        $telegramData = json_decode($telegramResponse, true);
        if ($telegramData['ok'] === true) {
            echo "<p class='success'>✓ Telegram Bot Token is valid</p>";
            echo "<p class='info'>Bot Username: @" . $telegramData['result']['username'] . "</p>";
            echo "<p class='info'>Bot ID: " . $telegramData['result']['id'] . "</p>";
        } else {
            echo "<p class='error'>✗ Telegram Bot Token is invalid: " . $telegramData['description'] . "</p>";
        }
    } else {
        echo "<p class='error'>✗ Failed to connect to Telegram API. Check your internet connection and firewall settings.</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Skipping Telegram validation because token is not set</p>";
}

echo "</div>";

// Check 3: OpenRouter API Key Validity
echo "<div class='section'>
    <h2>3. OpenRouter API Key Validation</h2>";

if (isset($openRouterApiKey) && $openRouterApiKey !== 'YOUR_OPENROUTER_API_KEY' && !empty($openRouterApiKey)) {
    $openRouterUrl = 'https://openrouter.ai/api/v1/models';
    
    $options = [
        'http' => [
            'header' => [
                'Authorization: Bearer ' . $openRouterApiKey,
                'Content-Type: application/json'
            ],
            'method' => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $openRouterResponse = @file_get_contents($openRouterUrl, false, $context);
    
    if ($openRouterResponse !== false) {
        $openRouterData = json_decode($openRouterResponse, true);
        if (isset($openRouterData['data']) && is_array($openRouterData['data'])) {
            echo "<p class='success'>✓ OpenRouter API Key is valid</p>";
            echo "<p class='info'>Available models: " . count($openRouterData['data']) . "</p>";
        } else {
            echo "<p class='error'>✗ OpenRouter API Key is invalid or unexpected response</p>";
            echo "<pre>" . htmlspecialchars($openRouterResponse) . "</pre>";
        }
    } else {
        echo "<p class='error'>✗ Failed to connect to OpenRouter API. Check your internet connection and firewall settings.</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Skipping OpenRouter validation because API key is not set</p>";
}

echo "</div>";

// Check 4: Webhook Status
echo "<div class='section'>
    <h2>4. Telegram Webhook Status</h2>";

if (isset($telegramBotToken) && $telegramBotToken !== 'YOUR_TELEGRAM_BOT_TOKEN' && !empty($telegramBotToken)) {
    $webhookUrl = "https://api.telegram.org/bot{$telegramBotToken}/getWebhookInfo";
    $webhookResponse = @file_get_contents($webhookUrl);
    
    if ($webhookResponse !== false) {
        $webhookData = json_decode($webhookResponse, true);
        if ($webhookData['ok'] === true) {
            if (!empty($webhookData['result']['url'])) {
                echo "<p class='success'>✓ Webhook is set to: " . $webhookData['result']['url'] . "</p>";
                echo "<p class='info'>Last error: " . ($webhookData['result']['last_error_message'] ?? 'None') . "</p>";
                echo "<p class='info'>Last error date: " . ($webhookData['result']['last_error_date'] ?? 'None') . "</p>";
            } else {
                echo "<p class='warning'>⚠️ Webhook is not set. You need to set up your webhook.</p>";
            }
        } else {
            echo "<p class='error'>✗ Failed to get webhook info: " . $webhookData['description'] . "</p>";
        }
    } else {
        echo "<p class='error'>✗ Failed to connect to Telegram API to check webhook.</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Skipping webhook check because Telegram token is not set</p>";
}

echo "</div>";

// Check 5: PHP Configuration
echo "<div class='section'>
    <h2>5. PHP Configuration</h2>";

// Check if required extensions are loaded
$requiredExtensions = ['json', 'openssl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ Extension '$ext' is loaded</p>";
    } else {
        echo "<p class='error'>✗ Extension '$ext' is not loaded</p>";
    }
}

// Check if allow_url_fopen is enabled
if (ini_get('allow_url_fopen')) {
    echo "<p class='success'>✓ allow_url_fopen is enabled</p>";
} else {
    echo "<p class='error'>✗ allow_url_fopen is disabled. This is required for the bot to work.</p>";
}

echo "</div>";

// Check 6: Test LLM Response
echo "<div class='section'>
    <h2>6. Test LLM Response</h2>";

if (isset($openRouterApiKey) && $openRouterApiKey !== 'YOUR_OPENROUTER_API_KEY' && !empty($openRouterApiKey)) {
    echo "<p>Sending test request to LLM...</p>";
    
    $testPrompt = "Respond with exactly 'TEST SUCCESS' and nothing else.";
    $llmModel = $llmModel ?? 'mistralai/mistral-7b-instruct:free';
    $maxTokens = $maxTokens ?? 100;
    
    $url = 'https://openrouter.ai/api/v1/chat/completions';
    
    $data = [
        'model' => $llmModel,
        'messages' => [
            ['role' => 'user', 'content' => $testPrompt]
        ],
        'max_tokens' => $maxTokens
    ];
    
    $options = [
        'http' => [
            'header' => [
                'Authorization: Bearer ' . $openRouterApiKey,
                'Content-Type: application/json'
            ],
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $responseData = json_decode($response, true);
        if (isset($responseData['choices'][0]['message']['content'])) {
            echo "<p class='success'>✓ Successfully received response from LLM:</p>";
            echo "<pre>" . htmlspecialchars($responseData['choices'][0]['message']['content']) . "</pre>";
        } else {
            echo "<p class='error'>✗ Failed to get response from LLM:</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p class='error'>✗ Failed to connect to LLM API.</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Skipping LLM test because API key is not set</p>";
}

echo "</div>";

echo "<div class='section'>
    <h2>Common Solutions</h2>
    <ul>
        <li>Make sure your webhook URL is accessible from the internet (not localhost)</li>
        <li>Ensure your server has SSL certificate (HTTPS is required by Telegram)</li>
        <li>Check that your server's firewall allows outgoing connections</li>
        <li>Verify that PHP has the required extensions (json, openssl)</li>
        <li>Make sure allow_url_fopen is enabled in php.ini</li>
        <li>Check your server's error logs for any PHP errors</li>
    </ul>
</div>";

echo "</body>
</html>";
?>
