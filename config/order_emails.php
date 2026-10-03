<?php

return [
    // Disabling delivery retains the outbox so it can resume after configuration is fixed.
    'enabled' => env('ORDER_EMAILS_ENABLED', true),
    'mailer' => env('ORDER_EMAILS_MAILER'),
];
