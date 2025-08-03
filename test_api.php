<?php
// Test script for OpenRouter API connection
require_once 'config.php';

function testOpenRouterAPI($apiKey) {
    $url = 'https://openrouter.ai/api/v1/models';
    
    $options = [
        'http' => [
            'header' => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'HTTP-Referer: ' . ($_SERVER['HTTP_HOST'] ?? 'tg-chatbot'),
                'X-Title: ' . ($_SERVER['HTTP_HOST'] ?? 'tg-chatbot')
            ],
            'method' => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return "Failed to connect to OpenRouter API.";
    }
    
    $responseData = json_decode($response, true);
    
    if (isset($responseData['data']) && is_array($responseData['data'])) {
        return "Successfully connected to OpenRouter API. Found " . count($responseData['data']) . " models.";
    }
    
    return "Unexpected response from OpenRouter API.";
}

// Only run the test if this script is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    if (empty($openRouterApiKey) || $openRouterApiKey === 'YOUR_OPENROUTER_API_KEY') {
        echo "Please configure your OpenRouter API key in config.php first.\n";
    } else {
        echo testOpenRouterAPI($openRouterApiKey) . "\n";
    }
}
?>
