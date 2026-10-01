@extends('layouts.admin')

@section('title', 'Cẩm nang nước hoa')
@section('page_title', 'Quản lý bài viết & Cẩm nang')

@section('content')
<div class="admin-articles-page">

    <div class="admin-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h5 class="font-weight-bold mb-1" style="color: #0f172a;">Danh sách bài viết</h5>
                <p class="text-muted mb-0 small">Chia sẻ kiến thức chọn mùi, bảo quản và phong cách sử dụng nước hoa</p>
            </div>
            <a href="{{ route('admin.articles.create') }}" class="btn btn-primary px-3 py-2 font-weight-bold" style="border-radius: 8px;">
                <i class="fa-solid fa-pen-nib mr-1"></i> Viết bài mới
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th width="320">Tiêu đề bài viết</th>
                        <th>Trích dẫn</th>
                        <th width="140" class="text-center">Trạng thái</th>
                        <th width="130">Ngày tạo</th>
                        <th width="140" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td>
                                <strong style="color: #0f172a; font-size: 0.95rem;">{{ $article->title }}</strong>
                            </td>
                            <td>
                                <p class="text-muted small mb-0 text-truncate" style="max-width: 380px;">{{ $article->excerpt }}</p>
                            </td>
                            <td class="text-center">
                                @if($article->is_published)
                                    <span class="badge-active"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> Đã xuất bản</span>
                                @else
                                    <span class="badge-inactive"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> Bản nháp</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $article->created_at->format('d/m/Y') }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-outline-warning btn-sm mr-1" title="Chỉnh sửa">
                                    <i class="fa-solid fa-pen-to-square"></i> Sửa
                                </a>
                                <form class="d-inline" method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit" title="Xóa">
                                        <i class="fa-solid fa-trash-can"></i> Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-book-open mb-2" style="font-size: 2rem; color: #cbd5e1; display: block;"></i>
                                Chưa có bài viết cẩm nang nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($articles->hasPages())
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                <div class="text-muted small">
                    Hiển thị <strong>{{ $articles->firstItem() }}</strong> - <strong>{{ $articles->lastItem() }}</strong> trong tổng số <strong>{{ $articles->total() }}</strong> bài viết
                </div>
                <div>
                    {{ $articles->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
