@extends('layouts.user')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Welcome to Our Store</h1>
    <div class="card-deck row">
        @foreach ($products as $product)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-between">
                        <div>
                            @if($product->image_url)
                                <img src="{{ asset($product->image_url) }}" alt="{{ $product->localized_name }}" class="card-img-top mb-3" style="max-height: 150px; object-fit: contain;">
                            @endif
                            <h5 class="card-title">{{ $product->localized_name }}</h5>
                            <p class="card-text">{{ Str::limit($product->localized_description, 80) }}</p>
                        </div>
                        <div class="mt-3">
                            <p class="card-text mb-1">Quantity: {{ $product->quantity }}</p>
                            <p class="card-text mb-2">Price: ${{ number_format($product->price, 2) }}</p>
                            <p class="card-text mb-3 text-muted">Category: {{ optional($product->category)->name }}</p>
                            <a href="{{ route('products.show', $product->id) }}" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!-- Hiển thị liên kết phân trang -->
    <div class="d-flex justify-content-center">
        {{ $products->links() }}
    </div>
</div>
@endsection
