@extends('layouts.store')

@section('title', __('Thêm danh mục · Soopi'))

@section('content')
    <section class="store-container public-form-page">
        <div class="public-page-heading">
            <div>
                <a class="back-link" href="{{ route('categories.index') }}">{{ __('← Quay lại danh mục của tôi') }}</a>
                <h1>{{ __('Thêm danh mục') }}</h1>
                <p>{{ __('Nhập tên danh mục.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('categories.store') }}">@csrf @include('categories._form')</form>
    </section>
@endsection
