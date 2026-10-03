@php
    $bundlePrice = (int) ($perfume->sale_price ?? $perfume->price) + \App\Services\GiftBundleService::EXTRA_PRICE;
    $bundleAvailable = $bundleMainAvailable > 0 && $bundleSamples->count() >= 2;
    $selectedSamples = array_map(fn ($id) => is_scalar($id) ? (string) $id : '', (array) old('sample_ids', []));
    $bundleErrors = array_merge($errors->get('bundle'), $errors->get('sample_ids'), $errors->get('sample_ids.*'), old('sample_ids') ? $errors->get('quantity') : []);
@endphp
<section class="store-container gift-bundle" id="gift-bundle" aria-labelledby="gift-bundle-title">
    <div class="gift-bundle-panel">
        <div class="gift-bundle-story">
            <span class="gift-bundle-kicker">SOOPI / LE COFFRET</span>
            <h2 id="gift-bundle-title">{{ __('Một mùi hương quen.') }}<br><em>{{ __('Hai khám phá mới.') }}</em></h2>
            <p>Chai {{ $perfume->name }} {{ __('cùng hai mẫu thử bạn chọn, gói trong hộp quà và thiệp.') }}</p>
            <div class="gift-bundle-composition" aria-label="{{ __('Thành phần combo') }}">
                <figure><img src="{{ $perfume->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $perfume->name }}" width="180" height="180" loading="lazy"><figcaption>Chai {{ $perfume->volume_ml }}ml</figcaption></figure>
                <span aria-hidden="true">+</span>
                <div><span class="gift-bundle-vials" aria-hidden="true">@include('partials.icon', ['name' => 'vial', 'size' => 35]) @include('partials.icon', ['name' => 'vial', 'size' => 35])</span><small>{{ __('2 mẫu × 5ml') }}</small></div>
                <span aria-hidden="true">+</span>
                <div>@include('partials.icon', ['name' => 'gift', 'size' => 38])<small>{{ __('Hộp quà & thiệp') }}</small></div>
            </div>
        </div>
        <form class="gift-bundle-form" data-gift-bundle action="{{ route('cart.add-gift-bundle', $perfume) }}" method="POST">
            @csrf
            <input type="hidden" name="quantity" value="1">
            <div class="gift-bundle-form-heading"><span class="gift-bundle-kicker">{{ __('TỰ CHỌN HAI MÙI HƯƠNG') }}</span><span>01 + 02</span></div>
            <p class="gift-bundle-hint" id="gift-bundle-hint">{{ __('Chọn hai mùi khác nhau. Mỗi mẫu chiết có dung tích 5ml.') }}</p>
            @if($bundleErrors)
                <div class="gift-bundle-error" role="alert">@foreach($bundleErrors as $messages) @foreach((array) $messages as $message)<p>{{ $message }}</p>@endforeach @endforeach</div>
            @endif
            @foreach([0, 1] as $slot)
                <label class="gift-bundle-choice" for="bundle-sample-{{ $slot }}"><span>{{ __('MẪU') }} {{ $slot + 1 }} · 5ML</span>
                    <select id="bundle-sample-{{ $slot }}" name="sample_ids[]" required aria-describedby="gift-bundle-hint" @disabled(! $bundleAvailable)>
                        <option value="">{{ __('Chọn mùi hương thứ') }} {{ $slot + 1 }}</option>
                        @foreach($bundleSamples as $sample)<option value="{{ $sample->id }}" @selected((string) ($selectedSamples[$slot] ?? '') === (string) $sample->id)>{{ $sample->name }}</option>@endforeach
                    </select>
                </label>
            @endforeach
            <div class="gift-bundle-price"><div><span>{{ __('Giá trọn bộ') }}</span><strong>{{ number_format($bundlePrice, 0, ',', '.') }}₫</strong></div><p>{{ __('Đã gồm 2 mẫu thử, hộp quà & thiệp.') }}<br>{{ __('Phần combo thêm') }} {{ number_format(\App\Services\GiftBundleService::EXTRA_PRICE, 0, ',', '.') }}{{ __('₫ vào giá chai.') }}</p></div>
            @if(! $bundleAvailable)
                <p class="gift-bundle-unavailable" role="status">{{ $bundleMainAvailable < 1 ? __('Chai chính đã hết hoặc đã được chọn hết trong giỏ.') : __('Hiện chưa đủ hai mùi mẫu 5ml khác nhau để tạo combo.') }} {{ __('Bạn vẫn có thể khám phá các dung tích ở phần mua sản phẩm.') }}</p>
                <button class="ht-button ht-button-primary" type="submit" disabled>{{ __('Combo tạm chưa khả dụng') }}</button>
            @elseif(auth()->check())
                <button class="ht-button ht-button-primary" type="submit">{{ __('Thêm trọn bộ vào giỏ') }} @include('partials.icon', ['name'=>'arrow', 'size'=>18])</button>
            @else
                <a class="ht-button ht-button-primary" href="{{ route('perfumes.quick-view.login', $perfume) }}">{{ __('Đăng nhập để chọn combo') }} @include('partials.icon', ['name'=>'arrow', 'size'=>18])</a>
            @endif
            <small class="gift-bundle-footnote">{{ __('Thành phần và tổng tiền sẽ được hiển thị đầy đủ trong giỏ hàng.') }}</small>
        </form>
    </div>
</section>
