@extends('layouts.store')

@section('title', __('Thêm sản phẩm · Soopi'))

@section('content')
    <section class="store-container public-form-page">
        <div class="public-page-heading">
            <div>
                <a class="back-link" href="{{ route('perfumes.index') }}">{{ __('← Quay lại danh sách') }}</a>
                <h1>{{ __('Thêm sản phẩm') }}</h1>
                <p>{{ __('Nhập thông tin sản phẩm vào biểu mẫu bên dưới.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('perfumes.store') }}" enctype="multipart/form-data">
            @csrf
            @include('perfumes._form')
        </form>
    </section>
@endsection
