<?php
// Configuration file for Telegram Bot with OpenRouter LLM Integration

// Telegram Bot Configuration
$telegramBotToken = 'YOUR_TELEGRAM_BOT_TOKEN';  // Get this from @BotFather on Telegram

// OpenRouter API Configuration
$openRouterApiKey = 'YOUR_OPENROUTER_API_KEY';  // Get this from https://openrouter.ai/

// LLM Configuration
$llmModels = [
    'mistralai/mistral-7b-instruct:free' => 'Mistral 7B (Free)',
    'google/gemma-7b-it:free' => 'Gemma 7B (Free)',
    'microsoft/phi-3-mini-128k-instruct:free' => 'Phi-3 Mini 128K (Free)',
    'openchat/openchat-7b:free' => 'OpenChat 7B (Free)',
    'neversleep/llama-3-lumimaid-8b:free' => 'Llama 3 Lumimaid 8B (Free)'
];

$llmModel = 'mistralai/mistral-7b-instruct:free';  // Default free model
$maxTokens = 500;  // Maximum number of tokens in the response

// Bot Behavior Configuration
$botName = 'Telegram LLM Bot';  // Name of your bot
$allowedChatTypes = ['group', 'supergroup'];  // Chat types where the bot will respond

// Error Messages
$errorMessages = [
    'llm_failed' => "Sorry, I couldn't process your request at the moment.",
    'response_failed' => "Sorry, I couldn't generate a response.",
    'invalid_request' => "Invalid request."
];

// System Instructions for the LLM (Optional)
$systemInstructions = "You are a helpful assistant in a Telegram group chat. Answer questions concisely and accurately.";
?>
