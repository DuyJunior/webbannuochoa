<fieldset class="p-3 my-3 border rounded">
    <legend class="h6 px-2 w-auto">{{ __('Nội dung tiếng Anh') }}</legend>
    <p class="small text-muted">{{ __('Dùng khi khách chọn English. Nếu bỏ trống, website dùng bản dịch đã có cho nội dung gốc; nội dung mới chưa có bản dịch sẽ giữ nguyên.') }}</p>
    @foreach($englishFields as $field => $settings)
        @php
            $englishValue = old($field, $englishRecord->{$field});
            $englishValue = is_scalar($englishValue) ? $englishValue : '';
        @endphp
        <div class="form-group mb-3">
            <label for="english-{{ $field }}" class="form-label small font-weight-bold">{{ __($settings['label']) }}</label>
            @if(($settings['rows'] ?? 1) > 1)
                <textarea id="english-{{ $field }}" name="{{ $field }}" lang="en" rows="{{ $settings['rows'] }}" @if(isset($settings['max'])) maxlength="{{ $settings['max'] }}" @endif class="form-control @error($field) is-invalid @enderror" @error($field) aria-invalid="true" aria-describedby="english-{{ $field }}-error" @enderror>{{ $englishValue }}</textarea>
            @else
                <input id="english-{{ $field }}" name="{{ $field }}" lang="en" type="text" maxlength="{{ $settings['max'] ?? 255 }}" value="{{ $englishValue }}" class="form-control @error($field) is-invalid @enderror" @error($field) aria-invalid="true" aria-describedby="english-{{ $field }}-error" @enderror>
            @endif
            @error($field)<div class="invalid-feedback" id="english-{{ $field }}-error">{{ $message }}</div>@enderror
        </div>
    @endforeach
</fieldset>
