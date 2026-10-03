@extends('layouts.admin')

@section('title', $livestream->exists ? __('Sửa livestream') : __('Tạo livestream'))
@section('page_title', $livestream->exists ? __('Sửa buổi livestream') : __('Tạo buổi livestream'))
@section('styles')
<link rel="stylesheet" href="{{ asset('css/livestream-admin.css') }}">
@endsection

@section('content')
<div class="live-admin">
    <header class="live-admin-hero">
        <div>
            <p class="live-admin-kicker">{{ __('Soopi · Studio trực tiếp') }}</p>
            <h2>{{ $livestream->exists ? __('Chỉnh sửa buổi phát') : __('Tạo buổi phát mới') }}</h2>
            <p>{{ __('Chọn lên sóng ngay hoặc hẹn giờ để khách biết trước buổi phát.') }}</p>
        </div>
        <div class="live-admin-actions"><a class="live-admin-btn" href="{{ route('admin.livestreams.index') }}">{{ __('← Danh sách livestream') }}</a></div>
    </header>



    <div class="live-admin-form-grid">
        <div class="live-admin-form-card">
            <h2>{{ __('Thông tin buổi phát') }}</h2>
            <form method="POST" action="{{ $livestream->exists ? route('admin.livestreams.update', $livestream) : route('admin.livestreams.store') }}">
                @csrf
                @if($livestream->exists) @method('PUT') @endif

                @unless($livestream->exists)
                    @php($launchMode = old('launch_mode', request()->query('mode') === 'schedule' ? 'schedule' : 'now'))
                    <fieldset class="live-admin-mode" aria-label="{{ __('Thời điểm livestream') }}">
                        <legend>{{ __('Bạn muốn phát khi nào?') }}</legend>
                        <label class="live-admin-mode-card">
                            <input type="radio" name="launch_mode" value="now" @checked($launchMode === 'now')>
                            <span><strong>@include('partials.icon', ['name' => 'broadcast', 'size' => '1em']) Livestream ngay</strong><small>{{ __('Chuẩn bị camera và lên sóng từ studio.') }}</small></span>
                        </label>
                        <label class="live-admin-mode-card">
                            <input type="radio" name="launch_mode" value="schedule" @checked($launchMode === 'schedule')>
                            <span><strong>{{ __('◷ Đặt lịch phát') }}</strong><small>{{ __('Thông báo giờ phát trước cho khách hàng.') }}</small></span>
                        </label>
                    </fieldset>
                    <input type="hidden" name="status" value="scheduled">
                @endunless

                <div class="live-admin-field">
                    <label for="live-title">{{ __('Tên buổi phát *') }}</label>
                    <input id="live-title" name="title" type="text" required maxlength="255" value="{{ old('title', $livestream->title) }}" placeholder="{{ __('Ví dụ: Chọn hương cho ngày hẹn đầu tiên') }}">
                </div>
                <div class="live-admin-field">
                    <label for="live-description">{{ __('Giới thiệu ngắn') }}</label>
                    <textarea id="live-description" name="description" rows="4" maxlength="2000" placeholder="{{ __('Kể cho khách biết điều thú vị trong buổi live...') }}">{{ old('description', $livestream->description) }}</textarea>
                </div>
                <div class="live-admin-field">
                    <label for="live-source">{{ __('Cách phát *') }}</label>
                    <select id="live-source" name="source" required @disabled($livestream->isBrowserOnAir())>
                        <option value="browser" @selected(old('source', $livestream->source ?? 'browser') === 'browser')>{{ __('Camera/micro ngay trên web') }}</option>
                        <option value="youtube" @selected(old('source', $livestream->source ?? 'browser') === 'youtube')>{{ __('Nhúng YouTube Live') }}</option>
                    </select>
                    @if($livestream->isBrowserOnAir())<input type="hidden" name="source" value="browser">@endif
                </div>
                <div class="live-admin-field" id="live-youtube-field">
                    <label for="live-url">{{ __('Liên kết YouTube Live *') }}</label>
                    <input id="live-url" name="youtube_url" type="url" maxlength="2048" value="{{ old('youtube_url', $livestream->youtube_video_id ? 'https://www.youtube.com/watch?v='.$livestream->youtube_video_id : '') }}" placeholder="https://www.youtube.com/live/...">
                    <small>{{ __('Chỉ cần khi chọn YouTube. Video phải được bật quyền nhúng.') }}</small>
                </div>
                <div class="live-admin-field" id="live-time-field">
                    <label for="live-time">{{ __('Ngày và giờ phát · giờ Việt Nam (UTC+7)') }}</label>
                    <input id="live-time" name="starts_at" type="datetime-local" value="{{ old('starts_at', $livestream->starts_at?->format('Y-m-d\TH:i')) }}">
                    <small>{{ __('Đặt lịch giúp khách thấy giờ hẹn. Đến giờ, nhân viên bấm “Livestream ngay” trong danh sách; phát bằng camera sẽ mở studio.') }}</small>
                </div>
                @if($livestream->exists)
                    <div class="live-admin-field">
                        <label for="live-status">{{ __('Trạng thái *') }}</label>
                        <select id="live-status" name="status" required>
                            <option value="scheduled" @selected(old('status', $livestream->status ?? 'scheduled') === 'scheduled')>{{ __('Sắp phát') }}</option>
                            <option value="live" @selected(old('status', $livestream->status) === 'live')>{{ __('Đang phát') }}</option>
                            <option value="ended" @selected(old('status', $livestream->status) === 'ended')>{{ __('Đã kết thúc') }}</option>
                        </select>
                    </div>
                @endif
                <div class="live-admin-field">
                    <label for="live-product-search">{{ __('Sản phẩm trong buổi live') }}</label>
                    <input id="live-product-search" type="search" placeholder="{{ __('Tìm tên hoặc thương hiệu nước hoa...') }}" autocomplete="off">
                    @php($selectedProducts = array_map('strval', (array) (session()->hasOldInput() ? old('perfume_ids', []) : $livestream->products->pluck('id')->all())))
                    <div id="live-product-options" class="live-product-options">
                        @forelse($perfumes as $perfume)
                            <label class="live-product-choice" data-search="{{ mb_strtolower($perfume->name.' '.$perfume->brand) }}">
                                <input type="checkbox" name="perfume_ids[]" value="{{ $perfume->id }}" @checked(in_array((string) $perfume->id, $selectedProducts, true))>
                                @if($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="" loading="lazy">@endif
                                <span><strong>{{ $perfume->name }}</strong><small>{{ $perfume->brand }} · {{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}{{ __('đ') }}</small></span>
                            </label>
                        @empty
                            <p class="text-muted small p-3 mb-0">{{ __('Chưa có sản phẩm đang bán để gắn vào buổi phát.') }}</p>
                        @endforelse
                    </div>
                    <p id="live-product-empty" class="text-muted small mt-2" hidden>{{ __('Không tìm thấy sản phẩm phù hợp. Thử tên hoặc thương hiệu khác.') }}</p>
                    <small id="live-product-count" class="d-block mt-2" role="status">{{ __('Đã chọn') }} {{ count($selectedProducts) }} {{ __('/ 50 sản phẩm') }}</small>
                    <small>{{ __('Chọn tối đa 50 sản phẩm. Khi đang phát, bạn vẫn có thể thêm hoặc gỡ sản phẩm ngay trong Studio.') }}</small>
                </div>
                <button id="live-submit" type="submit" class="live-admin-btn live-admin-btn--primary">{{ $livestream->exists ? __('Lưu thay đổi') : __('Tiếp tục') }}</button>
            </form>
        </div>
        <aside class="live-admin-help">
            <h3>{{ __('Phát trực tiếp trên web') }}</h3>
            <ol>
                <li>{{ __('Chọn “Livestream ngay” hoặc đặt một giờ phát trong tương lai.') }}</li>
                <li>{{ __('Vào studio, cho phép trình duyệt dùng camera và micro.') }}</li>
                <li>{{ __('Bấm “Livestream ngay” trong studio để khách bắt đầu xem.') }}</li>
            </ol>
            <p>{{ __('Lịch phát không thể tự bật camera. Studio cần máy chủ LiveKit; khi sử dụng trên mạng cần HTTPS.') }}</p>
            @if($livestream->exists && $livestream->source === 'browser' && $livestream->status !== 'ended')
                <a class="live-admin-btn live-admin-btn--primary" href="{{ route('admin.livestreams.studio', $livestream) }}">{{ __('Vào studio →') }}</a>
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
    const productEmpty = document.getElementById('live-product-empty');
    const productCount = document.getElementById('live-product-count');
    const editing = {{ $livestream->exists ? 'true' : 'false' }};
    const sync = () => {
        const youtube = source.value === 'youtube';
        const scheduled = modes.find(mode => mode.checked)?.value === 'schedule';
        youtubeField.hidden = !youtube;
        youtubeUrl.required = youtube;
        timeField.hidden = !editing && !scheduled;
        timeInput.required = !editing && scheduled;
        if (status) status.querySelector('option[value="live"]').disabled = !youtube && status.value !== 'live';
        if (!editing) submit.textContent = scheduled ? (window.soopiT || (text => text))("Lưu lịch phát") : (youtube ? (window.soopiT || (text => text))("Hiển thị YouTube Live ngay") : (window.soopiT || (text => text))("Mở studio để livestream ngay"));
    };
    source.addEventListener('change', () => {
        if (status && source.value === 'browser' && status.value === 'live') status.value = 'scheduled';
        sync();
    });
    modes.forEach(mode => mode.addEventListener('change', sync));
    productSearch.addEventListener('input', () => {
        const query = productSearch.value.trim().toLocaleLowerCase('vi');
        productOptions.forEach(option => { option.hidden = !option.dataset.search.includes(query); });
        productEmpty.hidden = !productOptions.length || productOptions.some(option => !option.hidden);
    });
    productOptions.forEach(option => option.querySelector('input').addEventListener('change', () => {
        productCount.textContent = `${(window.soopiT || (text => text))("Đã chọn")} ${productOptions.filter(option => option.querySelector('input').checked).length} ${(window.soopiT || (text => text))("/ 50 sản phẩm")}`;
    }));
    sync();
})();
</script>
@endsection
