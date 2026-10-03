@php
    $variantValue = fn ($field, $default = '') => is_scalar($variantRow[$field] ?? null) ? $variantRow[$field] : $default;
    $savedVariant = (int) $variantValue('id', 0) > 0;
@endphp
<fieldset class="variant-row" data-variant-index="{{ $variantIndex }}">
    <legend>{{ $savedVariant ? 'Chai '.$variantValue('volume_ml').' ml' : 'Dung tích mới' }}</legend>
    @if($savedVariant)<input type="hidden" name="variants[{{ $variantIndex }}][id]" value="{{ $variantValue('id') }}">@endif
    <div class="variant-fields">
        @foreach([
            ['volume_ml', 'Dung tích (ml)', '', 1, 5000, '200'],
            ['price', 'Giá bán (₫)', '', 0, 999999999999, 'Nhập giá bán'],
            ['stock', 'Tồn kho (chai)', 0, 0, 999999999, '0'],
            ['weight', 'Khối lượng (g)', 200, 1, 50000, 'Gồm bao bì'],
        ] as [$field, $label, $default, $min, $max, $placeholder])
            <div class="form-group mb-0"><label for="variant-{{ $variantIndex }}-{{ $field }}">{{ $label }} <span class="text-danger">*</span></label>
                <input type="number" id="variant-{{ $variantIndex }}-{{ $field }}" name="variants[{{ $variantIndex }}][{{ $field }}]" class="form-control" value="{{ $variantValue($field, $default) }}" min="{{ $min }}" max="{{ $max }}" step="1" placeholder="{{ $placeholder }}" required @if($field === 'volume_ml') data-variant-volume @readonly($savedVariant) @endif>
                @error('variants.'.$errorIndex.'.'.$field)<small class="text-danger d-block" role="alert">{{ $message }}</small>@enderror
            </div>
        @endforeach
    </div>
    <div class="variant-row-footer"><label class="mb-0" for="variant-{{ $variantIndex }}-active"><input type="hidden" name="variants[{{ $variantIndex }}][is_active]" value="0"><input type="checkbox" id="variant-{{ $variantIndex }}-active" name="variants[{{ $variantIndex }}][is_active]" value="1" @checked($variantValue('is_active', true))> Mở bán dung tích này</label>
        @if(!$savedVariant)<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-variant>Bỏ dòng</button>@else<small class="text-muted">Giữ riêng giá và tồn kho</small>@endif
    </div>
</fieldset>
