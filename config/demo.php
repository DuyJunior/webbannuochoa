<?php

return [
    // Explicit opt-in, ignored outside local/testing environments.
    'enabled' => env('DEMO_MODE', false),
    'ghn_webhook_token' => env('GHN_WEBHOOK_TOKEN', ''),
];
