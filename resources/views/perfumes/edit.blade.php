@extends('layouts.store')

@section('title', __('Sửa ').$perfume->name.' · Soopi')

@section('content')
    <section class="store-container public-form-page">
        <div class="public-page-heading">
            <div>
                <a class="back-link" href="{{ route('perfumes.show', $perfume) }}">{{ __('← Quay lại sản phẩm') }}</a>
                <h1>{{ __('Sửa sản phẩm') }}</h1>
                <p>{{ $perfume->name }}</p>
            </div>
            @if ($perfume->image_src)
                <div class="public-edit-preview"><img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}"><span>{{ __('Ảnh hiện tại') }}</span></div>
            @endif
        </div>

        <p><a class="btn btn-secondary" href="{{ route('admin.products.edit', $perfume->id) }}#product-stock">{{ __('Quản lý tất cả dung tích và tồn kho') }}</a></p>
        <form method="POST" action="{{ route('perfumes.update', $perfume) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('perfumes._form')
        </form>
    </section>
@endsection
