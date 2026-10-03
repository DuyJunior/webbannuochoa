@extends('layouts.store')

@section('title', $category->name.' · Soopi')

@section('content')
    <section class="store-container public-manage-page">
        <div class="public-page-heading manage-heading">
            <div>
                <a class="back-link" href="{{ route('categories.index') }}">{{ __('← Quay lại danh mục của tôi') }}</a>
                <span class="section-kicker">{{ __('Chi tiết danh mục') }}</span>
                <h1>{{ __($category->name) }}</h1>
                <p>{{ $category->perfumes->count() }} {{ __('sản phẩm đang thuộc danh mục này.') }}</p>
            </div>
            <div class="manage-heading-actions">
                <a class="btn btn-secondary" href="{{ route('home', ['category' => $category->id]) }}#san-pham">{{ __('Xem trên cửa hàng') }}</a>
                <a class="public-primary-button" href="{{ route('categories.edit', $category) }}">{{ __('Sửa danh mục') }}</a>
            </div>
        </div>

        <section class="panel category-detail-panel">
            <div class="category-detail-heading">
                <span class="category-avatar category-avatar-large">{{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                <div><span class="section-kicker">{{ __('Mã #') }}{{ str_pad((string) $category->id, 3, '0', STR_PAD_LEFT) }}</span><h2>{{ __($category->name) }}</h2><p>{{ __('Được tạo ngày') }} {{ $category->created_at?->format('d/m/Y') }}</p></div>
            </div>
            <div class="category-products-heading"><h3>{{ __('Sản phẩm trong danh mục') }}</h3><span>{{ $category->perfumes->count() }} {{ __('sản phẩm') }}</span></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>{{ __('Sản phẩm') }}</th><th>{{ __('Giá bán') }}</th><th>{{ __('Trạng thái') }}</th><th class="text-right">{{ __('Thao tác') }}</th></tr></thead>
                    <tbody>
                        @forelse ($category->perfumes as $perfume)
                            <tr>
                                <td><div class="product-cell"><div class="product-thumb">@if ($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}">@else<span>{{ mb_substr($perfume->brand, 0, 1) }}</span>@endif</div><div><a href="{{ route('perfumes.show', $perfume) }}">{{ $perfume->name }}</a><small>{{ $perfume->brand }} · {{ $perfume->volume_ml }}ml</small></div></div></td>
                                <td><strong class="price">{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong></td>
                                <td><span class="badge {{ $perfume->is_active ? 'badge-active' : 'badge-muted' }}">{{ $perfume->is_active ? __('Đang hiển thị') : __('Đang ẩn') }}</span></td>
                                <td><div class="row-actions"><a href="{{ route('perfumes.show', $perfume) }}">Xem</a><a href="{{ route('perfumes.edit', $perfume) }}">{{ __('Sửa') }}</a></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state"><span>◇</span><h2>{{ __('Chưa có sản phẩm') }}</h2><p>{{ __('Danh mục này chưa được gán cho sản phẩm nào.') }}</p><a class="btn btn-primary" href="{{ route('perfumes.create') }}">{{ __('+ Thêm sản phẩm') }}</a></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
