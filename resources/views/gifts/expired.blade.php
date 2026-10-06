@extends('gifts.layout')
@section('gift-content')
<section class="gx-lock">@include('partials.brand-mark', ['size' => 52])<h1>{{ __('Đã hết hạn') }}</h1><p>{{ __('Trang quà đã hết hạn. Vui lòng liên hệ người tặng.') }}</p></section>
@endsection
