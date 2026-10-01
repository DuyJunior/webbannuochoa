@extends('layouts.admin')
@section('title', 'Chi tiết sản phẩm')
@section('page_title', 'Chi tiết sản phẩm')
@section('content')
<div class="studio-list-heading mb-4"><div><span class="studio-form-kicker">SẢN PHẨM #{{ $product->id }}</span><h2>{{ $product->name }}</h2><p class="text-muted mb-0">{{ $product->brand }} · {{ optional($product->category)->name ?? 'Chưa phân loại' }}</p></div><div><a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary mr-2">Danh sách</a><a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary"><i class="fa-regular fa-pen-to-square mr-1" aria-hidden="true"></i> Chỉnh sửa</a></div></div>
<div class="studio-form-layout">
    <div>
        <section class="admin-card studio-form-section"><span class="studio-form-kicker">THÔNG TIN SẢN PHẨM</span><h3>Thông số & giá bán</h3>
            <dl class="studio-detail-grid">
                <div><dt>Giá bán hiện tại</dt><dd><strong class="h4 text-primary">{{ number_format($product->sale_price ?? $product->price, 0, ',', '.') }}₫</strong>@if($product->sale_price !== null && $product->sale_price < $product->price)<div class="text-muted small mt-1">Niêm yết <del>{{ number_format($product->price, 0, ',', '.') }}₫</del></div>@endif</dd></div>
                <div><dt>Trạng thái</dt><dd><span class="{{ $product->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $product->is_active ? 'Đang mở bán' : 'Tạm ẩn' }}</span></dd></div>
                <div><dt>Dành cho</dt><dd>{{ ['nam' => 'Nam', 'nu' => 'Nữ', 'unisex' => 'Unisex'][$product->gender] ?? $product->gender }}</dd></div>
                <div><dt>Nồng độ</dt><dd>{{ $product->concentration ?: 'Chưa cập nhật' }}</dd></div>
                <div><dt>Dung tích chai gốc</dt><dd>{{ $product->volume_ml }}ml</dd></div>
                <div><dt>Khối lượng tính phí</dt><dd>{{ $product->weight }}g</dd></div>
            </dl>
        </section>
        <section class="admin-card studio-form-section"><span class="studio-form-kicker">TỒN KHO THỰC TẾ</span><h3>Số lượng theo dung tích</h3><div class="row">
            @foreach([['Chai gốc '.$product->volume_ml.'ml', $product->stock], ['Mẫu thử 5ml', $product->stock_5ml], ['Chiết 10ml', $product->stock_10ml], ['Chai 50ml', $product->stock_50ml]] as [$label, $stock])
            <div class="col-6 col-lg-3 mb-3"><div class="border rounded p-3"><span class="text-muted small d-block mb-2">{{ $label }}</span><strong class="h3 {{ $stock > 5 ? 'text-success' : 'text-danger' }}">{{ number_format($stock) }}</strong><small class="text-muted"> chai</small><div class="small mt-2">{{ $stock === 0 ? 'Hết hàng' : ($stock <= 5 ? 'Sắp hết hàng' : 'Còn hàng') }}</div></div></div>
            @endforeach
        </div><p class="studio-field-help mb-0">Từng dung tích có số lượng riêng, không quy đổi tự động từ chai gốc.</p></section>
        <section class="admin-card studio-form-section"><h3>Mô tả sản phẩm</h3><p class="mb-0 text-muted" style="white-space:pre-line">{{ $product->description ?: 'Chưa có mô tả cho sản phẩm này.' }}</p></section>
    </div>
    <aside class="studio-form-aside"><section class="admin-card studio-form-section"><h3>Hình ảnh sản phẩm</h3><div class="studio-product-preview">@if($product->image_src)<img src="{{ $product->image_src }}" alt="{{ $product->name }}">@else<span><i class="fa-regular fa-image" aria-hidden="true"></i> Chưa có hình ảnh</span>@endif</div>@if($product->is_active)<a href="{{ route('perfumes.show', $product->id) }}" class="btn btn-outline-secondary btn-block mt-3" target="_blank" rel="noopener">Xem trên cửa hàng <i class="fa-solid fa-arrow-up-right-from-square ml-1" aria-hidden="true"></i></a>@endif</section></aside>
</div>
@endsection
