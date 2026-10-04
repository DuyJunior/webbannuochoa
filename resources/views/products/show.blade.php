@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ $product->localized_name }}</h1>

    @if($product->image_url)
        <div class="my-3">
            <img src="{{ asset($product->image_url) }}" alt="{{ $product->localized_name }}" class="img-fluid" style="max-height: 300px;">
        </div>
    @endif

    <p>{{ $product->localized_description }}</p>
    <p>Quantity: {{ $product->quantity }}</p>
    <p>Price: {{ $product->price }}</p>
    <p>Category: {{ optional($product->category)->name }}</p>

    <a href="{{ route('welcome') }}" class="btn btn-secondary">Back to list</a>
</div>
@endsection
