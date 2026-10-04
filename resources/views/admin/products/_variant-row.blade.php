@php
    $variantValue = fn ($field, $default = '') => is_scalar($variantRow[$field] ?? null) ? $variantRow[$field] : $default;
    $savedVariant = (int) $variantValue('id', 0) > 0;
@endphp
<fieldset class="variant-row" data-variant-index="{{ $variantIndex }}" data-saved-variant="{{ $savedVariant ? '1' : '0' }}">
    <legend>{{ $savedVariant ? 'Chai '.$variantValue('volume_ml').' ml' : __('Dung tích mới') }}</legend>
    @if($savedVariant)<input type="hidden" name="variants[{{ $variantIndex }}][id]" value="{{ $variantValue('id') }}">@endif
    @if(!$savedVariant)<p class="studio-field-help">{{ __('Dòng chưa nhập sẽ được bỏ qua khi lưu. Khi muốn thêm, hãy điền đủ thông tin bên dưới.') }}</p>@endif
    <div class="variant-fields">
        @foreach([
            ['volume_ml', __('Dung tích (ml)'), '', 1, 5000, '200'],
            ['price', __('Giá bán (₫)'), '', 0, 999999999999, __('Nhập giá bán')],
            ['stock', __('Tồn kho (chai)'), 0, 0, 999999999, '0'],
            ['weight', __('Khối lượng (g)'), 200, 1, 50000, __('Gồm bao bì')],
        ] as [$field, $label, $default, $min, $max, $placeholder])
            @continue($savedVariant && in_array($field, ['price', 'stock'], true))
            <div class="form-group mb-0"><label for="variant-{{ $variantIndex }}-{{ $field }}">{{ __($label) }} <span class="text-danger" data-variant-required @if(!$savedVariant) hidden @endif>*</span></label>
                <input type="number" id="variant-{{ $variantIndex }}-{{ $field }}" name="variants[{{ $variantIndex }}][{{ $field }}]" class="form-control" value="{{ $variantValue($field, $default) }}" min="{{ $min }}" max="{{ $max }}" step="1" placeholder="{{ $placeholder }}" data-variant-field data-optional-default="{{ $default }}" @required($savedVariant) @if($field === 'volume_ml') data-variant-volume @readonly($savedVariant) @endif>
                @error('variants.'.$errorIndex.'.'.$field)<small class="text-danger d-block" role="alert">{{ $message }}</small>@enderror
            </div>
        @endforeach
    </div>
    <div class="variant-row-footer"><label class="mb-0" for="variant-{{ $variantIndex }}-active"><input type="hidden" name="variants[{{ $variantIndex }}][is_active]" value="0"><input type="checkbox" id="variant-{{ $variantIndex }}-active" name="variants[{{ $variantIndex }}][is_active]" value="1" @checked($variantValue('is_active', true))> {{ __('Mở bán dung tích này') }}</label>
        @if(!$savedVariant)<button type="button" class="btn btn-sm btn-outline-secondary" data-remove-variant>{{ __('Bỏ dòng') }}</button>@else<a href="#variant-{{ $variantIndex }}-stock" class="btn btn-sm btn-outline-secondary">{{ __('Sửa giá & tồn kho') }}</a>@endif
    </div>
</fieldset>
