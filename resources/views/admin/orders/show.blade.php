@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng #' . $order->id)
@section('page_title')
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm mr-3" style="border-radius: 6px;">
        <i class="fa-solid fa-arrow-left"></i> Quay lại
    </a>
    Chi tiết đơn hàng #{{ $order->id }}
@endsection

@section('content')
@include('partials.order-timeline')
@include('partials.inventory-movements')
<div class="row">
    <!-- Left Column: Items and Customer Info -->
    <div class="col-lg-8 mb-4">
        <!-- Products Card -->
        <div class="admin-card mb-4">
            <h5 class="font-weight-bold mb-4" style="color: #0f172a;">
                <i class="fa-solid fa-box text-primary mr-2"></i> Danh sách sản phẩm đã đặt
            </h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">
                            <th>Sản phẩm</th>
                            <th class="text-right" width="120">Đơn giá</th>
                            <th class="text-center" width="100">Số lượng</th>
                            <th class="text-right" width="150">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
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
                                                    {{ $item->product_name ?? $item->perfume->name }}
                                                </a>
                                                <div class="text-muted small mt-1">
                                                    <span>Thương hiệu: <strong>{{ $item->perfume->brand }}</strong></span>
                                                    <span class="mx-1">|</span>
                                                    <span>Dung tích: <strong class="text-primary">{{ $item->volume_ml ? $item->volume_ml.'ml' : ($item->perfume->volume_ml.'ml') }}</strong></span>
                                                </div>
                                                @if($item->addon_gift || $item->engrave_text)
                                                    <div class="mt-1 d-flex flex-wrap gap-1" style="font-size: 0.82rem;">
                                                        @if($item->addon_gift)
                                                            <span class="badge badge-warning text-dark mr-1">
                                                                @include('partials.icon', ['name' => 'gift', 'size' => '1em']) Gói quà Luxury & Thiệp (+50k)
                                                            </span>
                                                        @endif
                                                        @if($item->engrave_text)
                                                            <span class="badge badge-info mr-1">
                                                                @include('partials.icon', ['name' => 'pen', 'size' => '1em']) Khắc Laser: "<strong>{{ $item->engrave_text }}</strong>"
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted font-italic">Sản phẩm đã bị xóa khỏi hệ thống (#{{ $item->perfume_id }})</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right font-weight-500 text-dark">
                                    {{ number_format($item->price, 0, ',', '.') }} đ
                                </td>
                                <td class="text-center font-weight-bold text-dark">
                                    {{ $item->quantity }}
                                </td>
                                <td class="text-right font-weight-bold text-dark">
                                    {{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-top">
                            <td colspan="3" class="text-right font-weight-bold text-muted py-3">Tổng cộng:</td>
                            <td class="text-right font-weight-bold text-primary py-3" style="font-size: 1.15rem;">
                                {{ number_format($order->total_price, 0, ',', '.') }} đ
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Customer Information Card -->
        <div class="admin-card">
            <h5 class="font-weight-bold mb-4" style="color: #0f172a;">
                <i class="fa-solid fa-user-tag text-primary mr-2"></i> Thông tin giao hàng
            </h5>
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <table class="table table-borderless" style="font-size: 0.92rem; line-height: 1.8;">
                        <tr>
                            <td class="text-muted p-0" width="130">Họ và tên khách:</td>
                            <td class="font-weight-bold text-dark p-0">{{ $order->customer_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0">Số điện thoại:</td>
                            <td class="font-weight-bold text-dark p-0">{{ $order->phone }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0">Tài khoản đặt:</td>
                            <td class="p-0">
                                @if($order->user)
                                    <span class="badge badge-light border text-muted">
                                        <i class="fa-solid fa-user mr-1"></i> {{ $order->user->name }} ({{ $order->user->email }})
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-success ml-2 py-0 px-2" style="font-size: 0.78rem;" onclick="openChatWithUser({{ $order->user->id }}, '{{ addslashes($order->user->name) }}')" title="Nhắn tin cho khách hàng này">
                                        <i class="fa-solid fa-comment-dots mr-1"></i> Nhắn tin
                                    </button>
                                @else
                                    <span class="text-muted italic">Khách vãng lai (Guest)</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="bg-light p-3 rounded" style="border: 1px dashed #cbd5e1; font-size: 0.92rem;">
                        <div class="text-muted font-weight-bold mb-1"><i class="fa-solid fa-map-pin text-danger mr-1"></i> Địa chỉ nhận hàng:</div>
                        <div class="text-dark font-weight-500" style="line-height: 1.5;">
                            {{ $order->address }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($order->gift_wrap || $order->gift_card || $order->gift_message || $order->gift_delivery_date)
        <div class="admin-card mb-4" style="border: 2px dashed #f472b6; background: #fffdfd;">
            <h5 class="font-weight-bold mb-3" style="color: #be185d;">
                <i class="fa-solid fa-gift mr-2"></i> Yêu cầu Gói Quà & Thiệp Chúc Mừng
            </h5>
            <div class="row" style="font-size: 0.92rem;">
                <div class="col-md-6 mb-2">
                    <span class="text-muted">Mẫu giấy gói:</span>
                    <strong class="text-dark ml-1">{{ $order->gift_wrap ?: 'Mặc định sang trọng' }}</strong>
                </div>
                <div class="col-md-6 mb-2">
                    <span class="text-muted">Mẫu thiệp:</span>
                    <strong class="text-dark ml-1">{{ $order->gift_card ?: 'Thiệp chúc mừng' }}</strong>
                </div>
                @if($order->gift_delivery_date)
                <div class="col-12 mb-2">
                    <span class="text-muted">Ngày giao mong muốn:</span>
                    <strong class="text-danger ml-1">@include('partials.icon', ['name' => 'calendar', 'size' => '1em']) {{ \Carbon\Carbon::parse($order->gift_delivery_date)->format('d/m/Y') }}</strong>
                </div>
                @endif
                @if($order->gift_message)
                <div class="col-12 mt-2">
                    <div class="p-3 rounded" style="background: #fff0f5; border-left: 3px solid #be185d;">
                        <span class="text-muted font-weight-bold d-block mb-1">Lời nhắn in lên thiệp:</span>
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
                <i class="fa-solid fa-sliders text-primary mr-2"></i> Trạng thái đơn hàng & Giao hàng
            </h5>
            
            <div class="py-3 px-3 my-3 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted font-weight-bold">Trạng thái đơn:</span>
                    @if($order->status === 'pending')
                        <span class="badge badge-warning text-dark px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem;">
                            <i class="fa-regular fa-clock mr-1"></i> Chờ xử lý
                        </span>
                    @elseif($order->status === 'confirmed')
                        <span class="badge badge-primary px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem; background: #e0f2fe; color: #0369a1;">
                            <i class="fa-solid fa-check mr-1"></i> Đã xác nhận
                        </span>
                    @elseif($order->status === 'completed')
                        <span class="badge badge-success px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem; background: #dcfce7; color: #15803d;">
                            <i class="fa-solid fa-circle-check mr-1"></i> Đã hoàn thành
                        </span>
                    @elseif($order->status === 'cancelled')
                        <span class="badge badge-danger px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem; background: #fee2e2; color: #b91c1c;">
                            <i class="fa-solid fa-ban mr-1"></i> Đã hủy đơn
                        </span>
                    @else
                        <span class="badge badge-secondary px-3 py-1 font-weight-bold" style="border-radius: 14px; font-size: 0.8rem;">{{ strtoupper($order->status) }}</span>
                    @endif
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <span class="small text-muted font-weight-bold">Trạng thái GHN:</span>
                    @php
                        $shStatus = $order->shipping_status ?? 'pending';
                        $shColor = match($shStatus) {
                            'delivered' => '#16a34a',
                            'delivering', 'transporting', 'sorting', 'picked' => '#f59e0b',
                            'ready_to_pick', 'picking' => '#06b6d4',
                            'return', 'returning', 'returned' => '#ea580c',
                            'cancelled' => '#dc2626',
                            default => '#64748b'
                        };
                        $shLabels = [
                            'pending' => 'Chờ tạo vận đơn',
                            'ready_to_pick' => 'Chờ lấy hàng',
                            'picking' => 'Đang lấy hàng',
                            'delivering' => 'Đang giao hàng',
                            'delivered' => 'Giao hàng thành công',
                            'return' => 'Chờ hoàn hàng',
                            'returned' => 'Đã hoàn hàng',
                            'cancelled' => 'Hủy giao hàng',
                        ];
                    @endphp
                    <span class="font-weight-bold small" style="color: {{ $shColor }};">
                        <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:{{ $shColor }}; margin-right:3px;"></span>
                        {{ $shLabels[$shStatus] ?? $shStatus }}
                    </span>
                </div>
            </div>

            <!-- Error message if validation or exception fails -->
            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger p-2" role="alert" style="font-size: 0.85rem; border-radius: 8px;">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> {{ $errors->first() }}
                </div>
            @endif

            <!-- Update Status Form -->
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="mt-3">
                @csrf
                @method('PATCH')
                
                {{-- 1. Trạng thái giao hàng GHN --}}
                <div class="form-group mb-3">
                    <label for="shippingStatusSelect" class="font-weight-bold text-muted small" style="text-transform: uppercase;">
                        <i class="fa-solid fa-truck text-primary mr-1"></i> Trạng thái giao hàng (GHN)
                    </label>
                    <select name="shipping_status" id="shippingStatusSelect" class="form-control">
                        <option value="pending" {{ $order->shipping_status === 'pending' ? 'selected' : '' }}>Chờ tạo vận đơn (Pending)</option>
                        <option value="ready_to_pick" {{ $order->shipping_status === 'ready_to_pick' ? 'selected' : '' }}>Chờ lấy hàng (Ready to pick)</option>
                        <option value="picking" {{ $order->shipping_status === 'picking' ? 'selected' : '' }}>Đang lấy hàng (Picking)</option>
                        <option value="delivering" {{ $order->shipping_status === 'delivering' ? 'selected' : '' }}>Đang giao hàng (Delivering)</option>
                        <option value="delivered" {{ $order->shipping_status === 'delivered' ? 'selected' : '' }}>Giao hàng thành công (Delivered)</option>
                        <option value="return" {{ $order->shipping_status === 'return' ? 'selected' : '' }}>Chờ hoàn hàng (Return)</option>
                        <option value="returned" {{ $order->shipping_status === 'returned' ? 'selected' : '' }}>Đã hoàn hàng (Returned)</option>
                        <option value="cancelled" {{ $order->shipping_status === 'cancelled' ? 'selected' : '' }}>Hủy giao hàng (Cancelled)</option>
                    </select>
                </div>

                {{-- 2. Trạng thái đơn hàng tổng thể --}}
                <div class="form-group mb-3">
                    <label for="statusSelect" class="font-weight-bold text-muted small" style="text-transform: uppercase;">
                        <i class="fa-solid fa-receipt text-primary mr-1"></i> Trạng thái đơn hàng
                    </label>
                    <select name="status" id="statusSelect" class="form-control">
                        <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Chờ xử lý (Pending)</option>
                        <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Đã xác nhận (Confirmed)</option>
                        <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Đã hoàn thành (Completed)</option>
                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Hủy đơn hàng (Cancelled)</option>
                    </select>
                </div>

                @if($order->status !== 'cancelled' && $order->shipping_status !== 'cancelled')
                    <div class="alert alert-warning py-2 px-3 small my-3" style="border-radius: 8px; border-left: 3px solid #d97706;">
                        <i class="fa-solid fa-circle-info mr-1 text-warning"></i> 
                        <strong>Lưu ý:</strong> Khi chọn <strong>Hủy đơn</strong>, số lượng sản phẩm sẽ tự động cộng hoàn lại tồn kho. Khi chọn <strong>Giao hàng thành công</strong>, đơn hàng sẽ tự động chuyển sang Đã hoàn thành.
                    </div>
                @else
                    <div class="alert alert-info py-2 px-3 small my-3" style="border-radius: 8px; border-left: 3px solid #2563eb;">
                        <i class="fa-solid fa-circle-info mr-1 text-primary"></i> 
                        <strong>Lưu ý:</strong> Khôi phục đơn hàng đã hủy sẽ trừ lại tồn kho của các sản phẩm tương ứng.
                    </div>
                @endif

                <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold" style="border-radius: 8px;">
                    <i class="fa-solid fa-save mr-1"></i> Cập nhật trạng thái
                </button>
            </form>
        </div>

        <!-- History Metadata Card -->
        <div class="admin-card mt-4 p-3 bg-light border-0" style="font-size: 0.85rem;">
            <div class="text-muted mb-2">
                <i class="fa-regular fa-clock mr-1"></i> <strong>Lịch sử cập nhật:</strong>
            </div>
            <div class="text-muted">Ngày đặt: {{ $order->created_at->format('d/m/Y H:i:s') }}</div>
            <div class="text-muted">Cập nhật cuối: {{ $order->updated_at->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>
</div>
@endsection
