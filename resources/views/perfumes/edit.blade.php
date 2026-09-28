@extends('layouts.store')

@section('title', 'Sửa '.$perfume->name.' · Soopi')

@section('content')
    <section class="store-container public-form-page">
        <div class="public-page-heading">
            <div>
                <a class="back-link" href="{{ route('perfumes.show', $perfume) }}">← Quay lại sản phẩm</a>
                <h1>Sửa sản phẩm</h1>
                <p>{{ $perfume->name }}</p>
            </div>
            @if ($perfume->image_src)
                <div class="public-edit-preview"><img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}"><span>Ảnh hiện tại</span></div>
            @endif
        </div>

        <form method="POST" action="{{ route('perfumes.update', $perfume) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('perfumes._form')
        </form>
    </section>
@endsection
