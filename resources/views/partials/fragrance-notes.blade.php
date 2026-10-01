@php
    $editorial = $fragranceEditorial ?? \App\Services\FragranceEditorialService::forPerfume($perfume);
    $isPyramid = $editorial['mode'] === 'pyramid';
@endphp
<section class="fragrance-atelier" aria-labelledby="scent-story-title" data-fragrance-mode="{{ $editorial['mode'] }}">
    <div class="fragrance-story">
        <span class="atelier-kicker">SOOPI / L’ESSENCE DU PARFUM</span>
        <h2 id="scent-story-title">Une note.<br><em>Một dấu ấn riêng.</em></h2>
        <p class="fragrance-story-copy">{{ $editorial['story'] }}</p>
        @if($editorial['verified'])
            <p class="fragrance-occasion">{{ $editorial['occasion'] }}</p>
            <span class="fragrance-family">{{ $editorial['family'] }}</span>
        @else
            <a class="fragrance-advice" href="{{ config('storefront.zalo_url') }}" target="_blank" rel="noopener">Hỏi Soopi về chai nước hoa này ↗</a>
        @endif
    </div>
    @if($editorial['verified'])
        <div class="fragrance-notes" data-fragrance-notes>
            <div class="fragrance-notes-heading"><span>{{ $isPyramid ? 'BA TẦNG HƯƠNG' : 'NHỮNG NỐT HƯƠNG NỔI BẬT' }}</span><small>{{ $isPyramid ? 'Chạm cánh hoa để khám phá' : 'Một bản hòa hương, nhiều sắc thái' }}</small></div>
            @if($isPyramid)
                <div class="fragrance-petals" data-note-tabs aria-label="Khám phá ba tầng hương">
                    @foreach($editorial['layers'] as $key => $layer)
                        <button class="fragrance-petal" type="button" id="note-tab-{{ $key }}" data-note-tab="{{ $key }}" aria-controls="note-panel-{{ $key }}">
                            <span class="fragrance-petal-art" aria-hidden="true"></span><span class="fragrance-petal-number">0{{ $loop->iteration }}</span><strong>{{ $layer['label'] }}</strong>
                        </button>
                    @endforeach
                </div>
                <div class="fragrance-note-panels">
                    @foreach($editorial['layers'] as $key => $layer)
                        <div class="fragrance-note-panel" id="note-panel-{{ $key }}" data-note-panel="{{ $key }}" aria-labelledby="note-tab-{{ $key }}">
                            <span class="fragrance-note-caption">{{ $layer['label'] }}</span><h3>{{ implode(' · ', $layer['notes']) }}</h3><p>{{ $layer['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <ul class="fragrance-keynotes">
                    @foreach($editorial['key_notes'] as $note)
                        <li><span class="fragrance-keynote-petal" aria-hidden="true"></span><span><small>0{{ $loop->iteration }}</small>{{ $note }}</span></li>
                    @endforeach
                </ul>
                <p class="fragrance-keynotes-caption">Các nốt hương nổi bật do {{ $perfume->brand }} công bố cho phiên bản này.</p>
            @endif
            <div class="fragrance-sources"><span>Khám phá từ nhà hương</span>@foreach($editorial['sources'] as $source)<a href="{{ $source['url'] }}" target="_blank" rel="noopener">{{ $source['label'] }} ↗</a>@endforeach</div>
        </div>
    @endif
</section>
