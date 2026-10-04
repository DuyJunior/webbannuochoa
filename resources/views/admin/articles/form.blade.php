@extends('layouts.admin')

@section('title', $article->exists ? __('Sửa bài viết') : __('Viết bài mới'))
@section('page_title', $article->exists ? __('Chỉnh sửa bài viết') : __('Soạn bài viết mới'))

@section('content')
<div class="admin-article-form-page">
    <div class="admin-card">
        <div class="studio-page-intro align-items-center mb-4">
            <h2 class="h5 font-weight-bold mb-0">{{ __('Thông tin bài viết cẩm nang') }}</h2>
            <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> {{ __('Danh sách bài viết') }}</a>
        </div>

        <form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}">
            @csrf
            @if($article->exists)
                @method('PUT')
            @endif

            <div class="form-group mb-3">
                <label for="article-title" class="form-label font-weight-bold small text-muted text-uppercase">
                    {{ __('Tiêu đề bài viết') }} <span class="text-danger">*</span>
                </label>
                <input id="article-title" class="form-control @error('title') is-invalid @enderror" name="title" maxlength="200" value="{{ old('title', $article->title) }}" placeholder="{{ __('VD: Bí quyết lưu hương nước hoa suốt ngày dài...') }}" required @error('title') aria-invalid="true" aria-describedby="article-title-error" @enderror>
                @error('title')<div class="invalid-feedback" id="article-title-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-3">
                <label for="article-excerpt" class="form-label font-weight-bold small text-muted text-uppercase">
                    {{ __('Tóm tắt ngắn (Excerpt)') }} <span class="text-danger">*</span>
                </label>
                <textarea id="article-excerpt" class="form-control @error('excerpt') is-invalid @enderror" name="excerpt" rows="3" maxlength="500" placeholder="{{ __('Đoạn văn ngắn giới thiệu nội dung hiển thị ở danh sách bài...') }}" required @error('excerpt') aria-invalid="true" aria-describedby="article-excerpt-error" @enderror>{{ old('excerpt', $article->excerpt) }}</textarea>
                @error('excerpt')<div class="invalid-feedback" id="article-excerpt-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-3">
                <label for="article-body" class="form-label font-weight-bold small text-muted text-uppercase">
                    {{ __('Nội dung chi tiết') }} <span class="text-danger">*</span>
                </label>
                <textarea id="article-body" class="form-control @error('body') is-invalid @enderror" name="body" rows="12" minlength="50" placeholder="{{ __('Nội dung bài viết (ít nhất 50 ký tự). Mỗi đoạn cách nhau bằng một dòng trống.') }}" required @error('body') aria-invalid="true" aria-describedby="article-body-error" @enderror>{{ old('body', $article->body) }}</textarea>
                @error('body')<div class="invalid-feedback" id="article-body-error">{{ $message }}</div>@enderror
                <small class="text-muted mt-1 d-block">{{ __('Mẹo: Mỗi đoạn văn cách nhau bằng một dòng trống để hiển thị đẹp trên trang web.') }}</small>
            </div>

            <div class="form-group mb-3">
                <label for="article-image" class="form-label font-weight-bold small text-muted text-uppercase">
                    {{ __('Đường dẫn ảnh đại diện') }}
                </label>
                <input id="article-image" class="form-control @error('image_url') is-invalid @enderror" name="image_url" maxlength="255" value="{{ old('image_url', $article->image_url) }}" placeholder="VD: images/products/ten-anh.jpg" @error('image_url') aria-invalid="true" aria-describedby="article-image-error" @enderror>
                @error('image_url')<div class="invalid-feedback" id="article-image-error">{{ $message }}</div>@enderror
                <small class="text-muted mt-1 d-block">{{ __('Nhập đường dẫn tương đối trong thư mục public của dự án.') }}</small>
            </div>

            @include('partials.english-content-fields', ['englishRecord' => $article, 'englishFields' => [
                'title_en' => ['label' => 'Tiêu đề tiếng Anh', 'max' => 200],
                'excerpt_en' => ['label' => 'Tóm tắt tiếng Anh', 'rows' => 3, 'max' => 500],
                'body_en' => ['label' => 'Nội dung bài viết tiếng Anh', 'rows' => 12],
            ]])
            <div class="form-group mb-4">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" class="custom-control-input" id="isPublishedCheck" name="is_published" value="1" @checked(old('is_published', $article->is_published))>
                    <label class="custom-control-label font-weight-bold text-dark" for="isPublishedCheck" style="cursor: pointer;">
                        {{ __('Xuất bản ngay cho khách hàng xem') }}
                    </label>
                </div>
            </div>

            <div class="studio-form-actions d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="{{ route('admin.articles.index') }}" class="btn btn-light px-3">{{ __('Hủy bỏ') }}</a>
                <button class="btn btn-primary px-4 py-2 font-weight-bold" type="submit" style="border-radius: 8px;">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> {{ $article->exists ? __('Lưu thay đổi') : __('Tạo bài viết') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
