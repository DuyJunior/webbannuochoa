@php
    $transaction = $order->paymentTransactions->first(fn ($tx) => in_array($tx->status, ['paid', 'refund_pending', 'refunded'], true)) ?? $order->paymentTransactions->first();
    $gateway = $transaction?->gateway ?? \App\Services\FinancePaymentPolicy::gateway($order);
    $paymentStatus = $transaction?->status ?? \App\Services\FinancePaymentPolicy::status($order);
    $gatewayLabel = \App\Services\FinancePaymentPolicy::GATEWAY_LABELS[$gateway] ?? __('Chưa xác định');
@endphp
@if($gateway === 'momo' && $paymentStatus === 'paid')
    <span class="pay-badge pay-success">{{ __('Ví MoMo (Đã thanh toán)') }}</span>
@elseif($gateway === 'cod' && $paymentStatus === 'pending')
    <span class="pay-badge pay-cod">{{ __('Thanh toán khi nhận hàng (COD)') }}</span>
@else
    <span class="pay-badge {{ $paymentStatus === 'paid' ? 'pay-success' : 'pay-pending' }}">{{ __($gatewayLabel) }} · {{ __(\App\Services\FinancePaymentPolicy::STATUS_LABELS[$paymentStatus] ?? $paymentStatus) }}</span>
@endif
