<?php

// Validate names/shape without printing passwords, keys, or connection values.
$fail = static function (string $message): never {
    fwrite(STDERR, "Startup configuration error: {$message}\n");
    exit(1);
};

foreach (['APP_KEY', 'APP_URL', 'TRUSTED_PROXIES', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $name) {
    if (getenv($name) === false || trim((string) getenv($name)) === '') {
        $fail("{$name} is required.");
    }
}

foreach ([
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'DEMO_MODE' => 'false',
    'DB_CONNECTION' => 'mysql',
    'SESSION_DRIVER' => 'database',
    'SESSION_SECURE_COOKIE' => 'true',
    'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'database',
    'MYSQL_ATTR_SSL_VERIFY_SERVER_CERT' => 'true',
    'LOG_CHANNEL' => 'stderr',
] as $name => $expected) {
    if (getenv($name) !== $expected) {
        $fail("{$name} must be {$expected} in this deployment image.");
    }
}

foreach (['RUN_MIGRATIONS', 'RUN_SEEDERS'] as $name) {
    if (! in_array(getenv($name), ['true', 'false'], true)) {
        $fail("{$name} must be true or false.");
    }
}

foreach (['DB_URL', 'DB_SOCKET'] as $name) {
    if (getenv($name) !== false && getenv($name) !== '') {
        $fail("Remove {$name}; use the explicit TLS MySQL settings.");
    }
}

foreach (['DB_HOST', 'DB_DATABASE'] as $name) {
    if (preg_match('/[;\x00-\x20]/', (string) getenv($name))) {
        $fail("{$name} contains invalid DSN characters.");
    }
}

foreach (['DB_PORT', 'PORT'] as $name) {
    if (filter_var(getenv($name), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
        $fail("{$name} must be a valid TCP port.");
    }
}

$key = (string) getenv('APP_KEY');
if (! str_starts_with($key, 'base64:') || strlen((string) base64_decode(substr($key, 7), true)) !== 32) {
    $fail('APP_KEY must contain a stable base64-encoded 32-byte key (base64:...).');
}

$url = parse_url((string) getenv('APP_URL'));
if ($url === false || ($url['scheme'] ?? '') !== 'https' || empty($url['host']) || isset($url['user']) || isset($url['pass'])) {
    $fail('APP_URL must be the public HTTPS URL, without credentials.');
}

if (filter_var(getenv('DB_QUEUE_RETRY_AFTER'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 60]]) === false) {
    $fail('DB_QUEUE_RETRY_AFTER must be at least 60 seconds (worker timeout is 40).');
}

fwrite(STDOUT, "Production environment validated.\n");
