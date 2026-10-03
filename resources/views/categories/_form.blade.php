@php
    $editing = isset($category);
@endphp

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>{{ __('Vui lòng kiểm tra lại thông tin:') }}</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<section class="form-card category-form-card">
    <div class="form-card-heading">
        <h2>{{ __('Thông tin danh mục') }}</h2>
    </div>
    <div class="form-grid">
        <label class="field field-wide">
            <span>{{ __('Tên danh mục') }} <b>*</b></span>
            <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" maxlength="255" required autofocus placeholder="{{ __('Ví dụ: Nước hoa Niche') }}">
            <small>{{ __('Tên này sẽ xuất hiện trên trang chủ và bộ lọc sản phẩm.') }}</small>
            @error('name')<small class="field-error">{{ $message }}</small>@enderror
        </label>
    </div>
</section>

<div class="form-actions category-form-actions">
    <a class="btn btn-ghost" href="{{ $editing ? route('categories.show', $category) : route('categories.index') }}">{{ __('Hủy') }}</a>
    <button class="btn btn-primary" type="submit">{{ $editing ? __('Lưu thay đổi') : __('Thêm danh mục') }}</button>
</div>
