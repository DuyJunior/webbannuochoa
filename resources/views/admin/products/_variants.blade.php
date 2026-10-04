<section class="admin-card studio-form-section product-variants" aria-labelledby="product-variants-title" id="product-variants">
    <span class="studio-form-kicker">{{ __('04 / DUNG TÍCH BỔ SUNG') }}</span>
    <div class="variant-heading"><div><h3 id="product-variants-title">{{ __('Dung tích bổ sung (không bắt buộc)') }}</h3><p class="studio-field-help">{{ __('Bạn có thể lưu sản phẩm ngay và thêm dung tích sau khi cần.') }}</p></div><button type="button" class="btn btn-primary" id="add-product-variant"><i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> {{ __('Thêm dung tích') }}</button></div>
    @error('variants')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    <div id="product-variant-rows">
        @foreach($variantRows as $key => $row)
            @if(is_array($row))
                @include('admin.products._variant-row', ['variantIndex' => $loop->index, 'variantRow' => $row, 'errorIndex' => $key])
            @endif
        @endforeach
    </div>
    <p class="variant-empty studio-field-help" id="variant-empty" @if(count($variantRows)) hidden @endif>{{ __('Chưa có dung tích bổ sung. Chỉ nhấn “Thêm dung tích” khi bạn muốn thêm.') }}</p>
    <p class="studio-field-help mb-0">{{ __('Sau khi lưu, dung tích mới tự xuất hiện trong mục Giá bán & tồn kho phía trên. Bạn có thể tắt mở bán dung tích đã lưu để giữ lịch sử đơn hàng.') }}</p>
    <p class="sr-only" role="status" aria-live="polite" id="variant-announcement"></p>
    <template id="product-variant-template">@include('admin.products._variant-row', ['variantIndex' => '__INDEX__', 'variantRow' => [], 'errorIndex' => '__INDEX__'])</template>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.getElementById('product-variant-rows');
    const add = document.getElementById('add-product-variant');
    const empty = document.getElementById('variant-empty');
    let index = Math.max(-1, ...[...rows.children].map(row => Number(row.dataset.variantIndex))) + 1;
    const syncRequired = row => {
        const inputs = [...row.querySelectorAll('[data-variant-field]')];
        const required = row.dataset.savedVariant === '1' || inputs.some(input =>
            input.validity.badInput || (input.value !== '' && input.value !== input.dataset.optionalDefault)
        );
        inputs.forEach(input => { input.required = required; });
        row.querySelectorAll('[data-variant-required]').forEach(marker => { marker.hidden = !required; });
    };
    const refresh = () => {
        empty.hidden = !!rows.children.length;
        add.disabled = rows.children.length >= 20;
        [...rows.children].forEach(syncRequired);
    };
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
    ['input', 'change'].forEach(type => rows.addEventListener(type, event => {
        const row = event.target.closest('.variant-row');
        if (row) syncRequired(row);
    }));
    refresh();
});
</script>
