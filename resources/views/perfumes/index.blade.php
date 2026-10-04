@extends('layouts.store')

@section('title', __('Thêm và quản lý sản phẩm · Soopi'))

@section('content')
    <section class="store-container public-manage-page">
        <div class="public-page-heading manage-heading">
            <div>
                <a class="back-link" href="{{ route('home') }}">{{ __('← Quay lại cửa hàng') }}</a>
                <h1>{{ __('Quản lý sản phẩm') }}</h1>
                <p>{{ __('Danh sách sản phẩm nước hoa.') }}</p>
            </div>
            <div class="manage-heading-actions">
                <a class="btn btn-secondary" href="{{ route('categories.index') }}">{{ __('Danh mục') }}</a>
                <a class="public-primary-button" href="{{ route('perfumes.create') }}">{{ __('Thêm sản phẩm') }}</a>
            </div>
        </div>

        <section class="panel public-manage-panel">
            <form class="filter-bar" method="GET" action="{{ route('perfumes.index') }}">
                <label class="search-field">
                    <span class="sr-only">{{ __('Tìm kiếm') }}</span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Tìm theo tên hoặc thương hiệu...') }}">
                </label>
                <select name="gender" aria-label="{{ __('Lọc theo giới tính') }}">
                    <option value="">{{ __('Tất cả giới tính') }}</option>
                    <option value="nam" @selected(request('gender') === 'nam')>{{ __('Nước hoa nam') }}</option>
                    <option value="nu" @selected(request('gender') === 'nu')>{{ __('Nước hoa nữ') }}</option>
                    <option value="unisex" @selected(request('gender') === 'unisex')>{{ __('Nước hoa unisex') }}</option>
                </select>
                <select name="status" aria-label="{{ __('Lọc theo trạng thái') }}">
                    <option value="">{{ __('Tất cả trạng thái') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('Đang hiển thị') }}</option>
                    <option value="hidden" @selected(request('status') === 'hidden')>{{ __('Đang ẩn') }}</option>
                </select>
                <button class="btn btn-secondary" type="submit">{{ __('Lọc') }}</button>
                @if (request()->hasAny(['search', 'gender', 'status']))
                    <a class="clear-filter" href="{{ route('perfumes.index') }}">{{ __('Xóa lọc') }}</a>
                @endif
            </form>

            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>{{ __('Sản phẩm') }}</th><th>{{ __('Danh mục') }}</th><th>{{ __('Giá bán') }}</th><th>{{ __('Tồn kho') }}</th><th>{{ __('Trạng thái') }}</th><th class="text-right">{{ __('Thao tác') }}</th></tr></thead>
                    <tbody>
                        @forelse ($perfumes as $perfume)
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <div class="product-thumb">
                                            @if ($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="{{ $perfume->localized_name }}">@else<span>{{ mb_substr($perfume->brand, 0, 1) }}</span>@endif
                                        </div>
                                        <div><a href="{{ route('perfumes.show', $perfume) }}">{{ $perfume->localized_name }}</a><small>{{ $perfume->brand }} · {{ $perfume->volume_ml }}ml</small></div>
                                    </div>
                                </td>
                                <td><strong class="table-primary">{{ __($perfume->category?->localized_name ?? __('Chưa phân loại')) }}</strong><small class="table-secondary">{{ ['nam' => __('Nam'), 'nu' => __('Nữ'), 'unisex' => 'Unisex'][$perfume->gender] }}</small></td>
                                <td>
                                    <strong class="price">{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong>
                                    @if ($perfume->sale_price !== null)<del>{{ number_format((float) $perfume->price, 0, ',', '.') }}₫</del>@endif
                                </td>
                                <td>
                                    <div style="font-size: 11.5px; line-height: 1.4;">
                                        <div>10ml: <strong>{{ $perfume->stock_10ml }}</strong></div>
                                        <div>50ml: <strong>{{ $perfume->stock_50ml }}</strong></div>
                                        <div>100ml: <strong>{{ $perfume->stock }}</strong></div>
                                    </div>
                                </td>
                                <td><span class="badge {{ $perfume->is_active ? 'badge-active' : 'badge-muted' }}">{{ $perfume->is_active ? __('Đang hiển thị') : __('Đang ẩn') }}</span></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('perfumes.show', $perfume) }}">Xem</a>
                                        <a href="{{ route('perfumes.edit', $perfume) }}">{{ __('Sửa') }}</a>
                                        <form method="POST" action="{{ route('perfumes.destroy', $perfume) }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?')">@csrf @method('DELETE')<button type="submit">{{ __('Xóa') }}</button></form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state"><span>◇</span><h2>{{ __('Chưa có sản phẩm') }}</h2><p>{{ __('Hãy thêm sản phẩm đầu tiên vào bộ sưu tập.') }}</p><a class="btn btn-primary" href="{{ route('perfumes.create') }}">{{ __('+ Thêm sản phẩm') }}</a></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($perfumes->hasPages())<div class="pagination-wrap">{{ $perfumes->links() }}</div>@endif
        </section>
    </section>
@endsection
