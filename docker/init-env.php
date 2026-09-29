<?php

// Run in the PHP image, mounting the repository at /setup. Never print secrets.
$mode = $argv[1] ?? 'local';
if (! in_array($mode, ['local', 'vps'], true)) {
    fwrite(STDERR, "Usage: php docker/init-env.php [local|vps]\n");
    exit(1);
}
$path = '/setup/.env';
if (file_exists($path)) {
    fwrite(STDERR, ".env already exists; left unchanged.\n");
    exit(1);
}
$template = file_get_contents('/setup/.env.'.($mode === 'local' ? 'docker' : 'vps').'.example');
if ($template === false) {
    exit(1);
}
foreach ([
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'DB_PASSWORD' => bin2hex(random_bytes(24)),
    'DB_ROOT_PASSWORD' => bin2hex(random_bytes(32)),
] as $name => $value) {
    $template = preg_replace('/^'.$name.'=$/m', $name.'='.$value, str_replace("\r\n", "\n", $template));
}
umask(0077);
$file = fopen($path, 'x');
if ($file === false || fwrite($file, $template) !== strlen($template)) {
    fwrite(STDERR, "Could not write .env.\n");
    exit(1);
}
fclose($file);
fwrite(STDOUT, ".env created with random application key and database passwords.\n");
