<?php

$path = $argv[1] ?? '';
$fail = static function (string $message): never {
    fwrite(STDERR, "CA validation failed: {$message}\n");
    exit(1);
};

if ($path === '' || ! is_file($path) || ! is_readable($path)) {
    $fail('provide a readable Aiven CA secret file.');
}

$pem = file_get_contents($path);
if ($pem === false || ! preg_match_all('/-----BEGIN CERTIFICATE-----\s+[A-Za-z0-9+\/=\s]+-----END CERTIFICATE-----/', $pem, $matches)) {
    $fail('the secret is not a PEM certificate or CA bundle.');
}

$validCaFound = false;
foreach ($matches[0] as $certificate) {
    $parsed = @openssl_x509_parse($certificate);
    if ($parsed === false) {
        $fail('a certificate in the bundle could not be parsed.');
    }

    $isCa = str_contains((string) ($parsed['extensions']['basicConstraints'] ?? ''), 'CA:TRUE');
    if ($isCa && ($parsed['validFrom_time_t'] ?? PHP_INT_MAX) <= time() && ($parsed['validTo_time_t'] ?? 0) > time()) {
        $validCaFound = true;
    }
}

if (! $validCaFound) {
    $fail('the bundle has no currently valid CA certificate. Download the current project CA from Aiven.');
}

fwrite(STDOUT, "Aiven CA certificate validated.\n");
