<?php

$proxies = trim((string) env('TRUSTED_PROXIES', ''));

return [
    // Trust all only when the service is reachable exclusively through the hosting proxy.
    'trusted_proxies' => $proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $proxies)))),
];
