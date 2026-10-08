<section class="rv-section" id="danh-gia" aria-labelledby="reviews-heading">
    <header class="rv-heading"><div><span class="rv-eyebrow">SOOPI / {{ __('CẢM NHẬN THỰC TẾ') }}</span><h2 id="reviews-heading">{{ __('Những cảm nhận sau một mùi hương') }}</h2><p>{{ __('Trải nghiệm của bạn, niềm tin cho người đến sau.') }}</p></div><span class="rv-count">{{ $reviews->total() }} {{ __('đánh giá') }}</span></header>
    <div class="rv-summary">
        <div class="rv-score"><span class="rv-eyebrow">{{ __('Đánh giá từ khách hàng') }}</span><div><strong>{{ $reviews->total() ? number_format($averageRating, 1) : '—' }}</strong><span>/ 5</span></div><span class="rv-stars" aria-hidden="true">@for($star=1;$star<=5;$star++)<span class="{{ $star <= round($averageRating) ? 'is-filled' : '' }}">★</span>@endfor</span><p>{{ trans_choice(':count đánh giá', $reviews->total(), ['count' => $reviews->total()]) }}</p></div>
        <div class="rv-distribution" aria-label="{{ __('Phân bố điểm đánh giá') }}">
            @for($star=5;$star>=1;$star--)
                @php $votes = (int) ($ratingCounts[$star] ?? 0); $percent = $reviews->total() ? round($votes * 100 / $reviews->total()) : 0; @endphp
                <div><span>{{ $star }} <span aria-hidden="true">★</span></span><div class="rv-bar" role="meter" aria-label="{{ __(':rating trên 5 sao', ['rating'=>$star]) }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-valuetext="{{ $votes }} {{ __('đánh giá') }}"><span style="width:{{ $percent }}%"></span></div><small>{{ $votes }}</small></div>
            @endfor
        </div>
        <div class="rv-summary-note">@include('partials.icon', ['name'=>'heart', 'size'=>25])
            @if($reviews->total())<strong>{{ round(((int) ($ratingCounts[4] ?? 0) + (int) ($ratingCounts[5] ?? 0)) * 100 / $reviews->total()) }}% {{ __('hài lòng') }}</strong><p>{{ __('Tỷ lệ đánh giá 4 hoặc 5 sao.') }}</p>
            @else<strong>{{ __('Câu chuyện đầu tiên là của bạn') }}</strong><p>{{ __('Hãy chia sẻ sau khi nhận và trải nghiệm sản phẩm.') }}</p>@endif
            <a href="#review-compose" class="rv-text-link">{{ __('Chia sẻ cảm nhận') }} ↗</a>
        </div>
    </div>
    <div class="rv-layout">
        <div class="rv-feed">
            <div class="rv-feed-title"><h3>{{ __('Lời nhắn từ khách hàng') }}</h3><span>{{ __('Mới nhất trước') }}</span></div>
            @forelse($reviews as $review)
                <article class="rv-card" id="customer-review-{{ $review->id }}">
                    <header><span class="rv-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($review->user?->name ?? 'S', 0, 1)) }}</span><div class="rv-person"><strong>{{ $review->user?->name ?? __('Khách hàng') }}</strong>@if($verifiedBuyerIds->contains($review->user_id))<span class="rv-verified">@include('partials.icon', ['name'=>'shield', 'size'=>13]) {{ __('Đã mua hàng') }}</span>@endif</div><time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('d/m/Y') }}</time></header>
                    <div class="rv-stars" role="img" aria-label="{{ __(':rating trên 5 sao', ['rating'=>$review->rating]) }}">@for($star=1;$star<=5;$star++)<span class="{{ $star <= $review->rating ? 'is-filled' : '' }}" aria-hidden="true">★</span>@endfor</div>
                    @if($review->tags)<div class="rv-tags">@foreach($review->tags as $tag)@if(isset(\App\Models\PerfumeReview::TAGS[$tag]))<span>{{ __(\App\Models\PerfumeReview::TAGS[$tag]) }}</span>@endif @endforeach</div>@endif
                    <p class="rv-body">{{ $review->body }}</p>
                    @if(count($review->photos))<div class="rv-photos">@foreach($review->photos as $photo)<a href="{{ asset($photo) }}" target="_blank" rel="noopener" aria-label="{{ __('Xem ảnh đánh giá') }}"><img src="{{ asset($photo) }}" alt="{{ __('Ảnh do khách hàng chia sẻ') }}" loading="lazy"></a>@endforeach</div>@endif
                    @if($review->seller_reply)<div class="rv-reply"><strong>@include('partials.brand-mark', ['size'=>'18px']) {{ __('Phản hồi từ Soopi') }}</strong><p>{{ $review->seller_reply }}</p>@if($review->replied_at)<time>{{ $review->replied_at->format('d/m/Y') }}</time>@endif</div>@endif
                    @if(auth()->user()?->role === 'admin')<details class="rv-reply-compose"><summary>{{ __('Phản hồi khách hàng') }}</summary><form method="POST" action="{{ route('admin.reviews.reply', $review) }}">@csrf<label for="reply-{{ $review->id }}">{{ __('Phản hồi từ Soopi') }}</label><textarea id="reply-{{ $review->id }}" name="seller_reply" minlength="3" maxlength="2000" required rows="3">{{ $review->seller_reply }}</textarea><button type="submit" class="chic-button">{{ __('Lưu phản hồi') }}</button></form></details>@endif
                </article>
            @empty<div class="rv-empty">@include('partials.icon', ['name'=>'flower','size'=>36])<h3>{{ __('Mỗi cảm nhận đều đáng được lắng nghe') }}</h3><p>{{ __('Chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ cảm nhận.') }}</p></div>@endforelse
            <div class="rv-pagination">{{ $reviews->links() }}</div>
        </div>
        <aside class="rv-compose" id="review-compose"><span class="rv-eyebrow">{{ __('GÓC CỦA BẠN') }}</span><h3>{{ __('Chia sẻ cảm nhận của bạn') }}</h3><p class="rv-muted">{{ __('Một chút chia sẻ, nhiều điều ý nghĩa.') }}</p>
            @auth
                @if($canReview)
                    <div class="rv-purchase-context">
                        <span class="rv-eyebrow">{{ __('ĐÁNH GIÁ CHO LẦN MUA') }}</span>
                        @if($reviewPurchase)
                            <strong>#DH{{ str_pad($reviewPurchase->order_id, 5, '0', STR_PAD_LEFT) }} · {{ $reviewPurchase->volume_label }}</strong>
                            <span>{{ $reviewPurchase->order->created_at->format('d/m/Y') }}</span>
                        @else
                            <strong>{{ __('Đánh giá trước đây') }}</strong>
                            <span>{{ __('Đánh giá này được lưu trước khi hệ thống tách theo từng lần mua.') }}</span>
                        @endif
                        @if($reviewPurchases->count() + $legacyReviews->count() > 1)
                            <details><summary>{{ __('Chọn lần mua khác') }}</summary>
                                @foreach($reviewPurchases as $purchaseOption)
                                    <a href="{{ route('perfumes.show', ['perfume'=>$perfume, 'review_item'=>$purchaseOption->id]) }}#review-compose">#DH{{ str_pad($purchaseOption->order_id,5,'0',STR_PAD_LEFT) }} · {{ $purchaseOption->volume_label }} · {{ $purchaseOption->order->created_at->format('d/m/Y') }}</a>
                                @endforeach
                                @foreach($legacyReviews as $legacyReview)
                                    <a href="{{ route('perfumes.show', ['perfume'=>$perfume, 'legacy_review'=>$legacyReview->id]) }}#review-compose">{{ __('Đánh giá trước đây') }} · {{ $legacyReview->created_at->format('d/m/Y') }}</a>
                                @endforeach
                            </details>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('store.review', $perfume) }}" enctype="multipart/form-data" class="rv-form">@csrf
                        <input type="hidden" name="review_source" value="product">
                        @if($reviewPurchase)
                            <input type="hidden" name="order_id" value="{{ $reviewPurchase->order_id }}">
                            <input type="hidden" name="order_item_id" value="{{ $reviewPurchase->id }}">
                        @else
                            <input type="hidden" name="legacy_review_id" value="{{ $myReview->id }}">
                        @endif
                        @if($errors->any())<div class="rv-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                        @include('partials.review-fields', ['fieldKey'=>'product-'.$perfume->id, 'formReview'=>$myReview, 'restoreReview'=>$errors->any()])
                        <button class="chic-button" type="submit">{{ $myReview ? __('Cập nhật đánh giá') : __('Gửi đánh giá') }} @include('partials.icon',['name'=>'arrow','size'=>17])</button>
                    </form>
                @else<p class="rv-eligibility">@include('partials.icon', ['name'=>'shield','size'=>24]) {{ __('Bạn chỉ có thể đánh giá sản phẩm đã mua và nhận hàng thành công.') }}</p><a class="chic-button chic-button-outline" href="{{ route('orders.index') }}">{{ __('Đơn hàng của tôi') }} ↗</a>@endif
            @else<a class="chic-button" href="{{ route('login') }}">{{ __('Đăng nhập để đánh giá') }} ↗</a>@endauth
        </aside>
    </div>
</section>
