<section class="store-container soopi-sampling sampling-atelier" id="khoang-thu-huong" aria-labelledby="sampling-title">
    <header class="sampling-heading">
        <div><span class="atelier-kicker">SOOPI / L’ATELIER DES ESSAIS</span><h2 id="sampling-title">Thử một chút.<br><em data-typewriter>Yêu thật lâu.</em></h2></div>
        <div class="sampling-intro"><span class="sampling-edition">THE DISCOVERY RITUAL / 03</span><p>Mùi hương đẹp nhất,<br>là mùi hương <em>hợp với bạn.</em></p><span>Chọn 3 hoặc 5 mẫu · Mỗi mẫu 5 ml · Cảm nhận trên da</span></div>
    </header>
    <form class="sampling-layout" action="{{ route('store.discovery-box') }}" method="GET" data-sample-form data-vial-src="{{ asset('images/atelier/sample-vial.svg') }}?v=ivory">
        <aside class="sample-workbench" id="sample-workbench" aria-label="Hộp thử mùi của bạn">
            <div class="sample-box-signature"><span>SOOPI / HỘP THỬ MÙI</span>@include('partials.brand-mark', ['size' => 30])</div>
            <div class="sample-package">
                <h3>Một hộp hương.<br><em>Mang dấu ấn bạn.</em></h3>
                <fieldset class="sample-size-options"><legend>Chọn số mẫu trong hộp</legend>
                    <label><input type="radio" name="size" value="3" checked><span><strong>03 mẫu</strong><small>199.000₫</small></span></label>
                    <label><input type="radio" name="size" value="5"><span><strong>05 mẫu</strong><small>299.000₫</small></span></label>
                </fieldset>
            </div>
            <div class="sample-display">
                <div class="sample-display-caption"><span>TUYỂN CHỌN CỦA BẠN</span><span>5 ML / MẪU</span></div>
                <div class="sample-tray" data-sample-tray aria-label="Các mẫu đã chọn">
                    @for($slot = 1; $slot <= 3; $slot++)
                        <div class="sample-slot">
                            <span class="sample-vial" aria-hidden="true"><img src="{{ asset('images/atelier/sample-vial.svg') }}?v=ivory" width="140" height="360" alt=""><span class="sample-vial-label"><b>SOOPI</b><small>0{{ $slot }}</small><span>5 ML</span></span></span>
                            <span class="sample-slot-name">Mùi hương 0{{ $slot }}</span>
                        </div>
                    @endfor
                </div>
                <p class="sample-display-note">Một chút để thử.<br><em>Một mùi để nhớ.</em></p>
            </div>
            <div class="sample-box-progress"><span data-sample-status role="status" aria-live="polite">0/3 mẫu đã chọn</span><span data-sample-remaining>Thêm 3 mùi hương</span><div class="sample-progress-track" aria-hidden="true"><i data-sample-progress></i></div></div>
            <div class="sample-checkout">
                <div class="sample-total"><span>Hộp thử của bạn</span><strong data-sample-price>199.000₫</strong></div>
                <button class="atelier-button sample-complete" type="submit"><span>Tiếp tục chọn hộp thử</span><span aria-hidden="true">↗</span></button>
                <small>Bạn có thể xem lại và đổi mẫu ở bước tiếp theo.</small>
            </div>
            <a class="sample-quiz-link" href="{{ route('store.quiz') }}">Chưa biết chọn? Tìm gu hương của bạn <span aria-hidden="true">↗</span></a>
        </aside>
        <div class="sampling-catalog">
            <div class="sampling-catalog-heading"><div><span class="atelier-kicker">TỰ TAY TUYỂN CHỌN</span><h3>Những mùi hương bạn tò mò.</h3></div><a href="#sample-workbench" class="sample-view-tray"><span data-sample-count>0/3</span> trong hộp ↑</a></div>
            @if($sampleCandidates->isNotEmpty() && $sampleCandidates->every(fn ($item) => $item->getStockForVolume(5) < 1))
                <p class="sampling-stock-notice">Các mẫu 5 ml hiện đang tạm hết. Bạn vẫn có thể xem nhanh từng mùi hương hoặc <a href="{{ config('storefront.zalo_url') }}" target="_blank" rel="noopener">nhắn Soopi để hỏi khi có mẫu ↗</a>.</p>
            @endif
            <div class="sample-picks">
                @forelse($sampleCandidates as $sample)
                    @php
                        $sampleAvailable = ($homeSampleAvailability[$sample->id] ?? 0) > 0;
                        $unavailableReason = $sample->getStockForVolume(5) > 0 ? 'Đã đủ trong giỏ' : 'Mẫu 5 ml tạm hết';
                    @endphp
                    <article class="sample-card" data-sample-card>
                        <label class="sample-pick" @unless($sampleAvailable) data-unavailable @endunless>
                            <input type="checkbox" name="samples[]" value="{{ $sample->id }}" data-sample-name="{{ $sample->name }}" data-sample-img="{{ $sample->image_src }}" data-sample-unavailable-reason="{{ $unavailableReason }}" aria-label="Chọn mẫu 5 ml {{ $sample->name }}" @disabled(!$sampleAvailable)>
                            <span class="sample-photo">@if($sample->image_src)<img src="{{ $sample->image_src }}" alt="" width="260" height="300" loading="lazy" decoding="async">@else<span class="sample-photo-fallback">@include('partials.brand-mark', ['size' => 56])</span>@endif<span class="sample-choice-mark" aria-hidden="true">+</span><span class="sample-volume">5 ml</span></span>
                            <span class="sample-brand">{{ $sample->brand }}</span><strong title="{{ $sample->name }}">{{ $sample->name }}</strong>
                            <span class="sample-select-label"><span data-sample-action>{{ $sampleAvailable ? 'Thêm vào hộp' : $unavailableReason }}</span><span data-sample-order aria-hidden="true">{{ $sampleAvailable ? '+' : '—' }}</span></span>
                        </label>
                        <a class="sample-detail" href="{{ route('perfumes.show', $sample) }}" data-quick-view="{{ route('perfumes.quick-view', $sample) }}" data-product-name="{{ $sample->name }}" aria-haspopup="dialog" aria-controls="product-quick-view" aria-label="Xem nhanh {{ $sample->name }}">Khám phá mùi hương <span aria-hidden="true">↗</span></a>
                    </article>
                @empty
                    <p class="sampling-empty">Bộ mẫu đang được bổ sung. <a href="{{ config('storefront.zalo_url') }}" target="_blank" rel="noopener">Nhắn Soopi để được tư vấn ↗</a></p>
                @endforelse
            </div>
            <div class="sampling-catalog-foot"><span>Chọn bằng cảm xúc. Quyết định bằng trải nghiệm.</span><a class="atelier-link" href="{{ route('store.discovery-box') }}">Xem toàn bộ mẫu hương ↗</a></div>
        </div>
        <noscript><p>Các mẫu đã tích sẽ được chuyển sang trang hoàn thiện hộp thử. Bạn có thể đổi số lượng và xem lại lựa chọn tại đó.</p></noscript>
    </form>
</section>
