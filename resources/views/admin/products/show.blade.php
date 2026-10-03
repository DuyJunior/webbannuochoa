@extends('layouts.admin')
@section('title', __('Chi tiết sản phẩm'))
@section('page_title', __('Chi tiết sản phẩm'))
@section('content')
<div class="studio-list-heading mb-4"><div><span class="studio-form-kicker">{{ __('SẢN PHẨM #') }}{{ $product->id }}</span><h2>{{ $product->name }}</h2><p class="text-muted mb-0">{{ $product->brand }} · {{ optional($product->category)->name ?? __('Chưa phân loại') }}</p></div><div><a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary mr-2">{{ __('Danh sách') }}</a><a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary"><i class="fa-regular fa-pen-to-square mr-1" aria-hidden="true"></i> {{ __('Chỉnh sửa') }}</a></div></div>
<div class="studio-form-layout">
    <div>
        <section class="admin-card studio-form-section"><span class="studio-form-kicker">{{ __('THÔNG TIN SẢN PHẨM') }}</span><h3>{{ __('Thông số & giá bán') }}</h3>
            <dl class="studio-detail-grid">
                <div><dt>{{ __('Giá bán hiện tại') }}</dt><dd><strong class="h4 text-primary">{{ number_format($product->sale_price ?? $product->price, 0, ',', '.') }}₫</strong>@if($product->sale_price !== null && $product->sale_price < $product->price)<div class="text-muted small mt-1">{{ __('Niêm yết') }} <del>{{ number_format($product->price, 0, ',', '.') }}₫</del></div>@endif</dd></div>
                <div><dt>{{ __('Trạng thái') }}</dt><dd><span class="{{ $product->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $product->is_active ? __('Đang mở bán') : __('Tạm ẩn') }}</span></dd></div>
                <div><dt>{{ __('Dành cho') }}</dt><dd>{{ ['nam' => 'Nam', 'nu' => __('Nữ'), 'unisex' => 'Unisex'][$product->gender] ?? $product->gender }}</dd></div>
                <div><dt>{{ __('Nồng độ') }}</dt><dd>{{ $product->concentration ?: __('Chưa cập nhật') }}</dd></div>
                <div><dt>{{ __('Dung tích chai gốc') }}</dt><dd>{{ $product->volume_ml }}ml</dd></div>
                <div><dt>{{ __('Khối lượng tính phí') }}</dt><dd>{{ $product->weight }}g</dd></div>
            </dl>
        </section>
        <section class="admin-card studio-form-section"><span class="studio-form-kicker">{{ __('TỒN KHO THỰC TẾ') }}</span><h3>{{ __('Số lượng theo dung tích') }}</h3><div class="row">
            @foreach([[__('Chai gốc ').$product->volume_ml.'ml', $product->stock], [__('Mẫu thử 5ml'), $product->stock_5ml], [__('Chiết 10ml'), $product->stock_10ml], ['Chai 50ml', $product->stock_50ml]] as [$label, $stock])
            <div class="col-6 col-lg-3 mb-3"><div class="border rounded p-3"><span class="text-muted small d-block mb-2">{{ __($label) }}</span><strong class="h3 {{ $stock > 5 ? 'text-success' : 'text-danger' }}">{{ number_format($stock) }}</strong><small class="text-muted"> {{ __('chai') }}</small><div class="small mt-2">{{ $stock === 0 ? __('Hết hàng') : ($stock <= 5 ? __('Sắp hết hàng') : __('Còn hàng')) }}</div></div></div>
            @endforeach
        </div><p class="studio-field-help mb-0">{{ __('Từng dung tích có số lượng riêng, không quy đổi tự động từ chai gốc.') }}</p></section>
        @if($product->variants->isNotEmpty())
        <section class="admin-card studio-form-section"><h3>{{ __('Dung tích bổ sung') }}</h3><div class="table-responsive"><table class="table mb-0"><thead><tr><th>{{ __('Dung tích') }}</th><th>{{ __('Giá bán') }}</th><th>{{ __('Tồn kho') }}</th><th>{{ __('Hiển thị') }}</th></tr></thead><tbody>
        @foreach($product->variants as $variant)<tr><th>{{ $variant->volume_ml }} ml</th><td>{{ number_format($variant->price, 0, ',', '.') }}₫</td><td>{{ $variant->stock }} {{ __('chai') }}</td><td><span class="{{ $variant->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $variant->is_active ? __('Đang bán') : __('Đã ẩn') }}</span></td></tr>@endforeach
        </tbody></table></div></section>
        @endif
        <section class="admin-card studio-form-section"><h3>{{ __('Mô tả sản phẩm') }}</h3><p class="mb-0 text-muted" style="white-space:pre-line">{{ $product->description ?: __('Chưa có mô tả cho sản phẩm này.') }}</p></section>
    </div>
    <aside class="studio-form-aside"><section class="admin-card studio-form-section"><h3>{{ __('Hình ảnh sản phẩm') }}</h3><div class="studio-product-preview">@if($product->image_src)<img src="{{ $product->image_src }}" alt="{{ $product->name }}">@else<span><i class="fa-regular fa-image" aria-hidden="true"></i> {{ __('Chưa có hình ảnh') }}</span>@endif</div>@if($product->is_active)<a href="{{ route('perfumes.show', $product->id) }}" class="btn btn-outline-secondary btn-block mt-3" target="_blank" rel="noopener">{{ __('Xem trên cửa hàng') }} <i class="fa-solid fa-arrow-up-right-from-square ml-1" aria-hidden="true"></i></a>@endif</section></aside>
</div>
@endsection
