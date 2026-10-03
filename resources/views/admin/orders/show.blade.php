@extends('layouts.admin')

@section('title', __('Chi tiết đơn hàng #') . $order->id)
@section('page_title', __('Chi tiết đơn hàng #') . $order->id)

@section('content')
@php
    $orderLabels = ['pending' => __('Chờ xử lý'), 'confirmed' => __('Đã xác nhận'), 'paid' => __('Đã thanh toán'), 'paid_momo' => __('Đã thanh toán MoMo'), 'cod_ordered' => __('Chờ thu COD'), 'cod_paid' => __('Đã thu COD'), 'completed' => __('Đã hoàn thành'), 'cancelled' => __('Đã hủy')];
    $shippingLabels = ['pending' => __('Chờ tạo vận đơn'), 'not_shipped' => __('Chưa giao hàng'), 'processing' => __('Đang tạo vận đơn'), 'ready_to_pick' => __('Chờ lấy hàng'), 'picking' => __('Đang lấy hàng'), 'picked' => __('Đã lấy hàng'), 'storing' => __('Đang lưu kho'), 'transporting' => __('Đang trung chuyển'), 'sorting' => __('Đang phân loại'), 'delivering' => __('Đang giao hàng'), 'delivered' => __('Giao thành công'), 'return' => __('Chờ hoàn hàng'), 'returning' => __('Đang hoàn hàng'), 'return_transporting' => __('Đang chuyển hoàn'), 'return_sorting' => __('Đang phân loại hoàn'), 'returned' => __('Đã hoàn hàng'), 'cancelled' => __('Đã hủy giao hàng')];
    $shippingRanks = ['pending' => 0, 'not_shipped' => 0, 'processing' => 1, 'ready_to_pick' => 2, 'picking' => 3, 'picked' => 4, 'storing' => 5, 'transporting' => 5, 'sorting' => 5, 'delivering' => 6, 'delivered' => 7, 'return' => 8, 'returning' => 8, 'return_transporting' => 8, 'return_sorting' => 8, 'returned' => 9];
    $isTerminal = in_array($order->status, ['cancelled', 'completed'], true) || in_array($order->shipping_status, ['delivered', 'returned', 'cancelled'], true);
    $canCancel = !$isTerminal && (!$order->ghn_order_code || $order->is_demo) && in_array($order->shipping_status, [null, 'pending', 'not_shipped', 'ready_to_pick'], true);
@endphp
<a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> {{ __('Danh sách đơn hàng') }}</a>
<div class="admin-card d-flex justify-content-between align-items-center flex-wrap mb-4" style="gap:16px">
    <div><strong>#DH{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</strong><div class="text-muted small mt-1">{{ __('Đặt lúc') }} {{ $order->created_at->format('H:i · d/m/Y') }} · {{ $order->items->sum('quantity') }} {{ __('sản phẩm') }} @if($order->is_demo) · <span class="badge badge-warning">{{ __('DỮ LIỆU DEMO') }}</span>@endif</div></div>
    <a href="{{ route('admin.finance.transactions', ['search' => '#'.$order->id, 'mode' => $order->is_demo ? 'demo' : 'real']) }}" class="btn btn-outline-secondary btn-sm">{{ __('Xem thanh toán & đối soát') }}</a>
</div>
<div class="row">
    <!-- Left Column: Items and Customer Info -->
    <div class="col-lg-8 mb-4">
        <!-- Products Card -->
        <div class="admin-card mb-4">
            <h5 class="font-weight-bold mb-4" style="color: #0f172a;">
                <i class="fa-solid fa-box text-primary mr-2"></i> {{ __('Danh sách sản phẩm đã đặt') }}
            </h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">
                            <th>{{ __('Sản phẩm') }}</th>
                            <th class="text-right" width="120">{{ __('Đơn giá') }}</th>
                            <th class="text-center" width="100">{{ __('Số lượng') }}</th>
                            <th class="text-right" width="150">{{ __('Thành tiền') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->items as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3" style="width: 55px; height: 55px; border-radius: 8px; overflow: hidden; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                                            @if($item->perfume && $item->perfume->image_src)
                                                <img src="{{ $item->perfume->image_src }}" alt="{{ $item->product_name ?? $item->perfume->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <i class="fa-solid fa-image text-muted" style="font-size: 1.2rem;"></i>
                                            @endif
                                        </div>
                                        <div>
                                            @if($item->perfume)
                                                <a href="{{ route('admin.products.show', $item->perfume_id) }}" class="font-weight-bold text-dark text-decoration-none">
                                                    {{ $item->display_name }}
                                                </a>
                                                <div class="text-muted small mt-1">
                                                    <span>{{ __('Thương hiệu:') }} <strong>{{ $item->perfume->brand }}</strong></span>
                                                    <span class="mx-1">|</span>
                                                    <span>{{ __('Dung tích:') }} <strong class="text-primary">{{ $item->volume_label }}</strong></span>
                                                </div>
                                                @if($item->addon_gift || $item->engrave_text)
                                                    <div class="mt-1 d-flex flex-wrap gap-1" style="font-size: 0.82rem;">
                                                        @if($item->addon_gift)
                                                            <span class="badge badge-warning text-dark mr-1">
                                                                @include('partials.icon', ['name' => 'gift', 'size' => '1em']) {{ ($item->is_gift_bundle || $item->is_discovery_box) ? __('Gói quà đã gồm trong giá bộ') : __('Gói quà Luxury & Thiệp (+50k)') }}
                                                            </span>
                                                        @endif
                                                        @if($item->engrave_text)
                                                            <span class="badge badge-info mr-1">
                                                                @include('partials.icon', ['name' => 'pen', 'size' => '1em']) {{ __('Khắc Laser: "') }}<strong>{{ $item->engrave_text }}</strong>"
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @else
                                                <strong>{{ $item->product_name ?: __('Sản phẩm #').$item->perfume_id }}</strong>
                                                <div class="text-muted small">{{ __('Sản phẩm không còn trong danh mục') }} @if($item->volume_ml) · {{ $item->volume_ml }} ml @endif</div>
                                            @endif
                                            @include('partials.order-item-samples')
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right font-weight-500 text-dark">
                                    {{ number_format($item->price, 0, ',', '.') }} {{ __('đ') }}
                                </td>
                                <td class="text-center font-weight-bold text-dark">
                                    {{ $item->quantity }}
                                </td>
                                <td class="text-right font-weight-bold text-dark">
                                    {{ number_format($item->price * $item->quantity, 0, ',', '.') }} {{ __('đ') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Đơn hàng chưa có dòng sản phẩm.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-right text-muted">{{ __('Tiền sản phẩm') }}</td><td class="text-right">{{ number_format($order->items->sum(fn ($item) => $item->price * $item->quantity), 0, ',', '.') }} {{ __('đ') }}</td></tr>
                        <tr><td colspan="3" class="text-right text-muted">{{ __('Phí giao hàng') }}</td><td class="text-right">{{ number_format($order->ghn_total_fee ?? 0, 0, ',', '.') }} {{ __('đ') }}</td></tr>
                        @if($order->discount_amount)
                            <tr><td colspan="3" class="text-right text-muted">{{ __('Ưu đãi') }} @if($order->coupon_code)({{ $order->coupon_code }})@endif</td><td class="text-right text-success">−{{ number_format($order->discount_amount, 0, ',', '.') }} {{ __('đ') }}</td></tr>
                        @endif
                        @if($order->points_used)
                            <tr><td colspan="3" class="text-right text-muted">{{ __('Điểm đã dùng (') }}{{ number_format($order->points_used) }})</td><td class="text-right text-success">−{{ number_format($order->points_used * 1000, 0, ',', '.') }} {{ __('đ') }}</td></tr>
                        @endif
                        <tr class="border-top">
                            <td colspan="3" class="text-right font-weight-bold text-muted py-3">{{ __('Tổng cộng:') }}</td>
                            <td class="text-right font-weight-bold text-primary py-3" style="font-size: 1.15rem;">
                                {{ number_format($order->total_price, 0, ',', '.') }} {{ __('đ') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Customer Information Card -->
        <div class="admin-card">
            <h5 class="font-weight-bold mb-4" style="color: #0f172a;">
                <i class="fa-solid fa-user-tag text-primary mr-2"></i> {{ __('Thông tin giao hàng') }}
            </h5>
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <table class="table table-borderless" style="font-size: 0.92rem; line-height: 1.8;">
                        <tr>
                            <td class="text-muted p-0" width="130">{{ __('Họ và tên khách:') }}</td>
                            <td class="font-weight-bold text-dark p-0">{{ $order->name ?: __('Chưa có thông tin') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0">{{ __('Số điện thoại:') }}</td>
                            <td class="font-weight-bold text-dark p-0">{{ $order->phone }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0">{{ __('Tài khoản đặt:') }}</td>
                            <td class="p-0">
                                @if($order->user)
                                    <span class="badge badge-light border text-muted">
                                        <i class="fa-solid fa-user mr-1"></i> {{ $order->user->name }} ({{ $order->user->email }})
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-success ml-2 py-0 px-2" style="font-size: 0.78rem;" data-user-id="{{ $order->user->id }}" data-customer-name="{{ $order->user->name }}" onclick="openChatWithUser(Number(this.dataset.userId), this.dataset.customerName)" title="{{ __('Nhắn tin cho khách hàng này') }}">
                                        <i class="fa-solid fa-comment-dots mr-1"></i> {{ __('Nhắn tin') }}
                                    </button>
                                @else
                                    <span class="text-muted italic">{{ __('Khách vãng lai (Guest)') }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="bg-light p-3 rounded" style="border: 1px dashed #cbd5e1; font-size: 0.92rem;">
                        <div class="text-muted font-weight-bold mb-1"><i class="fa-solid fa-map-pin text-danger mr-1"></i> {{ __('Địa chỉ nhận hàng:') }}</div>
                        <div class="text-dark font-weight-500" style="line-height: 1.5;">
                            {{ $order->address }}
                        </div>
                        @if($order->note)
                            <div class="text-muted font-weight-bold mt-3 mb-1">{{ __('Ghi chú đơn hàng:') }}</div>
                            <div class="text-dark" style="white-space:pre-line;overflow-wrap:anywhere">{{ $order->note }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($order->gift_wrap || $order->gift_card || $order->gift_message || $order->gift_delivery_date)
        <div class="admin-card mb-4" style="border: 2px dashed #f472b6; background: #fffdfd;">
            <h5 class="font-weight-bold mb-3" style="color: #be185d;">
                <i class="fa-solid fa-gift mr-2"></i> {{ __('Yêu cầu Gói Quà & Thiệp Chúc Mừng') }}
            </h5>
            <div class="row" style="font-size: 0.92rem;">
                <div class="col-md-6 mb-2">
                    <span class="text-muted">{{ __('Mẫu giấy gói:') }}</span>
                    <strong class="text-dark ml-1">{{ $order->gift_wrap ?: __('Mặc định sang trọng') }}</strong>
                </div>
                <div class="col-md-6 mb-2">
                    <span class="text-muted">{{ __('Mẫu thiệp:') }}</span>
                    <strong class="text-dark ml-1">{{ $order->gift_card ?: __('Thiệp chúc mừng') }}</strong>
                </div>
                @if($order->gift_delivery_date)
                <div class="col-12 mb-2">
                    <span class="text-muted">{{ __('Ngày giao mong muốn:') }}</span>
                    <strong class="text-danger ml-1">@include('partials.icon', ['name' => 'calendar', 'size' => '1em']) {{ \Carbon\Carbon::parse($order->gift_delivery_date)->format('d/m/Y') }}</strong>
                </div>
                @endif
                @if($order->gift_message)
                <div class="col-12 mt-2">
                    <div class="p-3 rounded" style="background: #fff0f5; border-left: 3px solid #be185d;">
                        <span class="text-muted font-weight-bold d-block mb-1">{{ __('Lời nhắn in lên thiệp:') }}</span>
                        <em class="text-dark">“{{ $order->gift_message }}”</em>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Right Column: Status and Actions -->
    <div class="col-lg-4 mb-4">
        <!-- Status Card -->
        <div class="admin-card">
            <h5 class="font-weight-bold mb-3" style="color: #0f172a;">
                <i class="fa-solid fa-sliders text-primary mr-2"></i> {{ __('Trạng thái đơn hàng & Giao hàng') }}
            </h5>

            <div class="py-3 px-3 my-3 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted font-weight-bold">{{ __('Trạng thái đơn:') }}</span>
                    @if($order->status === 'pending')
                        <span class="badge badge-warning text-dark px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem;">
                            <i class="fa-regular fa-clock mr-1"></i> {{ __('Chờ xử lý') }}
                        </span>
                    @elseif($order->status === 'confirmed')
                        <span class="badge badge-primary px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem; background: #e0f2fe; color: #0369a1;">
                            <i class="fa-solid fa-check mr-1"></i> {{ __('Đã xác nhận') }}
                        </span>
                    @elseif($order->status === 'completed')
                        <span class="badge badge-success px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem; background: #dcfce7; color: #15803d;">
                            <i class="fa-solid fa-circle-check mr-1"></i> {{ __('Đã hoàn thành') }}
                        </span>
                    @elseif($order->status === 'cancelled')
                        <span class="badge badge-danger px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem; background: #fee2e2; color: #b91c1c;">
                            <i class="fa-solid fa-ban mr-1"></i> {{ __('Đã hủy đơn') }}
                        </span>
                    @else
                        <span class="badge badge-secondary px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem;">{{ $orderLabels[$order->status] ?? $order->status }}</span>
                    @endif
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <span class="small text-muted font-weight-bold">{{ __('Trạng thái GHN:') }}</span>
                    @php
                        $shStatus = $order->shipping_status ?? 'pending';
                        $shColor = match($shStatus) {
                            'delivered' => '#15803d',
                            'delivering', 'transporting', 'sorting', 'picked' => '#a16207',
                            'ready_to_pick', 'picking' => '#0e7490',
                            'return', 'returning', 'returned', 'return_transporting', 'return_sorting' => '#c2410c',
                            'cancelled' => '#b91c1c',
                            default => '#64748b'
                        };
                    @endphp
                    <span class="font-weight-bold small" style="color: {{ $shColor }};">
                        <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:{{ $shColor }}; margin-right:3px;"></span>
                        {{ $shippingLabels[$shStatus] ?? $shStatus }}
                    </span>
                </div>
            </div>

            <!-- Error message if validation or exception fails -->


            <!-- Update Status Form -->
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="mt-3" data-confirm="Xác nhận cập nhật đơn #{{ $order->id }}? Hủy đơn hợp lệ sẽ hoàn kho đã giữ. Giao thành công sẽ ghi nhận thanh toán COD đang chờ.">
                @csrf
                @method('PATCH')
                <fieldset @disabled($isTerminal)>

                {{-- 1. Trạng thái giao hàng GHN --}}
                <div class="form-group mb-3">
                    <label for="shippingStatusSelect" class="font-weight-bold text-muted small" style="text-transform: uppercase;">
                        <i class="fa-solid fa-truck text-primary mr-1"></i> {{ __('Trạng thái giao hàng (GHN)') }}
                    </label>
                    <select name="shipping_status" id="shippingStatusSelect" class="form-control">
                        <option value="">{{ __('Giữ nguyên —') }} {{ $shippingLabels[$shStatus] ?? $shStatus }}</option>
                        @foreach($shippingLabels as $value => $label)
                            @if($value !== $shStatus && ($value !== 'cancelled' || $canCancel) && (!isset($shippingRanks[$value], $shippingRanks[$shStatus]) || $shippingRanks[$value] >= $shippingRanks[$shStatus]))
                                <option value="{{ $value }}" @selected(old('shipping_status') === $value)>{{ __($label) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                {{-- 2. Trạng thái đơn hàng tổng thể --}}
                <div class="form-group mb-3">
                    <label for="statusSelect" class="font-weight-bold text-muted small" style="text-transform: uppercase;">
                        <i class="fa-solid fa-receipt text-primary mr-1"></i> {{ __('Trạng thái đơn hàng') }}
                    </label>
                    <select name="status" id="statusSelect" class="form-control">
                        <option value="">{{ __('Giữ nguyên —') }} {{ $orderLabels[$order->status] ?? $order->status }}</option>
                        @foreach(['pending', 'confirmed', 'completed', 'cancelled'] as $value)
                            @if($value !== $order->status && ($value !== 'cancelled' || $canCancel))
                                <option value="{{ $value }}" @selected(old('status') === $value)>{{ $orderLabels[$value] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                @if(!$isTerminal)
                    <div class="alert alert-warning py-2 px-3 small my-3" style="border-radius: 8px; border-left: 3px solid #d97706;">
                        <i class="fa-solid fa-circle-info mr-1 text-warning"></i>
                        {{ __('Chỉ chọn trạng thái cần thay đổi. Hủy đơn hợp lệ sẽ hoàn lượng hàng đã giữ; giao thành công ghi nhận thanh toán COD đang chờ.') }} @if(!$canCancel){{ __('Đơn này không thể hủy tại đây.') }}@endif
                    </div>
                @else
                    <div class="alert alert-info py-2 px-3 small my-3" style="border-radius: 8px; border-left: 3px solid #2563eb;">
                        <i class="fa-solid fa-circle-info mr-1 text-primary"></i>
                        {{ __('Đơn đã kết thúc và không thể mở lại. Bạn vẫn có thể xem thanh toán, đối soát và lịch sử bên dưới.') }}
                    </div>
                @endif

                <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold" style="border-radius: 8px;">
                    <i class="fa-solid fa-save mr-1"></i> {{ __('Cập nhật trạng thái') }}
                </button>
                </fieldset>
            </form>
        </div>

        <!-- History Metadata Card -->
        <div class="admin-card mt-4 p-3 bg-light border-0" style="font-size: 0.85rem;">
            <div class="text-muted mb-2">
                <i class="fa-regular fa-clock mr-1"></i> <strong>{{ __('Lịch sử cập nhật:') }}</strong>
            </div>
            <div class="text-muted">{{ __('Ngày đặt:') }} {{ $order->created_at->format('d/m/Y H:i:s') }}</div>
            <div class="text-muted">{{ __('Cập nhật cuối:') }} {{ $order->updated_at->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>
</div>
@include('admin.orders._emails')
<details class="admin-card mb-4">
    <summary class="font-weight-bold">{{ __('Lịch sử xử lý & chứng từ kho') }}</summary>
    @include('partials.order-timeline')
    @include('partials.inventory-movements')
</details>
@endsection
