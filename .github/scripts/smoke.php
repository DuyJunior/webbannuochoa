<?php

// Runs inside the production PHP image against the isolated CI Compose stack.
$base = rtrim($argv[1] ?? '', '/');
if (! preg_match('~^http://localhost:[0-9]+$~', $base)) {
    throw new RuntimeException('Expected localhost CI URL.');
}
$cookies = tempnam(sys_get_temp_dir(), 'soopi-cookie-');
$request = static function (string $path, ?array $data = null) use ($base, $cookies): string {
    $curl = curl_init($base.$path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 4, CURLOPT_TIMEOUT => 20,
        CURLOPT_COOKIEJAR => $cookies, CURLOPT_COOKIEFILE => $cookies,
    ]);
    if ($data !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
    if ($body === false || $status !== 200 || ! str_starts_with($url, $base.'/')) {
        throw new RuntimeException("HTTP check failed for {$path} (status {$status}).");
    }
    if ($data !== null && $url !== $base.'/admin/dashboard') {
        throw new RuntimeException('Login did not reach admin dashboard.');
    }
    curl_close($curl);

    return $body;
};
try {
    $request('/up');
    $home = $request('/');
    preg_match_all('~(?:href|src)="'.preg_quote($base, '~').'(/build/[^"<>]+)"~', $home, $assets);
    if (count($assets[1]) < 2) {
        throw new RuntimeException('Expected built CSS and JavaScript URLs.');
    }
    foreach (array_unique($assets[1]) as $path) {
        $request($path);
    }
    $login = $request('/admin/login');
    if (! preg_match('/name="_token"[^>]*value="([^"]+)"/', $login, $csrf)) {
        throw new RuntimeException('Missing login CSRF token.');
    }
    $request('/admin/login', [
        '_token' => html_entity_decode($csrf[1]),
        'email' => 'admin.demo@example.test', 'password' => 'DemoPerfume2026!',
    ]);
    echo "MySQL-backed home, health, built assets and admin session passed.\n";
} finally {
    unlink($cookies);
}
