@extends('layouts.admin')

@section('title', $article->exists ? 'Sửa bài viết' : 'Viết bài mới')
@section('page_title', $article->exists ? 'Chỉnh sửa bài viết' : 'Soạn bài viết mới')

@section('content')
<div class="admin-article-form-page">
    <div class="admin-card">
        <div class="studio-page-intro align-items-center mb-4">
            <h2 class="h5 font-weight-bold mb-0">Thông tin bài viết cẩm nang</h2>
            <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Danh sách bài viết</a>
        </div>

        <form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}">
            @csrf
            @if($article->exists)
                @method('PUT')
            @endif

            <div class="form-group mb-3">
                <label for="article-title" class="form-label font-weight-bold small text-muted text-uppercase">
                    Tiêu đề bài viết <span class="text-danger">*</span>
                </label>
                <input id="article-title" class="form-control @error('title') is-invalid @enderror" name="title" maxlength="200" value="{{ old('title', $article->title) }}" placeholder="VD: Bí quyết lưu hương nước hoa suốt ngày dài..." required @error('title') aria-invalid="true" aria-describedby="article-title-error" @enderror>
                @error('title')<div class="invalid-feedback" id="article-title-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-3">
                <label for="article-excerpt" class="form-label font-weight-bold small text-muted text-uppercase">
                    Tóm tắt ngắn (Excerpt) <span class="text-danger">*</span>
                </label>
                <textarea id="article-excerpt" class="form-control @error('excerpt') is-invalid @enderror" name="excerpt" rows="3" maxlength="500" placeholder="Đoạn văn ngắn giới thiệu nội dung hiển thị ở danh sách bài..." required @error('excerpt') aria-invalid="true" aria-describedby="article-excerpt-error" @enderror>{{ old('excerpt', $article->excerpt) }}</textarea>
                @error('excerpt')<div class="invalid-feedback" id="article-excerpt-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-3">
                <label for="article-body" class="form-label font-weight-bold small text-muted text-uppercase">
                    Nội dung chi tiết <span class="text-danger">*</span>
                </label>
                <textarea id="article-body" class="form-control @error('body') is-invalid @enderror" name="body" rows="12" minlength="50" placeholder="Nội dung bài viết (ít nhất 50 ký tự). Mỗi đoạn cách nhau bằng một dòng trống." required @error('body') aria-invalid="true" aria-describedby="article-body-error" @enderror>{{ old('body', $article->body) }}</textarea>
                @error('body')<div class="invalid-feedback" id="article-body-error">{{ $message }}</div>@enderror
                <small class="text-muted mt-1 d-block">Mẹo: Mỗi đoạn văn cách nhau bằng một dòng trống để hiển thị đẹp trên trang web.</small>
            </div>

            <div class="form-group mb-3">
                <label for="article-image" class="form-label font-weight-bold small text-muted text-uppercase">
                    Đường dẫn ảnh đại diện
                </label>
                <input id="article-image" class="form-control @error('image_url') is-invalid @enderror" name="image_url" maxlength="255" value="{{ old('image_url', $article->image_url) }}" placeholder="VD: images/products/ten-anh.jpg" @error('image_url') aria-invalid="true" aria-describedby="article-image-error" @enderror>
                @error('image_url')<div class="invalid-feedback" id="article-image-error">{{ $message }}</div>@enderror
                <small class="text-muted mt-1 d-block">Nhập đường dẫn tương đối trong thư mục public của dự án.</small>
            </div>

            <div class="form-group mb-4">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" class="custom-control-input" id="isPublishedCheck" name="is_published" value="1" @checked(old('is_published', $article->is_published))>
                    <label class="custom-control-label font-weight-bold text-dark" for="isPublishedCheck" style="cursor: pointer;">
                        Xuất bản ngay cho khách hàng xem
                    </label>
                </div>
            </div>

            <div class="studio-form-actions d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="{{ route('admin.articles.index') }}" class="btn btn-light px-3">Hủy bỏ</a>
                <button class="btn btn-primary px-4 py-2 font-weight-bold" type="submit" style="border-radius: 8px;">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> {{ $article->exists ? 'Lưu thay đổi' : 'Tạo bài viết' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
