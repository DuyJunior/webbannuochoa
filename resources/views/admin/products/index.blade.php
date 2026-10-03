@extends('layouts.admin')
@section('title', 'Bộ sưu tập nước hoa')
@section('page_title', 'Bộ sưu tập nước hoa')
@section('content')
<div class="studio-function-metrics">
    @foreach([
        ['Tổng sản phẩm', $stats['total'], 'spray-can-sparkles', 'Toàn bộ bộ sưu tập của cửa hàng'],
        ['Đang mở bán', $stats['active'], 'circle-check', 'Sẵn sàng đón khách trên website'],
        ['Cần kiểm tra tồn kho', $stats['low_stock'], 'box-open', 'Sản phẩm còn tối đa 5 chai gốc'],
    ] as [$label, $value, $icon, $note])
    <div class="studio-function-metric"><div><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong><small>{{ $note }}</small></div><i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i></div>
    @endforeach
</div>
<section class="admin-card studio-directory studio-catalog">
    <form method="GET" action="{{ route('admin.products.index') }}" class="studio-directory-filters studio-product-filters">
        <div class="studio-search-field"><label for="product-search">Tìm sản phẩm</label><input id="product-search" class="form-control" type="search" name="search" placeholder="Tên nước hoa, thương hiệu…" value="{{ request('search') }}"></div>
        <div><label for="product-gender">Dành cho</label><select class="form-control" name="gender" id="product-gender"><option value="">Tất cả</option>@foreach(['nu' => 'Nữ', 'nam' => 'Nam', 'unisex' => 'Unisex'] as $key => $label)<option value="{{ $key }}" @selected(request('gender') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div><label for="product-status">Trạng thái</label><select class="form-control" name="status" id="product-status"><option value="">Tất cả trạng thái</option><option value="active" @selected(request('status') === 'active')>Đang mở bán</option><option value="inactive" @selected(request('status') === 'inactive')>Tạm ẩn</option></select></div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter mr-1" aria-hidden="true"></i> Áp dụng</button>
        @if(request()->anyFilled(['search', 'gender', 'status']))<a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Xóa bộ lọc</a>@endif
    </form>
    <div class="studio-list-heading catalog-heading"><h3>Bộ sưu tập <span>{{ number_format($products->total()) }}</span></h3><div class="catalog-legend"><span><i class="is-low" aria-hidden="true"></i> Sắp hết: ≤ 5 chai</span><span><i class="is-empty" aria-hidden="true"></i> Hết hàng</span></div></div>
    <div class="catalog-table-wrap">
        <table class="table studio-product-table catalog-table">
            <caption class="sr-only">Danh sách sản phẩm, giá bán, số chai tồn theo dung tích và thao tác quản lý.</caption>
            <thead>
                <tr>
                    <th scope="col" class="catalog-product-column">Sản phẩm</th>
                    <th scope="col" class="catalog-price-column">Giá bán</th>
                    <th scope="col" class="catalog-stock-column">Tồn kho <span>· số chai</span></th>
                    <th scope="col" class="catalog-status-column">Hiển thị</th>
                    <th scope="col" class="catalog-actions-column">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @include('admin.products._catalog-row', ['product' => $product])
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open mb-2" style="font-size:2rem; color:#cbd5e1; display:block;"></i>
                            Không tìm thấy sản phẩm nào.
                            <div class="mt-3">@if(request()->anyFilled(['search', 'gender', 'status']))<a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Xóa bộ lọc</a>@else<a href="{{ route('admin.products.create') }}" class="btn btn-primary">Thêm sản phẩm đầu tiên</a>@endif</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
            <div class="text-muted small">
                Hiển thị <strong>{{ $products->firstItem() }}</strong> - <strong>{{ $products->lastItem() }}</strong> trong tổng số <strong>{{ $products->total() }}</strong> sản phẩm
            </div>
            <div>
                {{ $products->links() }}
            </div>
        </div>
    @endif
</section>
@endsection
