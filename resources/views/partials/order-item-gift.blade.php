@if($order->user_id === auth()->id() && $order->status !== 'cancelled')
<a class="gx-order-link" href="{{ route('gifts.create', ['order_id' => $order->id, 'perfume_id' => $item->perfume_id]) }}">@include('partials.icon', ['name' => 'gift', 'size' => 14]) {{ __('Tạo thiệp quà QR') }}</a>
@endif
