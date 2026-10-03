<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ __('Mùi hương bạn chờ đã có hàng · Soopi') }}</title></head>
<body style="margin:0;padding:0;background:#f4efec;color:#392632;font-family:Arial,Helvetica,sans-serif">
<table role="presentation" style="width:100%;border-collapse:collapse"><tr><td align="center" style="padding:24px 12px">
<table role="presentation" style="width:100%;max-width:600px;border-collapse:collapse;background:#fffdfb;border:1px solid #e4d9de">
    <tr><td style="padding:28px 24px;border-bottom:1px solid #e4d9de">@include('emails.partials.brand')</td></tr>
    <tr><td style="padding:28px 24px;font-size:14px;line-height:1.8">
        <p style="margin:0 0 16px">{{ __('Xin chào,') }}</p>
        <p style="margin:0 0 24px"><strong>{{ $perfume->name }}</strong> {{ __('đã có hàng trở lại tại Soopi.') }}</p>
        @if($availableVolumes)<p style="margin:0 0 24px">{{ __('Dung tích đang có hàng:') }} {{ collect($availableVolumes)->map(fn ($volume) => $volume.' ml')->join(', ') }}{{ __('. Bạn có thể xem giá và chọn dung tích tại trang sản phẩm.') }}</p>@endif
        <table role="presentation" style="border-collapse:collapse;background:#392632"><tr><td style="padding:14px 22px"><a href="{{ route('perfumes.show', $perfume) }}" style="display:block;color:#fff9f5;font-size:14px;text-decoration:none">{{ __('Xem sản phẩm') }}</a></td></tr></table>
        <p style="margin:24px 0 0;color:#755e6b">Soopi</p>
    </td></tr>
</table></td></tr></table>
</body></html>
