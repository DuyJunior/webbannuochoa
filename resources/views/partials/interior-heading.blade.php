<header class="interior-heading">
    <div class="interior-heading-copy">
        <a class="interior-back" href="{{ route('home') }}#san-pham">← Trở về bộ sưu tập</a>
        <span class="interior-kicker">SOOPI / {{ $eyebrow }}</span>
        <h1>{{ $heading }}<br><em>{{ $accent }}</em></h1>
        <p>{{ $description }}</p>
    </div>
    <div class="interior-heading-art" aria-hidden="true">
        <img src="{{ asset($art) }}" alt="" width="640" height="640" fetchpriority="high">
        <span>THE ART OF SCENT</span>
    </div>
</header>
