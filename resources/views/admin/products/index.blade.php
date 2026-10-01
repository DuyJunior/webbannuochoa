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
<section class="admin-card studio-directory">
    <form method="GET" action="{{ route('admin.products.index') }}" class="studio-directory-filters studio-product-filters">
        <div class="studio-search-field"><label for="product-search">Tìm sản phẩm</label><input id="product-search" class="form-control" type="search" name="search" placeholder="Tên nước hoa, thương hiệu…" value="{{ request('search') }}"></div>
        <div><label for="product-gender">Dành cho</label><select class="form-control" name="gender" id="product-gender"><option value="">Tất cả</option>@foreach(['nu' => 'Nữ', 'nam' => 'Nam', 'unisex' => 'Unisex'] as $key => $label)<option value="{{ $key }}" @selected(request('gender') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div><label for="product-status">Trạng thái</label><select class="form-control" name="status" id="product-status"><option value="">Tất cả trạng thái</option><option value="active" @selected(request('status') === 'active')>Đang mở bán</option><option value="inactive" @selected(request('status') === 'inactive')>Tạm ẩn</option></select></div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter mr-1" aria-hidden="true"></i> Áp dụng</button>
        @if(request()->anyFilled(['search', 'gender', 'status']))<a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Xóa bộ lọc</a>@endif
    </form>
    <div class="studio-list-heading"><h3>Bộ sưu tập <span>{{ number_format($products->total()) }}</span></h3><small>Giá bán · Tồn kho · Hiển thị</small></div>
    <div class="table-responsive">
        <table class="table table-hover table-admin studio-product-table">
            <thead>
                <tr>
                    <th class="studio-product-column">Sản phẩm</th>
                    <th>Giá niêm yết</th>
                    <th>Tồn kho</th>
                    <th>Trạng thái</th>
                    <th class="text-center studio-product-actions">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($product->image_url)
                                    <img src="{{ asset($product->image_url) }}" alt="{{ $product->name }}" style="width: 48px; height: 60px; object-fit: contain; border-radius: 8px; flex-shrink:0;" loading="lazy" class="mr-3 border">
                                @else
                                    <div class="mr-3 border rounded bg-light d-flex align-items-center justify-content-center text-muted" style="width: 42px; height: 42px;">
                                        <i class="fa-solid fa-image"></i>
                                    </div>
                                @endif
                                <div>
                                    <strong style="color:#0f172a;">{{ $product->name }}</strong>
                                    <div class="studio-product-meta">#{{ $product->id }} · {{ $product->brand }} · {{ optional($product->category)->name ?? 'Chưa phân loại' }}</div>
                                    <div class="text-muted" style="font-size:0.8rem;">
                                        {{ $product->volume_ml }}ml · {{ $product->weight ? $product->weight . 'g · ' : '' }}{{ ucfirst($product->gender) }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="font-weight-bold text-primary">
                            {{ number_format($product->price, 0, ',', '.') }}₫
                        </td>
                        <td>
                            <div class="d-flex flex-column" style="gap: 3px; font-size: 0.8rem; min-width: 0;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted"><i class="fa-solid fa-vial mr-1 text-info"></i>10ml:</span>
                                    <span class="badge {{ $product->stock_10ml > 5 ? 'badge-light border' : 'badge-danger' }} font-weight-bold ml-1">{{ $product->stock_10ml }} chai</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted"><i class="fa-solid fa-wine-bottle mr-1 text-primary"></i>50ml:</span>
                                    <span class="badge {{ $product->stock_50ml > 5 ? 'badge-light border' : 'badge-danger' }} font-weight-bold ml-1">{{ $product->stock_50ml }} chai</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted"><i class="fa-solid fa-box mr-1 text-success"></i>100ml:</span>
                                    <span class="badge {{ $product->stock > 5 ? 'badge-success' : 'badge-danger' }} ml-1">{{ $product->stock }} chai</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge-active"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> Đang bán</span>
                            @else
                                <span class="badge-inactive"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> Đã ẩn</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-outline-info btn-sm mr-1" title="Chi tiết">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-outline-warning btn-sm mr-1" title="Sửa">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Xóa">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open mb-2" style="font-size:2rem; color:#cbd5e1; display:block;"></i>
                            Không tìm thấy sản phẩm nào.
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
