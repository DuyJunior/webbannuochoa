@extends('layouts.admin')

@section('title', 'Studio · '.$livestream->title)
@section('page_title', 'Studio livestream')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/livestream-admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/livestream.css') }}">
@endsection

@section('content')
<div class="live-admin">
    <header class="live-admin-hero">
        <div>
            <p class="live-admin-kicker">{{ __('Soopi · Studio trực tiếp') }}</p>
            <h2>{{ $livestream->title }}</h2>
            <p>{{ __('Bật camera, kiểm tra hình và tiếng rồi phát trực tiếp trên website.') }}</p>
        </div>
        <div class="live-admin-actions">
            <a class="live-admin-btn" href="{{ route('admin.livestreams.index') }}">{{ __('← Danh sách') }}</a>
            <a class="live-admin-btn" href="{{ route('livestream.show') }}" target="_blank" rel="noopener noreferrer">{{ __('Trang khách xem') }} @include('partials.icon', ['name' => 'external', 'size' => '1em'])</a>
        </div>
    </header>

    @if(!$configured)
        <div class="alert alert-warning" role="alert">{{ __('Chưa cấu hình máy chủ video LiveKit. Hãy thêm LIVEKIT_URL, LIVEKIT_API_KEY và LIVEKIT_API_SECRET vào môi trường máy chủ trước khi lên sóng.') }}</div>
    @endif
    @if($configured && preg_match('~^wss?://(?:127\.0\.0\.1|localhost)(?::|/)~i', $livekitUrl ?? ''))
        <div class="live-local-note" role="note"><strong>{{ __('Đang dùng máy chủ video trên máy này.') }}</strong> {{ __('Khách ở thiết bị khác chỉ xem được khi LiveKit có địa chỉ công khai bảo mật (wss://).') }}</div>
    @endif
    @if($livestream->status === 'ended')
        <div class="alert alert-info" role="status">{{ __('Buổi phát này đã kết thúc. Tạo buổi mới để phát tiếp.') }}</div>
    @endif

    <div id="live-studio" class="live-studio-grid"
         data-token-url="{{ route('admin.livestreams.host-token', $livestream) }}"
         data-begin-url="{{ route('admin.livestreams.begin', $livestream) }}"
         data-heartbeat-url="{{ route('admin.livestreams.heartbeat', $livestream) }}"
         data-finish-url="{{ route('admin.livestreams.finish', $livestream) }}"
         data-viewer-url="{{ route('livestream.show') }}"
         data-configured="{{ $configured ? '1' : '0' }}"
         data-ended="{{ $livestream->status === 'ended' ? '1' : '0' }}">
        <section class="live-studio-preview" aria-label="{{ __('Hình ảnh camera của bạn') }}">
            <video id="live-studio-video" autoplay muted playsinline></video>
            <div class="live-studio-placeholder" id="live-studio-placeholder">@include('partials.brand-mark', ['size' => 80, 'light' => true])<span>{{ __('Hình ảnh của bạn sẽ hiện ở đây') }}</span></div>
            <span class="live-studio-pill" id="live-studio-pill">{{ __('Chưa lên sóng') }}</span>
        </section>
        <aside class="live-studio-controls">
            <span class="live-admin-kicker">{{ __('Phòng điều khiển') }}</span>
            <h3>{{ __('Buổi live của bạn') }}</h3>
            <p id="live-studio-status" role="status">{{ __('Sẵn sàng kiểm tra camera và micro.') }}</p>
            <div class="live-studio-buttons">
                <button id="live-studio-start" class="live-admin-btn live-admin-btn--primary" type="button" @disabled(!$configured || $livestream->status === 'ended')>@include('partials.icon', ['name' => 'broadcast', 'size' => '1em']) Livestream ngay</button>
                <button id="live-studio-mic" class="live-admin-btn" type="button" disabled>{{ __('Micro: bật') }}</button>
                <button id="live-studio-camera" class="live-admin-btn" type="button" disabled>{{ __('Camera: bật') }}</button>
                <button id="live-studio-end" class="live-admin-btn live-admin-btn--danger" type="button" disabled>{{ __('Kết thúc buổi live') }}</button>
            </div>
            <p class="live-studio-note">{{ __('Chỉ nhân viên được cấp quyền mới vào studio. Giữ trang này mở khi đang phát. Nếu kết nối bị mất, trang khách xem sẽ tạm dừng.') }}</p>
            <label for="live-studio-link">{{ __('Liên kết gửi cho khách') }}</label>
            <input id="live-studio-link" type="text" readonly value="{{ route('livestream.show') }}" onclick="this.select()">
        </aside>
    </div>
    <div class="live-studio-bottom">
        @include('livestream.chat', ['staff' => true])
        <div class="live-studio-tip"><span class="live-admin-kicker">{{ __('Tương tác trong buổi live') }}</span><h3>{{ __('Giới thiệu đúng lúc') }}</h3><p>{{ __('Ghim chai nước hoa đang nói tới. Khách sẽ thấy sản phẩm nổi bật ở đầu kệ ngay khi danh sách cập nhật.') }}</p><p>{{ __('Bình luận mới hiện ở đây; bấm') }} <strong>{{ __('Ẩn') }}</strong> {{ __('để gỡ nội dung không phù hợp.') }}</p></div>
    </div>
    <section class="live-admin-panel live-studio-products" aria-labelledby="studio-products-title">
        <div class="live-admin-panel-heading">
            <div><span class="live-admin-kicker">{{ __('Bán hàng trong buổi live') }}</span><h3 id="studio-products-title">{{ __('Sản phẩm đang giới thiệu') }}</h3></div>
            <small>{{ __('Khách thấy danh sách được cập nhật ngay, không cần dừng video.') }}</small>
        </div>
        @if($livestream->status !== 'ended')
            <form id="studio-product-form" class="live-studio-product-form" data-url="{{ route('admin.livestreams.products.store', $livestream) }}" data-pin-url="{{ route('admin.livestreams.pin', $livestream) }}">
                <label for="studio-product-select">{{ __('Thêm nước hoa') }}</label>
                <select id="studio-product-select" name="perfume_id" required>
                    <option value="">{{ __('Chọn sản phẩm...') }}</option>
                    @foreach($perfumes as $perfume)
                        <option value="{{ $perfume->id }}">{{ $perfume->name }} · {{ $perfume->brand }}</option>
                    @endforeach
                </select>
                <button class="live-admin-btn live-admin-btn--primary" type="submit">{{ __('+ Thêm vào live') }}</button>
            </form>
            <p id="studio-product-message" class="live-studio-product-message" role="status"></p>
        @endif
        <div id="studio-product-list">@include('admin.livestreams.products', ['products' => $livestream->products])</div>
    </section>
</div>
@if($livestream->status !== 'ended')<script defer src="{{ asset('js/livestream-products-admin.js') }}"></script>@endif
<script defer src="{{ asset('js/livestream-chat.js') }}"></script>
@if($configured && $livestream->status !== 'ended')
<script defer src="{{ asset('vendor/livekit-client/livekit-client.umd.js') }}"></script>
<script defer src="{{ asset('js/livestream-studio.js') }}"></script>
@endif
@endsection
