@extends('layouts.store')

@section('title', 'Livestream nước hoa · Soopi')
@section('meta_description', 'Xem livestream thử mùi nước hoa, nhận gợi ý chọn hương và khám phá sản phẩm tại Soopi.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/livestream.css') }}">
@endpush

@section('content')
<section id="ht-live-page" class="ht-live-page store-container" aria-labelledby="ht-live-heading"
         data-state-url="{{ route('livestream.state') }}"
         data-current-id="{{ $livestream?->id }}"
         data-on-air="{{ $livestream && (($livestream->source === 'youtube' && $livestream->status === 'live') || $livestream->isBrowserOnAir()) ? '1' : '0' }}">
    <header class="ht-live-hero">
        <span class="ht-live-eyebrow">Soopi</span>
        <h1 id="ht-live-heading">Chuyện hương <em>trực tiếp</em></h1>
        <p>Thử mùi cùng Soopi, lắng nghe câu chuyện phía sau mỗi hương thơm và tìm ra chai nước hoa dành cho bạn.</p>
    </header>

    @if($livestream)
        @php($onAir = $livestream->status === 'live' && ($livestream->source === 'youtube' || $livestream->isBrowserOnAir()))
        <div class="ht-live-layout {{ $onAir ? '' : 'ht-live-layout--solo' }}">
            <article class="ht-live-card">
                <div class="ht-live-stage">
                    @if($onAir && $livestream->source === 'youtube')
                        <iframe title="Phát trực tiếp: {{ $livestream->title }}"
                            src="{{ $livestream->embed_url }}"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen loading="lazy"></iframe>
                    @elseif($onAir && $livestream->source === 'browser')
                        <div id="live-viewer" class="ht-live-native" data-token-url="{{ route('livestream.viewer-token', $livestream) }}">
                            <video id="live-viewer-video" autoplay playsinline hidden></video>
                            <audio id="live-viewer-audio" autoplay></audio>
                            <div class="ht-live-native-overlay">
                                <span class="ht-live-flower" aria-hidden="true">@include('partials.brand-mark', ['size' => '1em', 'light' => true])</span>
                                <strong>Đang phát trực tiếp</strong>
                                <button id="live-viewer-join" class="ht-live-button" type="button">Xem livestream</button>
                                <span id="live-viewer-message" role="status">Nhấn để bắt đầu xem và nghe.</span>
                            </div>
                        </div>
                    @else
                        <div class="ht-live-wait">
                            <span class="ht-live-flower" aria-hidden="true">@include('partials.brand-mark', ['size' => '1em'])</span>
                            <strong>{{ $livestream->status === 'live' ? 'Buổi phát đang tạm gián đoạn' : ($livestream->isOverdue() ? 'Đã đến giờ phát' : 'Hẹn bạn ở buổi phát sắp tới') }}</strong>
                            <span>{{ $livestream->status === 'live' ? 'Nhân viên đang kết nối lại. Vui lòng tải lại trang sau ít phút.' : ($livestream->isOverdue() ? 'Nhân viên đang chuẩn bị lên sóng. Trang này sẽ tự cập nhật khi buổi phát bắt đầu.' : 'Nhân viên sẽ bắt đầu buổi phát vào giờ hẹn. Trang này sẽ tự cập nhật khi lên sóng.') }}</span>
                        </div>
                    @endif
                </div>
                <div class="ht-live-card-body">
                    <span class="ht-live-status {{ $onAir ? 'ht-live-status--on' : '' }}">{{ $onAir ? 'Đang phát trực tiếp' : ($livestream->status === 'live' ? 'Tạm gián đoạn' : ($livestream->isOverdue() ? 'Đã tới giờ · chờ lên sóng' : 'Sắp phát')) }}</span>
                    <h2>{{ $livestream->title }}</h2>
                    @if($livestream->starts_at)<p class="ht-live-date">◷ {{ $livestream->starts_at->format('H:i · d/m/Y') }} · Giờ Việt Nam</p>@endif
                    @if($livestream->description)<p class="ht-live-description">{{ $livestream->description }}</p>@endif
                    <div class="ht-live-actions">
                        @if($onAir && $livestream->source === 'youtube')
                            <a class="ht-live-button" href="https://www.youtube.com/watch?v={{ $livestream->youtube_video_id }}" target="_blank" rel="noopener noreferrer">Trò chuyện trên YouTube @include('partials.icon', ['name' => 'external', 'size' => '1em'])</a>
                        @endif
                        <a class="ht-live-text-link" href="{{ route('home') }}">Khám phá nước hoa →</a>
                    </div>
                </div>
            </article>
            @if($onAir)
                @include('livestream.chat', ['staff' => false])
            @endif
        </div>
        <section id="live-product-shelf" class="ht-live-shelf" data-products-url="{{ route('livestream.products', $livestream) }}">
            @include('livestream.products', ['onAir' => $onAir])
        </section>
    @else
        <div class="ht-live-empty">
            <div class="ht-live-empty-copy">
                <span class="ht-live-eyebrow">Studio đang chuẩn bị</span>
                <h2>Hẹn bạn ở buổi phát <em>tiếp theo.</em></h2>
                <p>Chưa có lịch livestream mới. Trong lúc chờ, hãy tìm mùi hương hợp với mình hoặc khám phá những chai nước hoa được yêu thích tại Soopi.</p>
                <div class="ht-live-empty-actions">
                    <a class="ht-live-button" href="{{ route('store.quiz') }}">Chọn hương của bạn →</a>
                    <a class="ht-live-text-link" href="{{ route('home') }}#san-pham">Xem bộ sưu tập</a>
                </div>
                <span class="ht-live-empty-footnote">@include('partials.brand-mark', ['size' => 18]) Một chút hương, một chút thương.</span>
            </div>
            <div class="ht-live-empty-visual">
                <img src="{{ asset('images/bloom/flower-open.webp') }}" alt="Chai nước hoa giữa những cánh lụa hồng của Soopi" loading="lazy">
                <span>THE SOOPI LIVE EDIT</span>
            </div>
        </div>
    @endif
</section>
@endsection

@push('scripts')
<script defer src="{{ asset('js/livestream-state.js') }}"></script>
<script defer src="{{ asset('js/livestream-products.js') }}"></script>
@if($livestream && $onAir)<script defer src="{{ asset('js/livestream-chat.js') }}"></script>@endif
@endpush

@if($livestream && $livestream->source === 'browser' && $livestream->isBrowserOnAir())
    @push('scripts')
    <script defer src="{{ asset('vendor/livekit-client/livekit-client.umd.js') }}"></script>
    <script defer src="{{ asset('js/livestream-viewer.js') }}"></script>
    @endpush
@endif
