@extends('layouts.store')

@section('title', 'Sửa '.$category->name.' · Soopi')

@section('content')
    <section class="store-container public-form-page">
        <div class="public-page-heading">
            <div>
                <a class="back-link" href="{{ route('categories.show', $category) }}">← Quay lại chi tiết</a>
                <h1>Sửa danh mục</h1>
                <p>{{ $category->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('categories.update', $category) }}">@csrf @method('PUT') @include('categories._form')</form>
    </section>
@endsection
