<?php

return [
    'enabled' => env('SEPAY_ENABLED', false),
    'bank' => env('SEPAY_BANK', 'BIDV'),
    'account_number' => env('SEPAY_ACCOUNT_NUMBER', ''),
    // BIDV personal accounts require an official VA. The QR uses this number.
    'sub_account' => env('SEPAY_SUB_ACCOUNT', ''),
    'account_name' => env('SEPAY_ACCOUNT_NAME', ''),
    'payment_prefix' => env('SEPAY_PAYMENT_PREFIX', 'DH'),
    'webhook_secret' => env('SEPAY_WEBHOOK_SECRET', ''),
];
