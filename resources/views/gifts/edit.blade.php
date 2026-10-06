@extends('layouts.store')
@section('robots', 'noindex, nofollow')
@section('title', __('Thiệp quà riêng tư') . ' · Soopi')
@section('content')
<section class="gx gx-page">
    <header class="gx-heading"><div><a class="gx-back" href="{{ route('gifts.index') }}">← {{ __('Quà của tôi') }}</a><span class="gx-kicker">SOOPI / THE ART OF GIVING</span><h1>{{ $gift->exists ? __('Chăm chút lời thương.') : __('Gói một điều riêng tư.') }}</h1><p>{{ __('Một bức ảnh. Một lời nhắn. Một giọng nói thân quen.') }}</p></div></header>
    @if($errors->any())<div class="gx-notice gx-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach<p>{{ __('Nếu có tệp đính kèm, vui lòng chọn lại trước khi lưu.') }}</p></div>@endif
    <div class="gx-editor">
        <form class="gx-form gx-panel" method="POST" enctype="multipart/form-data" action="{{ $gift->exists ? route('gifts.update', $gift) : route('gifts.store') }}" data-gift-form>
            @csrf @if($gift->exists) @method('PUT') @endif
            <input type="hidden" name="order_id" value="{{ old('order_id', $gift->order_id) }}">
            <span class="gx-kicker">01 / {{ __('Người thương & lời nhắn') }}</span>
            <div class="gx-fields"><label>{{ __('Người tặng') }}<input name="sender_name" maxlength="80" required value="{{ old('sender_name', $gift->sender_name) }}" autocomplete="name"></label><label>{{ __('Người nhận') }}<input name="recipient_name" maxlength="80" required value="{{ old('recipient_name', $gift->recipient_name) }}"></label></div>
            <label>{{ __('Lời nhắn của bạn') }}<textarea name="message" rows="6" maxlength="3000" required placeholder="{{ __('Có những điều, hương thơm nói hộ…') }}">{{ old('message', $gift->message) }}</textarea></label>
            <label>{{ __('Mùi hương đi cùng món quà') }}<select name="perfume_id"><option value="">{{ __('Chỉ gửi lời nhắn') }}</option>@foreach($perfumes as $perfume)<option value="{{ $perfume->id }}" @selected((string) old('perfume_id', $gift->perfume_id) === (string) $perfume->id)>{{ $perfume->localized_name }}</option>@endforeach</select></label>
            @if($gift->order_id)<small>{{ __('Gắn với đơn hàng') }} #{{ $gift->order_id }}. {{ __('Người nhận không thấy giá hoặc thông tin đơn hàng.') }}</small>@else<small>{{ __('Trang quà là thiệp cá nhân, không tự tạo hoặc thanh toán đơn hàng.') }}</small>@endif
            <hr><span class="gx-kicker">02 / {{ __('Thêm một kỷ niệm') }}</span>
            <label>{{ __('Ảnh kỷ niệm') }} <small>JPG / PNG / WEBP · {{ __('Tối đa 5 MB') }}</small><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
            @if($gift->photo_path)<img class="gx-photo-preview" src="{{ route('gifts.media', [$gift->token, 'photo']) }}" alt="{{ __('Ảnh kỷ niệm') }}"><label class="gx-check"><input type="checkbox" name="remove_photo" value="1">{{ __('Xóa ảnh hiện tại') }}</label>@endif
            <label>{{ __('Lời chúc bằng giọng nói') }}<small>MP3 / M4A / OGG / WAV / WEBM · {{ __('Tối đa 10 MB') }}</small><input type="file" name="audio" accept="audio/*,.webm" data-gift-audio></label>
            <div class="gx-recorder" data-gift-recorder data-recording="{{ __('Đang ghi âm… Bấm dừng khi hoàn tất.') }}" data-ready="{{ __('Đã ghi âm. Lưu trang quà để tải lời chúc lên.') }}" data-denied="{{ __('Không mở được micro. Bạn có thể tải tệp ghi âm ở phía trên.') }}" data-large="{{ __('Tệp ghi âm vượt quá 10 MB. Vui lòng ghi lại ngắn hơn.') }}"><div class="gx-actions"><button class="gx-button gx-button-light" type="button" data-record-start>{{ __('Ghi âm lời chúc') }}</button><button class="gx-button gx-button-light" type="button" data-record-stop hidden>{{ __('Dừng ghi âm') }}</button></div><small>{{ __('Ghi âm tối đa 2 phút. Chỉ bật micro khi bạn bấm ghi âm.') }}</small><p role="status" data-record-status></p><audio controls hidden data-record-preview></audio></div>
            @if($gift->audio_path)<audio controls preload="none" src="{{ route('gifts.media', [$gift->token, 'audio']) }}"></audio><label class="gx-check"><input type="checkbox" name="remove_audio" value="1">{{ __('Xóa ghi âm hiện tại') }}</label>@endif
            <hr><span class="gx-kicker">03 / {{ __('Mã riêng & thời hạn') }}</span>
            <label>{{ $gift->exists ? __('Đổi mã mở quà (để trống nếu giữ nguyên)') : __('Tạo mã mở quà gồm 6 chữ số') }}<input type="password" name="pin" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" @required(!$gift->exists)><small>{{ __('Hãy ghi nhớ và gửi mã riêng cho người nhận. Mã không nằm trong QR; đổi mã sẽ khóa các phiên đã mở trước đó.') }}</small></label>
            <label>{{ __('Hạn mở quà') }}<select name="days">@if($gift->exists)<option value="">{{ __('Giữ thời hạn hiện tại') }} · {{ $gift->expires_at->format('d/m/Y') }}</option>@endif @foreach([30,90,180] as $days)<option value="{{ $days }}" @selected((string) old('days', $gift->exists ? '' : 90) === (string) $days)>{{ __(':days ngày từ hôm nay', ['days' => $days]) }}</option>@endforeach</select></label>
            <p class="gx-hint">{{ __('Ảnh và ghi âm chỉ xem được sau khi mở khóa. Bạn có thể xóa toàn bộ trang quà bất cứ lúc nào.') }}</p>
            <button class="gx-button" type="submit" data-gift-save data-busy="{{ __('Đang lưu…') }}">{{ __('Lưu trang quà') }} <span aria-hidden="true">↗</span></button>
        </form>
        <aside class="gx-side">
            @if($gift->exists)
                <div class="gx-panel gx-share" data-gift-share data-url="{{ route('gifts.open', $gift->token) }}" data-qr-error="{{ __('Chưa tạo được QR. Hãy tải lại trang; bạn vẫn có thể sao chép đường dẫn.') }}" data-copied="{{ __('Đã sao chép đường dẫn.') }}" data-copy-error="{{ __('Hãy chọn và sao chép đường dẫn bên dưới.') }}">
                    <div class="gx-print-card">@include('partials.brand-mark', ['size' => 42])<span class="gx-kicker">A LITTLE SOMETHING, JUST FOR YOU</span><h2>{{ __('Dành riêng cho') }}<br><em>{{ $gift->recipient_name }}</em></h2><canvas data-gift-qr aria-label="{{ __('QR mở quà') }}" role="img"></canvas><p>{{ __('Quét QR. Nhập mã riêng. Mở một lời thương.') }}</p><small>{{ __('Từ') }} {{ $gift->sender_name }}</small></div>
                    <p class="gx-hint">{{ __('Gửi mã 6 chữ số riêng cho người nhận. Không in mã trên thiệp nếu muốn giữ riêng tư.') }}</p>
                    @if(in_array(request()->getHost(), ['localhost', '127.0.0.1'], true))<p class="gx-notice">{{ __('QR đang dùng địa chỉ máy của bạn. Để người nhận quét bằng điện thoại, hãy tạo thiệp trên website đã đưa lên mạng.') }}</p>@endif
                    @if($gift->isExpired())<p class="gx-notice">{{ __('Trang quà đã hết hạn. Hãy gia hạn trước khi gửi QR.') }}</p>@endif
                    <label class="gx-url-label">{{ __('Đường dẫn riêng') }}<input readonly value="{{ route('gifts.open', $gift->token) }}" data-gift-url></label>
                    <div class="gx-actions"><button class="gx-button gx-button-light" type="button" data-copy-gift>{{ __('Sao chép link') }}</button><a class="gx-button gx-button-light" data-download-qr hidden download="soopi-gift-qr.png">{{ __('Tải QR') }}</a><a class="gx-button gx-button-light" href="{{ route('gifts.card', $gift) }}">{{ __('In thiệp') }}</a><a class="gx-button gx-button-light" href="{{ route('gifts.preview', $gift) }}">{{ __('Xem trước') }}</a></div><p data-share-status role="status"></p>
                </div>
                <div class="gx-panel"><span class="gx-kicker">{{ __('Hành trình món quà') }}</span><p>{{ $gift->opened_at ? __('Đã mở quà') . ' · ' . $gift->opened_at->format('d/m/Y H:i') : __('Chờ người nhận mở') }}</p>@if($gift->thanked_at)<h3>{{ __('Lời cảm ơn dành cho bạn') }}</h3><blockquote>{{ $gift->thank_you }}</blockquote><small>{{ $gift->thanked_at->format('d/m/Y H:i') }}</small>@endif</div>
                <details class="gx-panel gx-delete"><summary>{{ __('Xóa trang quà') }}</summary><p>{{ __('Ảnh, ghi âm và lời cảm ơn sẽ bị xóa. QR cũ sẽ ngừng hoạt động. Không thể hoàn tác.') }}</p><form method="POST" action="{{ route('gifts.destroy', $gift) }}">@csrf @method('DELETE')<label class="gx-check"><input type="checkbox" required>{{ __('Tôi hiểu và muốn xóa trang quà này') }}</label><button class="gx-button" type="submit">{{ __('Xóa vĩnh viễn') }}</button></form></details>
            @else
                <div class="gx-editor-art"><img src="{{ asset('images/bloom/gift-atelier.webp') }}" alt="" width="640" height="800"><div><span class="gx-kicker">A SCENT. A MEMORY.</span><h2>{{ __('Một món quà.') }}<br><em>{{ __('Nhiều hơn một lời chúc.') }}</em></h2></div></div><div class="gx-panel"><h3>{{ __('Từ bạn, đến người thương.') }}</h3><p>{{ __('Sau khi lưu, bạn sẽ có QR để tải xuống hoặc in lên thiệp. Người nhận không cần tài khoản Soopi.') }}</p></div>
            @endif
        </aside>
    </div>
</section>
@endsection
