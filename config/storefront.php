<?php

return [
    // Store policy and contact confirmed by the owner.
    'return_days' => 7,
    'zalo_phone' => '0344216466',
    'zalo_url' => 'https://zalo.me/0344216466',
    'facebook_url' => 'https://www.facebook.com/khanhduy.nguyentran.39',
    'instagram_url' => 'https://www.instagram.com/ntkduy_1312/',
    // Publish only details confirmed by the shop owner. Empty values stay hidden.
    'address' => env('STORE_ADDRESS'),
    'support_hours' => env('STORE_SUPPORT_HOURS'),
    'contact_email' => env('STORE_CONTACT_EMAIL'),
    'privacy_updated_at' => '03/10/2026',
];
