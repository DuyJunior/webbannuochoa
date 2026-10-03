<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
    <title>{{ __('Phiên làm việc đã hết hạn · Soopi') }}</title>
    @include('partials.brand-favicon')
    @include('partials.localization')
    <style>
        @font-face{font-family:Lora;src:url('{{ asset('fonts/lora-regular.ttf') }}') format('truetype');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#faf6f2;color:#3b2533;font:15px/1.8 system-ui,sans-serif;min-height:100svh;display:grid;place-items:center;padding:32px 20px}main{width:100%;max-width:640px;padding:clamp(24px,6vw,64px);background:#fffdfb;border:1px solid #e3d5d9;border-radius:20px;box-shadow:0 24px 70px -45px #67384f66}.brand{width:170px;margin-bottom:40px}.eyebrow{font-size:11px;letter-spacing:.16em;color:#896076;text-transform:uppercase}h1{font:400 clamp(30px,6vw,44px)/1.25 Lora,serif;margin:15px 0 24px}p{color:#755e6b;margin:0 0 16px}.actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}.actions a,.actions button{font:500 14px/1.5 system-ui,sans-serif;padding:13px 20px;border:1px solid #d7c4ce;border-radius:7px;text-decoration:none;cursor:pointer;color:#442537;background:#fffdfb}.actions a{background:#442537;color:#fffaf6;border-color:#442537}a:focus-visible,button:focus-visible{outline:3px solid #a76f8e;outline-offset:4px}.note{font-size:12px;margin:26px 0 0}button[hidden]{display:none}
    </style>
</head>
<body><main>
    <div class="brand">@include('partials.brand-logo')</div>
    <span class="eyebrow">{{ __('419 / PHIÊN LÀM VIỆC') }}</span><h1>{{ __('Mình kết nối lại nhé.') }}</h1>
    <p>{{ __('Phiên làm việc hoặc mã bảo vệ biểu mẫu đã hết hạn. Yêu cầu vừa gửi chưa được xử lý.') }}</p>
    <p>{{ __('Nếu đang nhập nội dung, hãy quay lại biểu mẫu và sao chép phần cần giữ. Sau đó mở lại trang hoặc đăng nhập lại trước khi lưu.') }}</p>
    <div class="actions"><button type="button" id="return-to-form" hidden>{{ __('Quay lại biểu mẫu') }}</button><a href="{{ request()->is('admin', 'admin/*') ? route('admin.login') : route('login') }}">{{ __('Đăng nhập lại') }}</a></div>
    <p class="note">{{ __('Cần hỗ trợ?') }} <a href="{{ route('store.contact') }}">{{ __('Liên hệ Soopi ↗') }}</a></p>
    <script>const backButton=document.getElementById('return-to-form');if(history.length>1){backButton.hidden=false;backButton.addEventListener('click',()=>history.back());}</script>
</main></body></html>
