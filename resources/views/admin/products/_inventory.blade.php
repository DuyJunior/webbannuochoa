@php
    $baseVolume = (int) $value('volume_ml', 100);
    $saleValue = $value('sale_price');
    $basePrice = (float) ($saleValue !== null && $saleValue !== '' ? $saleValue : $value('price', 0));
@endphp
<section class="admin-card studio-form-section" aria-labelledby="product-stock">
    <span class="studio-form-kicker">{{ __('03 / GIÁ BÁN & TỒN KHO') }}</span>
    <h3 id="product-stock">{{ __('Giá và số lượng từng dung tích') }}</h3>
    <p class="studio-field-help">{{ __('Sửa giá bán và tổng số chai hiện có của từng dung tích rồi lưu thay đổi.') }}</p>
    <div class="product-inventory-grid">
        <div class="product-inventory-card">
            <h4>{{ __('Chai gốc') }} <span data-base-volume>{{ $baseVolume }}</span> ml</h4>
            @foreach([
                ['price', __('Giá niêm yết (₫)'), '', 999999999999, true],
                ['sale_price', __('Giá khuyến mãi (₫)'), '', 999999999999, false],
                ['stock', __('Kho chai gốc ').$baseVolume.' ml', 10, 999999999, true],
            ] as [$field, $label, $default, $max, $required])
            <div class="form-group mb-3">
                <label for="{{ $field }}"><span @if($field === 'stock') data-base-stock-label @endif>{{ $label }}</span> @if($required)<span class="text-danger">*</span>@endif</label>
                <input type="number" id="{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ $value($field, $default) }}" min="0" max="{{ $max }}" step="1" @required($required)>
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($field === 'sale_price')<small class="studio-field-help">{{ __('Để trống nếu không ưu đãi. Không vượt giá niêm yết.') }}</small>@endif
            </div>
            @endforeach
        </div>
        @foreach([10, 50, 5] as $volume)
        <div class="product-inventory-card" data-fixed-volume="{{ $volume }}" @if($volume === $baseVolume) hidden @endif>
            <h4>{{ $volume }} ml</h4>
            @if($volume !== 5)
                @php
                    $priceField = 'price_'.$volume.'ml';
                @endphp
                <div class="form-group mb-3">
                    <label for="{{ $priceField }}">{{ __('Giá bán (₫)') }}</label>
                    <input type="number" id="{{ $priceField }}" name="{{ $priceField }}" class="form-control @error($priceField) is-invalid @enderror" value="{{ $value($priceField) }}" min="0" max="999999999999" step="1" placeholder="{{ __('Tự động') }}" aria-describedby="{{ $priceField }}-help" @disabled($volume === $baseVolume)>
                    @error($priceField)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small id="{{ $priceField }}-help" class="studio-field-help">{{ __('Để trống để tính theo giá chai gốc. Giá tự động:') }} <strong data-size-auto-price="{{ $volume }}">{{ number_format($volume === 10 ? max(20000, round($basePrice * .22 / 10000) * 10000) : round($basePrice * .65 / 10000) * 10000, 0, ',', '.') }}₫</strong></small>
                </div>
            @else
                <p class="studio-field-help">{{ __('Mẫu 5 ml dùng trong hộp thử mùi và combo; giá bán theo gói.') }}</p>
            @endif
            @php
                $stockField = 'stock_'.$volume.'ml';
            @endphp
            <div class="form-group mb-0">
                <label for="{{ $stockField }}">{{ __('Tồn kho (chai)') }}</label>
                <input type="number" id="{{ $stockField }}" name="{{ $stockField }}" class="form-control @error($stockField) is-invalid @enderror" value="{{ $value($stockField, 0) }}" min="0" max="999999999" step="1" @disabled($volume === $baseVolume)>
                @error($stockField)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        @endforeach
    </div>
    @if(collect($variantRows)->contains(fn ($row) => is_array($row) && is_scalar($row['id'] ?? null) && (int) $row['id'] > 0))
    <p class="studio-field-help">{{ __('Các dung tích đã bổ sung tự động xuất hiện tại đây để sửa giá và tồn kho.') }}</p>
    <div class="product-inventory-grid" id="saved-variant-stock">
        @foreach($variantRows as $key => $row)
            @if(is_array($row) && is_scalar($row['id'] ?? null) && (int) $row['id'] > 0)
                @php
                    $stockIndex = $loop->index;
                    $stockVolume = is_scalar($row['volume_ml'] ?? null) ? $row['volume_ml'] : '';
                @endphp
                <div class="product-inventory-card">
                    <h4>{{ $stockVolume }} ml @if(empty($row['is_active']))<small>{{ __('Đã ẩn') }}</small>@endif</h4>
                    @foreach(['price' => __('Giá bán (₫)'), 'stock' => __('Kho :volume ml', ['volume' => $stockVolume])] as $field => $label)
                        <div class="form-group mb-3">
                            <label for="variant-{{ $stockIndex }}-{{ $field }}">{{ $label }} <span class="text-danger">*</span></label>
                            <input type="number" id="variant-{{ $stockIndex }}-{{ $field }}" name="variants[{{ $stockIndex }}][{{ $field }}]" class="form-control @error('variants.'.$key.'.'.$field) is-invalid @enderror" value="{{ is_scalar($row[$field] ?? null) ? $row[$field] : '' }}" min="0" max="{{ $field === 'price' ? 999999999999 : 999999999 }}" step="1" required>
                            @error('variants.'.$key.'.'.$field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
    @endif
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('studio-product-form');
    const refreshPrices = () => {
        const baseVolume = Number(form.elements.volume_ml.value);
        const basePrice = Number(form.elements.sale_price.value || form.elements.price.value || 0);
        form.querySelector('[data-base-volume]').textContent = form.elements.volume_ml.value;
        form.querySelector('[data-base-stock-label]').textContent = @json(__('Kho chai gốc ')) + form.elements.volume_ml.value + ' ml';
        form.querySelectorAll('[data-fixed-volume]').forEach(card => {
            card.hidden = Number(card.dataset.fixedVolume) === baseVolume;
            card.querySelectorAll('input').forEach(input => { input.disabled = card.hidden; });
        });
        form.querySelectorAll('[data-size-auto-price]').forEach(label => {
            const volume = Number(label.dataset.sizeAutoPrice);
            const price = Math.max(volume === 10 ? 20000 : 0, Math.round(basePrice * (volume === 10 ? .22 : .65) / 10000) * 10000);
            label.textContent = new Intl.NumberFormat(document.documentElement.lang || 'vi', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(price);
        });
    };
    ['price', 'sale_price', 'volume_ml'].forEach(id => form.elements.namedItem(id).addEventListener('input', refreshPrices));
    refreshPrices();
});
</script>
