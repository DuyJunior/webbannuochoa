{{-- The symbol is cropped from the approved signature, keeping every curve identical. --}}
<svg class="soopi-mark {{ $class ?? '' }}" width="{{ $size ?? 32 }}" height="{{ $size ?? 32 }}" viewBox="0 0 240 240" fill="none" focusable="false"
    @if($decorative ?? true) aria-hidden="true" @else role="img" aria-label="Soopi" @endif
    style="display:inline-block;vertical-align:-.15em;flex-shrink:0;{{ !empty($light) ? 'filter:brightness(0) invert(1);' : '' }}">
    <image href="{{ asset('images/brand/soopi-petal-mark.svg') }}" width="240" height="240" />
</svg>
