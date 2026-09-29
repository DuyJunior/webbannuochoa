@extends('layouts.admin')

@section('title', $livestream->exists ? 'Sửa livestream' : 'Tạo livestream')
@section('page_title', $livestream->exists ? 'Sửa buổi livestream' : 'Tạo buổi livestream')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/livestream-admin.css') }}">
@endsection

@section('content')
<div class="live-admin">
    <header class="live-admin-hero">
        <div>
            <p class="live-admin-kicker">Soopi · Studio trực tiếp</p>
            <h2>{{ $livestream->exists ? 'Chỉnh sửa buổi phát' : 'Tạo buổi phát mới' }}</h2>
            <p>Chọn lên sóng ngay hoặc hẹn giờ để khách biết trước buổi phát.</p>
        </div>
        <div class="live-admin-actions"><a class="live-admin-btn" href="{{ route('admin.livestreams.index') }}">← Danh sách livestream</a></div>
    </header>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Vui lòng kiểm tra lại thông tin:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="live-admin-form-grid">
        <div class="live-admin-form-card">
            <h2>Thông tin buổi phát</h2>
            <form method="POST" action="{{ $livestream->exists ? route('admin.livestreams.update', $livestream) : route('admin.livestreams.store') }}">
                @csrf
                @if($livestream->exists) @method('PUT') @endif

                @unless($livestream->exists)
                    @php($launchMode = old('launch_mode', request()->query('mode') === 'schedule' ? 'schedule' : 'now'))
                    <fieldset class="live-admin-mode" aria-label="Thời điểm livestream">
                        <legend>Bạn muốn phát khi nào?</legend>
                        <label class="live-admin-mode-card">
                            <input type="radio" name="launch_mode" value="now" @checked($launchMode === 'now')>
                            <span><strong>@include('partials.icon', ['name' => 'broadcast', 'size' => '1em']) Livestream ngay</strong><small>Chuẩn bị camera và lên sóng từ studio.</small></span>
                        </label>
                        <label class="live-admin-mode-card">
                            <input type="radio" name="launch_mode" value="schedule" @checked($launchMode === 'schedule')>
                            <span><strong>◷ Đặt lịch phát</strong><small>Thông báo giờ phát trước cho khách hàng.</small></span>
                        </label>
                    </fieldset>
                    <input type="hidden" name="status" value="scheduled">
                @endunless

                <div class="live-admin-field">
                    <label for="live-title">Tên buổi phát *</label>
                    <input id="live-title" name="title" type="text" required maxlength="255" value="{{ old('title', $livestream->title) }}" placeholder="Ví dụ: Chọn hương cho ngày hẹn đầu tiên">
                </div>
                <div class="live-admin-field">
                    <label for="live-description">Giới thiệu ngắn</label>
                    <textarea id="live-description" name="description" rows="4" maxlength="2000" placeholder="Kể cho khách biết điều thú vị trong buổi live...">{{ old('description', $livestream->description) }}</textarea>
                </div>
                <div class="live-admin-field">
                    <label for="live-source">Cách phát *</label>
                    <select id="live-source" name="source" required @disabled($livestream->isBrowserOnAir())>
                        <option value="browser" @selected(old('source', $livestream->source ?? 'browser') === 'browser')>Camera/micro ngay trên web</option>
                        <option value="youtube" @selected(old('source', $livestream->source ?? 'browser') === 'youtube')>Nhúng YouTube Live</option>
                    </select>
                    @if($livestream->isBrowserOnAir())<input type="hidden" name="source" value="browser">@endif
                </div>
                <div class="live-admin-field" id="live-youtube-field">
                    <label for="live-url">Liên kết YouTube Live *</label>
                    <input id="live-url" name="youtube_url" type="url" maxlength="2048" value="{{ old('youtube_url', $livestream->youtube_video_id ? 'https://www.youtube.com/watch?v='.$livestream->youtube_video_id : '') }}" placeholder="https://www.youtube.com/live/...">
                    <small>Chỉ cần khi chọn YouTube. Video phải được bật quyền nhúng.</small>
                </div>
                <div class="live-admin-field" id="live-time-field">
                    <label for="live-time">Ngày và giờ phát · giờ Việt Nam (UTC+7)</label>
                    <input id="live-time" name="starts_at" type="datetime-local" value="{{ old('starts_at', $livestream->starts_at?->format('Y-m-d\TH:i')) }}">
                    <small>Đặt lịch giúp khách thấy giờ hẹn. Đến giờ, nhân viên bấm “Livestream ngay” trong danh sách; phát bằng camera sẽ mở studio.</small>
                </div>
                @if($livestream->exists)
                    <div class="live-admin-field">
                        <label for="live-status">Trạng thái *</label>
                        <select id="live-status" name="status" required>
                            <option value="scheduled" @selected(old('status', $livestream->status ?? 'scheduled') === 'scheduled')>Sắp phát</option>
                            <option value="live" @selected(old('status', $livestream->status) === 'live')>Đang phát</option>
                            <option value="ended" @selected(old('status', $livestream->status) === 'ended')>Đã kết thúc</option>
                        </select>
                    </div>
                @endif
                <div class="live-admin-field">
                    <label for="live-product-search">Sản phẩm trong buổi live</label>
                    <input id="live-product-search" type="search" placeholder="Tìm tên hoặc thương hiệu nước hoa..." autocomplete="off">
                    @php($selectedProducts = array_map('strval', old('perfume_ids', $livestream->products->pluck('id')->all())))
                    <div id="live-product-options" class="live-product-options">
                        @foreach($perfumes as $perfume)
                            <label class="live-product-choice" data-search="{{ mb_strtolower($perfume->name.' '.$perfume->brand) }}">
                                <input type="checkbox" name="perfume_ids[]" value="{{ $perfume->id }}" @checked(in_array((string) $perfume->id, $selectedProducts, true))>
                                @if($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="" loading="lazy">@endif
                                <span><strong>{{ $perfume->name }}</strong><small>{{ $perfume->brand }} · {{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}đ</small></span>
                            </label>
                        @endforeach
                    </div>
                    <small>Chọn tối đa 50 sản phẩm. Khi đang phát, bạn vẫn có thể thêm hoặc gỡ sản phẩm ngay trong Studio.</small>
                </div>
                <button id="live-submit" type="submit" class="live-admin-btn live-admin-btn--primary">{{ $livestream->exists ? 'Lưu thay đổi' : 'Tiếp tục' }}</button>
            </form>
        </div>
        <aside class="live-admin-help">
            <h3>Phát trực tiếp trên web</h3>
            <ol>
                <li>Chọn “Livestream ngay” hoặc đặt một giờ phát trong tương lai.</li>
                <li>Vào studio, cho phép trình duyệt dùng camera và micro.</li>
                <li>Bấm “Livestream ngay” trong studio để khách bắt đầu xem.</li>
            </ol>
            <p>Lịch phát không thể tự bật camera. Studio cần máy chủ LiveKit; khi sử dụng trên mạng cần HTTPS.</p>
            @if($livestream->exists && $livestream->source === 'browser' && $livestream->status !== 'ended')
                <a class="live-admin-btn live-admin-btn--primary" href="{{ route('admin.livestreams.studio', $livestream) }}">Vào studio →</a>
            @endif
        </aside>
    </div>
</div>
<script>
(() => {
    const source = document.getElementById('live-source');
    const youtubeField = document.getElementById('live-youtube-field');
    const youtubeUrl = document.getElementById('live-url');
    const timeField = document.getElementById('live-time-field');
    const timeInput = document.getElementById('live-time');
    const status = document.getElementById('live-status');
    const submit = document.getElementById('live-submit');
    const modes = [...document.querySelectorAll('input[name="launch_mode"]')];
    const productSearch = document.getElementById('live-product-search');
    const productOptions = [...document.querySelectorAll('#live-product-options .live-product-choice')];
    const editing = {{ $livestream->exists ? 'true' : 'false' }};
    const sync = () => {
        const youtube = source.value === 'youtube';
        const scheduled = modes.find(mode => mode.checked)?.value === 'schedule';
        youtubeField.hidden = !youtube;
        youtubeUrl.required = youtube;
        timeField.hidden = !editing && !scheduled;
        timeInput.required = !editing && scheduled;
        if (status) status.querySelector('option[value="live"]').disabled = !youtube && status.value !== 'live';
        if (!editing) submit.textContent = scheduled ? 'Lưu lịch phát' : (youtube ? 'Hiển thị YouTube Live ngay' : 'Mở studio để livestream ngay');
    };
    source.addEventListener('change', () => {
        if (status && source.value === 'browser' && status.value === 'live') status.value = 'scheduled';
        sync();
    });
    modes.forEach(mode => mode.addEventListener('change', sync));
    productSearch.addEventListener('input', () => {
        const query = productSearch.value.trim().toLocaleLowerCase('vi');
        productOptions.forEach(option => { option.hidden = !option.dataset.search.includes(query); });
    });
    sync();
})();
</script>
@endsection
