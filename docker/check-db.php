<?php

// External databases require verified TLS; Compose is restricted to private db:3306.
// Do not log PDO exception text: it can reveal connection details.
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_DATABASE'));
$internal = getenv('APP_DEPLOYMENT') === 'compose';
if ($internal && (getenv('DB_HOST') !== 'db' || getenv('DB_PORT') !== '3306')) {
    fwrite(STDERR, "Compose database must be the private db:3306 service.\n");
    exit(1);
}

for ($attempt = 1; $attempt <= 5; $attempt++) {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ];
        if (! $internal) {
            $options += [
                PDO::MYSQL_ATTR_SSL_CA => getenv('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ];
        }
        $pdo = new PDO($dsn, (string) getenv('DB_USERNAME'), (string) getenv('DB_PASSWORD'), $options);
        if ($internal) {
            fwrite(STDOUT, "Private Compose MySQL connection established.\n");
            exit(0);
        }
        $row = $pdo->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM);
        if (! $row || empty($row[1])) {
            fwrite(STDERR, "Database connection refused: TLS is not active.\n");
            exit(1);
        }
        fwrite(STDOUT, "Verified TLS MySQL connection established.\n");
        exit(0);
    } catch (PDOException) {
        fwrite(STDERR, "MySQL connection attempt {$attempt}/5 failed; check database settings, CA (external DB), hostname and network access.\n");
        if ($attempt < 5) {
            sleep(3);
        }
    }
}

exit(1);
