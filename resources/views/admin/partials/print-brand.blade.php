<style>
.admin-print-brand{display:none}
@media print{
    .boutique-admin .admin-print-brand{display:flex!important;align-items:center;justify-content:space-between;gap:24px;margin:0 0 24px;padding:0 0 18px;border-bottom:1px solid #ddd3d8;break-inside:avoid;color:#392632}
    .admin-print-brand img{display:block;width:160px;height:auto}
    .admin-print-brand-copy{text-align:right}
    .admin-print-brand h2{margin:0;font-size:18px;font-weight:600}
    .admin-print-brand p{margin:6px 0 0;font-size:11px;line-height:1.6}
}
</style>
<div class="admin-print-brand">
    <img src="{{ asset('images/brand/soopi-petal-logo.png') }}" width="160" height="58" alt="SOOPI · Perfume Studio">
    <div class="admin-print-brand-copy">
        <h2>{{ $printTitle }}</h2>
        @if(!empty($printSubtitle))<p>{{ $printSubtitle }}</p>@endif
    </div>
</div>
