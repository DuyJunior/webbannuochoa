@extends('layouts.admin')

@section('title', __('Cẩm nang nước hoa'))
@section('page_title', __('Quản lý bài viết & Cẩm nang'))

@section('content')
<div class="admin-articles-page">

    <div class="admin-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h5 class="font-weight-bold mb-1" style="color: #0f172a;">{{ __('Danh sách bài viết') }}</h5>
                <p class="text-muted mb-0 small">{{ __('Chia sẻ kiến thức chọn mùi, bảo quản và phong cách sử dụng nước hoa') }}</p>
            </div>
            <a href="{{ route('admin.articles.create') }}" class="btn btn-primary px-3 py-2 font-weight-bold" style="border-radius: 8px;">
                <i class="fa-solid fa-pen-nib mr-1"></i> {{ __('Viết bài mới') }}
            </a>
        </div>

        <form method="GET" action="{{ route('admin.articles.index') }}" class="studio-filter-bar mb-4">
            <div class="form-row align-items-end">
                <div class="col-md-6 mb-2"><label for="article-search" class="small font-weight-bold">{{ __('Tìm bài viết') }}</label><input id="article-search" class="form-control" type="search" name="search" maxlength="200" value="{{ request('search') }}" placeholder="{{ __('Tiêu đề hoặc tóm tắt…') }}"></div>
                <div class="col-md-3 mb-2"><label for="article-status" class="small font-weight-bold">{{ __('Trạng thái') }}</label><select id="article-status" class="form-control" name="status"><option value="">{{ __('Tất cả trạng thái') }}</option><option value="published" @selected(request('status') === 'published')>{{ __('Đã xuất bản') }}</option><option value="draft" @selected(request('status') === 'draft')>{{ __('Bản nháp') }}</option></select></div>
                <div class="col-md-3 mb-2 d-flex gap-2"><button class="btn btn-primary" type="submit">{{ __('Lọc bài viết') }}</button>@if(request()->filled('search') || request()->filled('status'))<a class="btn btn-outline-secondary" href="{{ route('admin.articles.index') }}">{{ __('Xóa lọc') }}</a>@endif</div>
            </div>
        </form>
        <p class="studio-result-count text-muted small">{{ number_format($articles->total()) }} {{ __('bài viết') }}{{ request()->filled('search') || request()->filled('status') ? __(' phù hợp') : '' }}</p>

        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th width="320">{{ __('Tiêu đề bài viết') }}</th>
                        <th>{{ __('Trích dẫn') }}</th>
                        <th width="140" class="text-center">{{ __('Trạng thái') }}</th>
                        <th width="130">{{ __('Ngày tạo') }}</th>
                        <th width="140" class="text-center">{{ __('Thao tác') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td>
                                <strong style="color: #0f172a; font-size: 0.95rem;">{{ $article->localized_title }}</strong>
                            </td>
                            <td>
                                <p class="text-muted small mb-0 text-truncate" style="max-width: 380px;">{{ $article->localized_excerpt }}</p>
                            </td>
                            <td class="text-center">
                                @if($article->is_published)
                                    <span class="badge-active"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> {{ __('Đã xuất bản') }}</span>
                                @else
                                    <span class="badge-inactive"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> {{ __('Bản nháp') }}</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $article->created_at->format('d/m/Y') }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-outline-warning btn-sm mr-1" title="{{ __('Chỉnh sửa') }}">
                                    <i class="fa-solid fa-pen-to-square"></i> {{ __('Sửa') }}
                                </a>
                                <form class="d-inline" method="POST" action="{{ route('admin.articles.destroy', $article) }}" data-confirm="Xóa bài viết {{ $article->localized_title }}? Nội dung đã xóa sẽ không thể khôi phục.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit" title="{{ __('Xóa') }}" aria-label="Xóa bài viết {{ $article->localized_title }}">
                                        <i class="fa-solid fa-trash-can"></i> {{ __('Xóa') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-book-open mb-2" style="font-size: 2rem; color: #cbd5e1; display: block;"></i>
                                <strong class="d-block">{{ request()->filled('search') || request()->filled('status') ? __('Không tìm thấy bài viết phù hợp') : __('Chưa có bài viết cẩm nang nào') }}</strong>
                                <span class="d-block small mt-2">{{ request()->filled('search') || request()->filled('status') ? __('Thử từ khóa khác hoặc xóa bộ lọc để xem tất cả bài viết.') : __('Viết bài đầu tiên để chia sẻ kiến thức về nước hoa với khách hàng.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($articles->total())
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                <div class="text-muted small">
                    {{ __('Hiển thị') }} <strong>{{ $articles->firstItem() }}</strong> - <strong>{{ $articles->lastItem() }}</strong> {{ __('trong tổng số') }} <strong>{{ $articles->total() }}</strong> {{ __('bài viết') }}
                </div>
                <div>
                    {{ $articles->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
