@php
    $fieldKey = $fieldKey ?? 'product';
    $formReview = $formReview ?? null;
    $restoreReview = $restoreReview ?? false;
    $ratingValue = $restoreReview ? old('rating') : $formReview?->rating;
    $ratingValue = is_scalar($ratingValue) ? (int) $ratingValue : 0;
    $bodyValue = $restoreReview ? old('body') : $formReview?->body;
    $bodyValue = is_string($bodyValue) ? $bodyValue : '';
    $tagValues = $restoreReview ? old('tags', []) : ($formReview?->tags ?? []);
    $tagValues = is_array($tagValues) ? $tagValues : [];
@endphp
<div class="rv-fields" data-review-fields>
    <fieldset class="rv-rating"><legend>{{ __('Đánh giá của bạn') }}</legend>
        <div class="rv-stars-input" data-star-picker>
            @for($star = 1; $star <= 5; $star++)
                <label class="rv-star-choice" data-star="{{ $star }}">
                    <input type="radio" name="rating" value="{{ $star }}" @checked($ratingValue === $star) required aria-label="{{ __(':rating trên 5 sao', ['rating' => $star]) }}">
                    <span aria-hidden="true">★</span>
                </label>
            @endfor
        </div>
        <span class="rv-muted">{{ __('Chạm vào sao để chấm điểm') }}</span>
    </fieldset>
    <fieldset class="rv-tag-picker"><legend>{{ __('Điều bạn yêu thích') }} <small>({{ __('Không bắt buộc') }})</small></legend>
        <div class="rv-tags">@foreach(\App\Models\PerfumeReview::TAGS as $value => $label)
            <label><input type="checkbox" name="tags[]" value="{{ $value }}" @checked(in_array($value, $tagValues, true))><span>{{ __($label) }}</span></label>
        @endforeach</div>
    </fieldset>
    <label class="rv-field-label" for="review-body-{{ $fieldKey }}">{{ __('Cảm nhận') }}</label>
    <textarea id="review-body-{{ $fieldKey }}" name="body" minlength="10" maxlength="2000" rows="4" required placeholder="{{ __('Bạn cảm nhận mùi hương như thế nào?') }}">{{ $bodyValue }}</textarea>
    <small class="rv-muted">{{ __('Từ 10 đến 2.000 ký tự. Chia sẻ trải nghiệm thật của bạn.') }}</small>
    @if($formReview && count($formReview->photos))
        <div class="rv-photos">@foreach($formReview->photos as $photo)<a href="{{ asset($photo) }}" target="_blank" rel="noopener"><img src="{{ asset($photo) }}" alt="{{ __('Ảnh do khách hàng chia sẻ') }}" loading="lazy"></a>@endforeach</div>
        <small class="rv-muted">{{ __('Chọn ảnh mới để thay bộ ảnh hiện tại; để trống để giữ ảnh cũ.') }}</small>
    @endif
    <div class="rv-upload" data-review-upload data-error="{{ __('Chọn tối đa 4 ảnh, mỗi ảnh không quá 3 MB.') }}" data-type-error="{{ __('Vui lòng chọn ảnh JPG, PNG hoặc WEBP, tối đa 3 MB.') }}">
        <label for="review-images-{{ $fieldKey }}">
            @include('partials.icon', ['name' => 'camera', 'size' => 26])
            <strong>{{ __('Thêm ảnh trải nghiệm') }}</strong>
            <span>{{ __('Kéo ảnh vào đây hoặc chọn từ thiết bị') }}</span>
            <small id="review-image-help-{{ $fieldKey }}">{{ __('Tối đa 4 ảnh · JPG, PNG, WEBP · 3 MB/ảnh') }}</small>
        </label>
        <input id="review-images-{{ $fieldKey }}" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" aria-describedby="review-image-help-{{ $fieldKey }}">
        <p class="rv-upload-error" role="status" data-upload-error></p>
        <div class="rv-photos" data-upload-preview></div>
        <button class="rv-clear" type="button" data-upload-clear hidden>{{ __('Bỏ ảnh đã chọn') }}</button>
    </div>
</div>
