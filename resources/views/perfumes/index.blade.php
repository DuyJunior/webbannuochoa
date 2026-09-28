@extends('layouts.store')

@section('title', 'Thêm và quản lý sản phẩm · Soopi')

@section('content')
    <section class="store-container public-manage-page">
        <div class="public-page-heading manage-heading">
            <div>
                <a class="back-link" href="{{ route('home') }}">← Quay lại cửa hàng</a>
                <h1>Quản lý sản phẩm</h1>
                <p>Danh sách sản phẩm nước hoa.</p>
            </div>
            <div class="manage-heading-actions">
                <a class="btn btn-secondary" href="{{ route('categories.index') }}">Danh mục</a>
                <a class="public-primary-button" href="{{ route('perfumes.create') }}">Thêm sản phẩm</a>
            </div>
        </div>

        <section class="panel public-manage-panel">
            <form class="filter-bar" method="GET" action="{{ route('perfumes.index') }}">
                <label class="search-field">
                    <span class="sr-only">Tìm kiếm</span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên hoặc thương hiệu...">
                </label>
                <select name="gender" aria-label="Lọc theo giới tính">
                    <option value="">Tất cả giới tính</option>
                    <option value="nam" @selected(request('gender') === 'nam')>Nước hoa nam</option>
                    <option value="nu" @selected(request('gender') === 'nu')>Nước hoa nữ</option>
                    <option value="unisex" @selected(request('gender') === 'unisex')>Nước hoa unisex</option>
                </select>
                <select name="status" aria-label="Lọc theo trạng thái">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Đang hiển thị</option>
                    <option value="hidden" @selected(request('status') === 'hidden')>Đang ẩn</option>
                </select>
                <button class="btn btn-secondary" type="submit">Lọc</button>
                @if (request()->hasAny(['search', 'gender', 'status']))
                    <a class="clear-filter" href="{{ route('perfumes.index') }}">Xóa lọc</a>
                @endif
            </form>

            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Sản phẩm</th><th>Danh mục</th><th>Giá bán</th><th>Tồn kho</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr></thead>
                    <tbody>
                        @forelse ($perfumes as $perfume)
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <div class="product-thumb">
                                            @if ($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}">@else<span>{{ mb_substr($perfume->brand, 0, 1) }}</span>@endif
                                        </div>
                                        <div><a href="{{ route('perfumes.show', $perfume) }}">{{ $perfume->name }}</a><small>{{ $perfume->brand }} · {{ $perfume->volume_ml }}ml</small></div>
                                    </div>
                                </td>
                                <td><strong class="table-primary">{{ $perfume->category?->name ?? 'Chưa phân loại' }}</strong><small class="table-secondary">{{ ['nam' => 'Nam', 'nu' => 'Nữ', 'unisex' => 'Unisex'][$perfume->gender] }}</small></td>
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
                                <td><span class="badge {{ $perfume->is_active ? 'badge-active' : 'badge-muted' }}">{{ $perfume->is_active ? 'Đang hiển thị' : 'Đang ẩn' }}</span></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('perfumes.show', $perfume) }}">Xem</a>
                                        <a href="{{ route('perfumes.edit', $perfume) }}">Sửa</a>
                                        <form method="POST" action="{{ route('perfumes.destroy', $perfume) }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?')">@csrf @method('DELETE')<button type="submit">Xóa</button></form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state"><span>◇</span><h2>Chưa có sản phẩm</h2><p>Hãy thêm sản phẩm đầu tiên vào bộ sưu tập.</p><a class="btn btn-primary" href="{{ route('perfumes.create') }}">+ Thêm sản phẩm</a></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($perfumes->hasPages())<div class="pagination-wrap">{{ $perfumes->links() }}</div>@endif
        </section>
    </section>
@endsection
