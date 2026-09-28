<?php

// The app uses the same explicit connection fields and mandatory verification.
// Do not log PDO exception text: it can reveal connection details.
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_DATABASE'));

for ($attempt = 1; $attempt <= 5; $attempt++) {
    try {
        $pdo = new PDO($dsn, (string) getenv('DB_USERNAME'), (string) getenv('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_SSL_CA => getenv('MYSQL_ATTR_SSL_CA'),
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ]);
        $row = $pdo->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM);
        if (! $row || empty($row[1])) {
            fwrite(STDERR, "Database connection refused: TLS is not active.\n");
            exit(1);
        }
        fwrite(STDOUT, "Verified TLS MySQL connection established.\n");
        exit(0);
    } catch (PDOException) {
        fwrite(STDERR, "TLS MySQL connection attempt {$attempt}/5 failed; check database settings, CA, hostname and network access.\n");
        if ($attempt < 5) {
            sleep(3);
        }
    }
}

exit(1);
