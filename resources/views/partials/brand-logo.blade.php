{{-- Shared approved petal-S signature. Keep the lettering with the emblem at every size. --}}
<img
    class="soopi-logo {{ $class ?? '' }}"
    src="{{ asset('images/brand/soopi-petal-logo.webp') }}"
    alt="Soopi · Perfume Studio"
    width="660"
    height="240"
    decoding="async"
    draggable="false"
    style="display:block;max-width:100%;height:auto;{{ !empty($light) ? 'filter:brightness(0) invert(1);' : '' }}"
>
