@if((int) $order->user_id === (int) auth()->id())
    <div class="order-product-reviews">
        @if(! $order->canReviewProducts())
            @unless($order->status === 'cancelled')
                <p class="order-review-hint">{{ __('Bạn có thể đánh giá sau khi nhận hàng thành công.') }}</p>
            @endunless
        @else
            @foreach($item->reviewProductIds() as $reviewProductId)
                @php
                    $reviewProduct = $reviewProducts->get($reviewProductId);
                    $myReview = $customerReviews->get($reviewProductId);
                    $reviewKey = $order->id.'-'.$item->id.'-'.$reviewProductId;
                    $reviewFailed = old('review_key') === $reviewKey && $errors->any();
                    $selectedRating = $reviewFailed ? old('rating') : $myReview?->rating;
                    $selectedRating = is_scalar($selectedRating) ? (string) $selectedRating : '';
                @endphp
                @if($reviewProduct)
                    <div class="order-review-entry" id="review-{{ $reviewKey }}">
                        @if(session('review_saved') === $reviewKey)
                            <p class="order-review-success" role="status">{{ __('Đã lưu đánh giá của bạn.') }}</p>
                        @endif
                        <details class="order-review-details" @if($reviewFailed) open @endif>
                            <summary>
                                <span aria-hidden="true">★</span>
                                <span>{{ $myReview ? __('Sửa đánh giá') : __('Đánh giá sản phẩm') }}@if(count($item->reviewProductIds()) > 1) · {{ $reviewProduct->localized_name }}@endif</span>
                                @if($myReview)<span class="order-review-score">{{ $myReview->rating }}/5</span>@endif
                            </summary>
                            <form method="POST" action="{{ route('store.review', $reviewProduct) }}" enctype="multipart/form-data" class="order-review-form">
                                @csrf
                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                <input type="hidden" name="review_key" value="{{ $reviewKey }}">
                                <p class="order-review-product">{{ $reviewProduct->localized_name }}</p>
                                @if($reviewFailed)
                                    <div class="order-review-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                                @endif
                                <label for="rating-{{ $reviewKey }}">{{ __('Đánh giá') }}</label>
                                <select id="rating-{{ $reviewKey }}" name="rating" required>
                                    <option value="">{{ __('Chọn số sao') }}</option>
                                    @foreach([5 => __('5 sao · Rất thích'), 4 => __('4 sao · Hài lòng'), 3 => __('3 sao · Khá'), 2 => __('2 sao · Chưa hợp'), 1 => __('1 sao · Không hợp')] as $rating => $ratingLabel)
                                        <option value="{{ $rating }}" @selected($selectedRating === (string) $rating)>{{ $ratingLabel }}</option>
                                    @endforeach
                                </select>
                                <label for="body-{{ $reviewKey }}">{{ __('Cảm nhận') }}</label>
                                <textarea id="body-{{ $reviewKey }}" name="body" rows="4" minlength="10" maxlength="2000" required placeholder="{{ __('Bạn cảm nhận mùi hương như thế nào?') }}">{{ $reviewFailed && is_string(old('body')) ? old('body') : $myReview?->body }}</textarea>
                                @if($myReview?->image_path)<img class="order-review-photo" src="{{ asset($myReview->image_path) }}" alt="{{ __('Ảnh do khách hàng chia sẻ') }}" loading="lazy">@endif
                                <label for="image-{{ $reviewKey }}">{{ __('Ảnh trải nghiệm (không bắt buộc)') }}</label>
                                <input id="image-{{ $reviewKey }}" type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="image-help-{{ $reviewKey }}">
                                <small id="image-help-{{ $reviewKey }}">{{ __('Ảnh JPG, PNG hoặc WEBP, tối đa 3 MB.') }}</small>
                                <button type="submit">{{ $myReview ? __('Cập nhật đánh giá') : __('Gửi đánh giá') }}</button>
                            </form>
                        </details>
                        @if($myReview)<span class="order-review-saved">{{ __('Đã đánh giá') }}</span>@endif
                    </div>
                @else
                    <p class="order-review-hint">{{ __('Sản phẩm này hiện không nhận đánh giá.') }}</p>
                @endif
            @endforeach
        @endif
    </div>
@endif
