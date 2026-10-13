<?php

return [
    'enabled' => env('AI_CHAT_ENABLED', false),
    'daily_request_limit' => (int) env('AI_CHAT_DAILY_LIMIT', 50),
    // Any OpenAI-compatible Chat Completions API; must be HTTPS.
    'base_url' => env('AI_CHAT_BASE_URL', 'https://aixingialaire.com/v1'),
    'api_key' => env('AI_CHAT_API_KEY', ''),
    'model' => env('AI_CHAT_MODEL', 'gpt-5.6-sol'),
    // Always use an asynchronous connection; do not hold the customer's HTTP request open.
    'connection' => 'database',
    'queue' => 'ai-chat',
];
