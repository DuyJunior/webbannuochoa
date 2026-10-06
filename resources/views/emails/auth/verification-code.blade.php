<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ __('Soopi · Mã xác thực email của bạn') }}</title></head>
<body style="margin:0;padding:0;background:#f4efec;color:#392632;font-family:Arial,Helvetica,sans-serif">
<table role="presentation" style="width:100%;border-collapse:collapse"><tr><td align="center" style="padding:24px 12px">
<table role="presentation" style="width:100%;max-width:600px;border-collapse:collapse;background:#fffdfb;border:1px solid #e4d9de">
<tr><td style="padding:28px 24px;border-bottom:1px solid #e4d9de">@include('emails.partials.brand')</td></tr>
<tr><td style="padding:30px 24px"><h1 style="font:normal 28px/1.3 Arial,Helvetica,sans-serif;margin:0 0 22px">{{ __('Chào mừng đến với Soopi.') }}</h1><p>{{ __('Chào') }} {{ $customerName }},</p><p style="font-size:14px;line-height:1.8">{{ __('Nhập mã sau trên trang xác thực của Soopi để hoàn tất đăng ký:') }}</p>
<p style="background:#f4ecef;border:1px solid #e4d9de;padding:22px;text-align:center;font:bold 34px/1.4 Arial,sans-serif;letter-spacing:8px;color:#49313f">{{ $code }}</p>
<p style="font-size:14px;line-height:1.8">{{ __('Mã có hiệu lực :minutes phút và chỉ sử dụng một lần.', ['minutes' => $expiresMinutes]) }}</p><p style="font-size:13px;line-height:1.8;color:#755e6b">{{ __('Chỉ dùng mã trong email mới nhất. Không chia sẻ mã OTP hoặc mật khẩu với bất kỳ ai.') }}</p></td></tr>
<tr><td style="padding:22px 24px;background:#f6eff2;border-top:1px solid #e4d9de"><p style="font:italic 20px Arial,Helvetica,sans-serif;margin:0 0 10px;color:#805b71">{{ __('Một chút hương. Một chút thương.') }}</p><p style="font-size:12px;line-height:1.8;margin:0">{{ __('Nếu bạn không tạo tài khoản tại Soopi, hãy bỏ qua email này.') }}</p></td></tr>
</table></td></tr></table></body></html>
