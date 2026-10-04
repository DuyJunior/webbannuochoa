<?php

namespace Tests\Concerns;

use App\Models\PaymentTransaction;
use App\Services\SePayService;
use Illuminate\Testing\TestResponse;

trait SePayRequests
{
    private function configureSePay(): void
    {
        config([
            'demo.enabled' => false, 'sepay.enabled' => true, 'sepay.bank' => 'BIDV',
            'sepay.account_number' => '123456789', 'sepay.sub_account' => '96247TEST',
            'sepay.account_name' => 'TEST MERCHANT', 'sepay.payment_prefix' => 'DH',
            'sepay.webhook_secret' => 'whsec_'.str_repeat('test', 16),
            'queue.connections.database.connection' => 'sqlite',
        ]);
    }

    private function sepayPayload(PaymentTransaction $payment, array $overrides = []): array
    {
        app(SePayService::class)->prepare($payment);

        return array_replace([
            'id' => 1001, 'gateway' => 'BIDV', 'accountNumber' => '123456789',
            'subAccount' => '96247TEST', 'code' => $payment->gateway_order_id,
            'transferType' => 'in', 'transferAmount' => (int) $payment->amount,
            'referenceCode' => 'BANK-REF-1001',
        ], $overrides);
    }

    private function sendSePay(array $payload, ?int $timestamp = null, ?string $secret = null): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $timestamp ??= time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? config('sepay.webhook_secret'));

        return $this->call('POST', route('payment.sepay.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SEPAY_TIMESTAMP' => (string) $timestamp, 'HTTP_X_SEPAY_SIGNATURE' => 'sha256='.$signature,
        ], $body);
    }
}
