<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive"><meta name="referrer" content="no-referrer">
    <title>{{ __('Một món quà dành riêng cho bạn · Soopi') }}</title>
    @include('partials.brand-favicon')
    @vite(['resources/css/app.css', 'resources/js/gift-experience.js'])
</head>
<body class="gift-private">
    <header class="gift-private-header"><a href="{{ route('home') }}" aria-label="Soopi">@include('partials.brand-logo')</a>@include('partials.language-switcher', ['languageClass' => 'gift-language'])</header>
    <main id="main-content" class="gx">@yield('gift-content')</main>
    <footer class="gift-private-footer">SOOPI / A SCENT. A MEMORY.</footer>
    @include('partials.toasts')
</body>
</html>
