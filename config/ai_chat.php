<?php

return [
    'enabled' => env('AI_CHAT_ENABLED', false),
    // Operator confirmation, not automatic billing-plan verification.
    'free_plan_confirmed' => env('GROQ_FREE_PLAN_CONFIRMED', false),
    'daily_request_limit' => (int) env('AI_CHAT_DAILY_LIMIT', 50),
    'api_key' => env('GROQ_API_KEY', ''),
    'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
    // Always use an asynchronous connection; do not hold the customer's HTTP request open.
    'connection' => 'database',
    'queue' => 'ai-chat',
];
