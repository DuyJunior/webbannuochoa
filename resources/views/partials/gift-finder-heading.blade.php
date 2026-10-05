@php
    $giftRecipient = match(request('gender')) {
        'nu' => __('Quà tặng dành cho nàng'),
        'nam' => __('Quà tặng dành cho chàng'),
        'unisex' => __('Quà tặng không giới hạn'),
        default => __('Quà tặng cho người bạn thương'),
    };
    $giftOccasion = match(request('occasion')) {
        'tiec' => __('Sinh nhật / dịp đặc biệt'),
        'hang-ngay' => __('Một món quà mỗi ngày'),
        'hen-ho' => __('Một cuộc hẹn / kỷ niệm'),
        default => __('Mọi dịp'),
    };
@endphp
<header class="gift-finder-heading">
    <a class="ht-text-link" href="{{ route('home') }}#chon-qua">{{ __('← Trở lại chọn quà') }}</a>
    <span class="interior-kicker">SOOPI / THE ART OF GIVING</span>
    <h1>{{ $giftRecipient }}</h1>
    <p class="gift-finder-budget">{{ request()->filled('max_price') ? __('Tối đa :amount', ['amount' => number_format((int) request('max_price'), 0, ',', '.').'₫']) : __('Không giới hạn ngân sách') }}</p>
    <p>{{ $giftOccasion }} · {{ __('Gợi ý bên dưới đã áp dụng lựa chọn của bạn.') }}</p>
    @if(session('gift_finder_message'))
        <div class="gift-finder-note"><span>{{ __('Lời nhắn trên thiệp của bạn') }}</span><blockquote>{{ session('gift_finder_message') }}</blockquote><small>{{ __('Đã giữ lời nhắn. Khi thanh toán, chọn dịch vụ thiệp quà để gửi kèm đơn hàng.') }}</small></div>
    @endif
</header>
@push('styles')
<style>
.soopi-store .gift-finder-heading{max-width:850px;padding-bottom:20px}
.gift-finder-heading .interior-kicker{display:block;margin-top:24px}
.soopi-store .gift-finder-heading h1{font:400 clamp(32px,4vw,54px)/1.18 var(--ht-serif);margin:12px 0;color:#392333}
.gift-finder-budget{font:400 clamp(23px,2.5vw,32px)/1.3 var(--ht-serif);color:#95657d;margin:8px 0}
.gift-finder-heading>p:not(.gift-finder-budget){font-size:14px;line-height:1.7;color:#705763}
.gift-finder-note{border-left:2px solid #c7a0b2;padding:10px 20px;margin-top:18px;background:#f7f0f3;overflow-wrap:anywhere}
.gift-finder-note span,.gift-finder-note small{font-size:12px;line-height:1.6;color:#705763}
.gift-finder-note blockquote{font:italic 400 23px/1.4 var(--ht-serif);margin:6px 0;color:#49313f}
.gift-finder-adjust{border-block:1px solid #e4d2db;margin:0 0 24px;padding:14px 0}
.gift-finder-adjust summary{cursor:pointer;font-size:14px;color:#614353}
.gift-finder-adjust .ht-finder-form{margin-bottom:0}
.soopi-store .interior-finder:has(.gift-finder-heading){padding-top:30px}
.interior-finder:has(.gift-finder-heading) .ht-section-heading{margin:0 0 20px;padding:0 0 14px}
.soopi-store .interior-finder:has(.gift-finder-heading) .ht-section-heading h2{font-size:28px;margin:8px 0}
@media(min-width:1000px){.soopi-store .gift-finder-heading{max-width:none;display:grid;grid-template-columns:minmax(0,1.3fr) minmax(280px,.7fr);column-gap:40px}.gift-finder-heading>*{grid-column:1}.gift-finder-heading .gift-finder-note{grid-column:2;grid-row:2 / span 4;align-self:center}}
</style>
@endpush
