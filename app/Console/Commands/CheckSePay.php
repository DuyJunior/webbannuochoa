<?php

namespace App\Console\Commands;

use App\Services\SePayService;
use App\Support\DemoMode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckSePay extends Command
{
    protected $signature = 'sepay:check';

    protected $description = 'Check SePay readiness without exposing secrets or making a transfer';

    public function handle(SePayService $sepay): int
    {
        $checks = [
            'SePay enabled' => (bool) config('sepay.enabled'),
            'Demo mode off' => ! DemoMode::enabled(),
            'HTTPS application URL' => str_starts_with((string) config('app.url'), 'https://'),
            'HMAC secret configured (32+ characters)' => strlen((string) config('sepay.webhook_secret')) >= 32,
            'Backend ready to accept SePay payments' => $sepay->ready(),
            'Receipt migration applied' => Schema::hasTable('sepay_webhook_receipts'),
            'Database queue table available' => Schema::hasTable(config('queue.connections.database.table', 'jobs')),
            'Queue uses the same database' => config('queue.connections.database.driver') === 'database'
                && (config('queue.connections.database.connection') ?: config('database.default')) === config('database.default'),
        ];
        foreach ($checks as $label => $passed) {
            $this->line(($passed ? 'PASS ' : 'FAIL ').$label);
        }
        $this->line('Webhook: '.rtrim((string) config('app.url'), '/').'/payment/sepay/webhook');
        $this->line('Also verify the SePay dashboard, HMAC secret match, DH code rule, and running database queue worker.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
