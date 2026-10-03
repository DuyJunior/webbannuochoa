@php
    $variantRows = old('variants', $currentProduct?->variants->toArray() ?? []);
    $variantRows = is_array($variantRows) ? $variantRows : [];
@endphp
<section class="admin-card studio-form-section product-variants" aria-labelledby="product-variants-title" id="product-variants">
    <span class="studio-form-kicker">{{ __('04 / DUNG TÍCH BỔ SUNG') }}</span>
    <div class="variant-heading"><div><h3 id="product-variants-title">{{ __('Thêm lựa chọn cho cùng sản phẩm') }}</h3><p class="studio-field-help">{{ __('Muốn bán thêm chai 200 ml? Thêm một dòng bên dưới, nhập giá và số chai rồi lưu sản phẩm. Chai gốc vẫn được giữ nguyên.') }}</p></div><button type="button" class="btn btn-primary" id="add-product-variant"><i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> {{ __('Thêm dung tích') }}</button></div>
    @error('variants')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    <div id="product-variant-rows">
        @foreach($variantRows as $key => $row)
            @if(is_array($row))
                @include('admin.products._variant-row', ['variantIndex' => $loop->index, 'variantRow' => $row, 'errorIndex' => $key])
            @endif
        @endforeach
    </div>
    <p class="variant-empty studio-field-help" id="variant-empty" @if(count($variantRows)) hidden @endif>{{ __('Chưa có dung tích bổ sung. Ví dụ: 200 ml · giá bán riêng · tồn kho riêng.') }}</p>
    <p class="studio-field-help mb-0">{{ __('Các dung tích 5, 10, 50 ml đã có ở phần kho phía trên. Dung tích đã lưu có thể tắt mở bán để giữ lịch sử đơn hàng.') }}</p>
    <p class="sr-only" role="status" aria-live="polite" id="variant-announcement"></p>
    <template id="product-variant-template">@include('admin.products._variant-row', ['variantIndex' => '__INDEX__', 'variantRow' => [], 'errorIndex' => '__INDEX__'])</template>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.getElementById('product-variant-rows');
    const add = document.getElementById('add-product-variant');
    const empty = document.getElementById('variant-empty');
    let index = Math.max(-1, ...[...rows.children].map(row => Number(row.dataset.variantIndex))) + 1;
    const refresh = () => { empty.hidden = !!rows.children.length; add.disabled = rows.children.length >= 20; };
    add.addEventListener('click', () => {
        if (rows.children.length >= 20) return;
        const html = document.getElementById('product-variant-template').innerHTML.replaceAll('__INDEX__', String(index++));
        rows.insertAdjacentHTML('beforeend', html);
        rows.lastElementChild.querySelector('[data-variant-volume]').focus({ preventScroll: false });
        document.getElementById('variant-announcement').textContent = (window.soopiT || (text => text))("Đã thêm dòng dung tích. Nhập số ml, giá bán, tồn kho và khối lượng.");
        refresh();
    });
    rows.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-variant]');
        if (!remove) return;
        remove.closest('.variant-row').remove();
        add.focus({ preventScroll: true });
        refresh();
    });
    refresh();
});
</script>
