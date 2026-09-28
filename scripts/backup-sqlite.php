<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite') {
    fwrite(STDERR, "This helper only supports SQLite. Back up your MySQL database manually before migrating.\n");
    exit(1);
}
$source = config('database.connections.sqlite.database');
if (! is_file($source) || filesize($source) === 0) {
    echo "No existing SQLite data to back up.\n";
    exit(0);
}
$folder = storage_path('app/backups');
if (! is_dir($folder)) {
    mkdir($folder, 0700, true);
}
$target = $folder.'/database-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sqlite';
$from = new SQLite3($source, SQLITE3_OPEN_READONLY);
$to = new SQLite3($target, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
if (! $from->backup($to) || $to->querySingle('PRAGMA integrity_check') !== 'ok') {
    throw new RuntimeException('Backup failed; migration must not proceed.');
}
$to->close();
$from->close();
echo 'SQLite backup verified: '.basename($target)."\n";
