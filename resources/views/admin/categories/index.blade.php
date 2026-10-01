@extends('layouts.admin')

@section('title', 'Quản lý danh mục')
@section('page_title', 'Danh sách danh mục sản phẩm')

@section('content')
<div class="admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="font-weight-bold mb-1" style="color: #0f172a;">Tất cả danh mục</h5>
            <p class="text-muted mb-0" style="font-size:0.88rem;">Quản lý và phân loại các dòng nước hoa trong hệ thống</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-admin">
            <thead>
                <tr>
                    <th width="80">ID</th>
                    <th>Tên danh mục</th>
                    <th>Số sản phẩm</th>
                    <th>Ngày tạo</th>
                    <th width="220" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td class="font-weight-bold text-muted">#{{ $category->id }}</td>
                        <td>
                            <strong style="color: #0f172a; font-size:0.95rem;">{{ $category->name }}</strong>
                        </td>
                        <td>
                            <span class="badge badge-light px-2 py-1 font-weight-bold border">
                                {{ $category->perfumes_count ?? $category->perfumes()->count() }} sản phẩm
                            </span>
                        </td>
                        <td class="text-muted">{{ $category->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.categories.show', $category->id) }}" class="btn btn-outline-info btn-sm mr-1" title="Chi tiết">
                                <i class="fa-regular fa-eye"></i> Xem
                            </a>
                            <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-outline-warning btn-sm mr-1" title="Chỉnh sửa">
                                <i class="fa-regular fa-pen-to-square"></i> Sửa
                            </a>
                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Xóa">
                                    <i class="fa-regular fa-trash-can"></i> Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open mb-2" style="font-size:2rem; color:#cbd5e1; display:block;"></i>
                            Chưa có danh mục nào trong hệ thống.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
