<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DockerRuntimeTest extends TestCase
{
    private function validate(array $overrides = []): int
    {
        $environment = array_merge([
            'APP_ENV' => 'production', 'APP_DEBUG' => 'false',
            'APP_KEY' => 'base64:'.base64_encode(str_repeat('t', 32)),
            'APP_URL' => 'https://shop.example.test', 'TRUSTED_PROXIES' => '172.16.0.0/12',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => 'db', 'DB_PORT' => '3306',
            'DB_DATABASE' => 'soopi', 'DB_USERNAME' => 'soopi', 'DB_PASSWORD' => 'test-only-password',
            'SESSION_DRIVER' => 'database', 'SESSION_SECURE_COOKIE' => 'true',
            'CACHE_STORE' => 'database', 'QUEUE_CONNECTION' => 'database', 'LOG_CHANNEL' => 'stderr',
            'RUN_MIGRATIONS' => 'true', 'RUN_SEEDERS' => 'false',
            'DEMO_MODE' => 'false', 'LOCAL_DEMO_SEED' => 'false',
            'PORT' => '10000', 'DB_QUEUE_RETRY_AFTER' => '90',
        ], $overrides);
        $process = proc_open([PHP_BINARY, dirname(__DIR__, 2).'/docker/check-compose-runtime.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
        self::assertIsResource($process);
        foreach ($pipes as $pipe) {
            stream_get_contents($pipe);
            fclose($pipe);
        }

        return proc_close($process);
    }

    public function test_production_configuration_is_accepted(): void
    {
        self::assertSame(0, $this->validate());
    }

    public function test_local_http_demo_configuration_is_accepted(): void
    {
        self::assertSame(0, $this->validate([
            'APP_ENV' => 'local', 'APP_URL' => 'http://localhost:8002',
            'SESSION_SECURE_COOKIE' => 'false', 'TRUSTED_PROXIES' => '',
            'DEMO_MODE' => 'true', 'LOCAL_DEMO_SEED' => 'true',
        ]));
    }

    #[DataProvider('unsafeConfigurations')]
    public function test_unsafe_production_configuration_is_rejected(array $overrides): void
    {
        self::assertSame(1, $this->validate($overrides));
    }

    public static function unsafeConfigurations(): array
    {
        return [
            'public database without TLS' => [['DB_HOST' => 'db.example.com']],
            'database URL override' => [['DB_URL' => 'mysql://elsewhere']],
            'database socket override' => [['DB_SOCKET' => '/tmp/mysql.sock']],
            'HTTP production' => [['APP_URL' => 'http://shop.example.test']],
            'debug exposure' => [['APP_DEBUG' => 'true']],
            'demo account seeding' => [['LOCAL_DEMO_SEED' => 'true']],
            'demo mode' => [['DEMO_MODE' => 'true']],
            'insecure session cookie' => [['SESSION_SECURE_COOKIE' => 'false']],
            'trust every proxy' => [['TRUSTED_PROXIES' => '*']],
            'missing key' => [['APP_KEY' => '']],
            'invalid key' => [['APP_KEY' => 'base64:invalid']],
            'job retried before timeout' => [['DB_QUEUE_RETRY_AFTER' => '20']],
            'DSN injection' => [['DB_DATABASE' => 'soopi;host=elsewhere']],
        ];
    }
}
