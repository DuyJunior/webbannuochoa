{{-- This view is used only as text/plain; preserve literal characters instead of HTML entities. --}}
SOOPI · PERFUME STUDIO
@if($details['is_demo'])
ĐƠN MÔ PHỎNG — Không thu tiền hoặc giao hàng thật.
@endif

{!! match ($details['type']) { 'paid' => 'Đã ghi nhận thanh toán', 'dispatched' => 'Đơn hàng đang vận chuyển', 'delivered' => 'Đã cập nhật giao thành công', default => 'Soopi đã nhận đơn hàng' } !!} · {!! $details['order_number'] !!}

Chào {!! $details['customer_name'] !!},

@foreach($details['items'] as $item)
{!! $item['name'] !!} · {!! $item['volume_label'] ?? ($item['volume_ml'].' ml') !!} × {!! $item['quantity'] !!}: {!! number_format((float) $item['line_total'], 0, ',', '.') !!} ₫
@if(!empty($item['sample_names']))
Mẫu 5ml: {!! implode(' · ', $item['sample_names']) !!}
@endif
@if(!empty($item['is_gift_bundle']))
Hộp quà & thiệp đã gồm trong giá combo.
@endif
@endforeach

Tổng đơn hàng: {!! number_format((float) $details['total'], 0, ',', '.') !!} ₫
Thanh toán: {!! $details['payment_method'] !!} · {!! $details['payment_status'] !!}
Giao đến: {!! $details['shipping_address'] !!}
@if(!empty($details['note']))
Ghi chú đơn hàng: {!! $details['note'] !!}
@endif
@if($details['tracking_code'])
Mã vận đơn: {!! $details['tracking_code'] !!}
@endif

Theo dõi đơn hàng: {!! $details['tracking_url'] !!}
Vui lòng đăng nhập tài khoản đặt hàng để xem thông tin chi tiết.
Nếu thông tin giao hàng chưa đúng, hãy liên hệ Soopi kèm mã đơn.

Zalo hỗ trợ: {!! config('storefront.zalo_url') !!}
Thông tin cửa hàng: {!! route('store.contact') !!}
Chính sách riêng tư: {!! route('store.privacy') !!}
