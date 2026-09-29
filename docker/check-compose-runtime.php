<?php

// Compose uses a private, un-published MySQL service on the same Docker host.
// The Render entrypoint retains its separate mandatory verified-TLS checks.
$fail = static function (string $message): never {
    fwrite(STDERR, "Compose configuration error: {$message}\n");
    exit(1);
};
$local = getenv('APP_ENV') === 'local';
if (! in_array(getenv('APP_ENV'), ['local', 'production'], true)) {
    $fail('APP_ENV must be local or production.');
}
foreach (['APP_KEY', 'APP_URL', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $name) {
    if (trim((string) getenv($name)) === '') {
        $fail("{$name} is required.");
    }
}
foreach ([
    'APP_DEBUG' => 'false', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => 'db',
    'DB_PORT' => '3306', 'SESSION_DRIVER' => 'database', 'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'database', 'LOG_CHANNEL' => 'stderr',
    'SESSION_SECURE_COOKIE' => $local ? 'false' : 'true',
] as $name => $value) {
    if (getenv($name) !== $value) {
        $fail("{$name} must be {$value}.");
    }
}
foreach (['DB_URL', 'DB_SOCKET', 'MYSQL_ATTR_SSL_CA'] as $name) {
    if (getenv($name) !== false && getenv($name) !== '') {
        $fail("Remove {$name}; Compose uses its private db service.");
    }
}
if (! preg_match('/^[a-zA-Z0-9_]+$/', (string) getenv('DB_DATABASE'))) {
    $fail('DB_DATABASE must contain only letters, digits and underscores.');
}
foreach (['RUN_MIGRATIONS', 'RUN_SEEDERS', 'LOCAL_DEMO_SEED', 'DEMO_MODE'] as $name) {
    if (! in_array(getenv($name), ['true', 'false'], true)) {
        $fail("{$name} must be true or false.");
    }
}
if (! $local && (getenv('LOCAL_DEMO_SEED') !== 'false' || getenv('DEMO_MODE') !== 'false')) {
    $fail('Demo mode and demo seeding are forbidden in production.');
}
$key = (string) getenv('APP_KEY');
if (! str_starts_with($key, 'base64:') || strlen((string) base64_decode(substr($key, 7), true)) !== 32) {
    $fail('APP_KEY must be a stable base64-encoded 32-byte key.');
}
$url = parse_url((string) getenv('APP_URL'));
if (! $url || empty($url['host']) || isset($url['user']) || isset($url['pass']) ||
    ($local ? ! in_array($url['scheme'] ?? '', ['http', 'https'], true) : ($url['scheme'] ?? '') !== 'https')) {
    $fail('APP_URL must be a valid URL (HTTPS required in production).');
}
if (! $local && in_array(trim((string) getenv('TRUSTED_PROXIES')), ['', '*', '**'], true)) {
    $fail('Production requires explicit trusted proxy addresses/ranges.');
}
if (filter_var(getenv('PORT'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false ||
    filter_var(getenv('DB_QUEUE_RETRY_AFTER'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 60]]) === false) {
    $fail('PORT or DB_QUEUE_RETRY_AFTER is invalid.');
}
fwrite(STDOUT, 'Compose '.($local ? 'local' : 'production')." environment validated.\n");
