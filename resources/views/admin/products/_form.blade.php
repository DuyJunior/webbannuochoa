@php
    $editing = isset($product);
    $value = fn ($field, $default = '') => old($field, $editing ? ($product->{$field} ?? $default) : $default);
    $previewUrl = $value('image_url');
    $previewUrl = $previewUrl ? (\Illuminate\Support\Str::startsWith($previewUrl, ['http://', 'https://']) ? $previewUrl : asset(ltrim($previewUrl, '/'))) : null;
@endphp
<div class="studio-list-heading mb-4">
    <div><span class="studio-form-kicker">BỘ SƯU TẬP / {{ $editing ? 'CHỈNH SỬA' : 'TẠO MỚI' }}</span><h2>{{ $editing ? $product->name : 'Thêm sản phẩm mới' }}</h2><p class="text-muted mb-0">Thông tin, giá bán và tồn kho theo từng dung tích.</p></div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> Danh sách sản phẩm</a>
</div>

<form action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="studio-product-form">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="studio-form-layout">
        <div>
            <section class="admin-card studio-form-section" aria-labelledby="product-basics">
                <span class="studio-form-kicker">01 / THÔNG TIN</span><h3 id="product-basics">Nhận diện sản phẩm</h3><p class="studio-field-help">Các trường có dấu * cần được điền trước khi lưu.</p>
                <div class="row">
                    <div class="col-md-7 form-group">
                        <label for="name">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ $value('name') }}" maxlength="255" placeholder="Dior Sauvage Eau de Parfum" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5 form-group">
                        <label for="brand">Thương hiệu <span class="text-danger">*</span></label>
                        <input id="brand" name="brand" list="product-brands" class="form-control @error('brand') is-invalid @enderror" value="{{ $value('brand') }}" maxlength="120" placeholder="Chọn hoặc nhập thương hiệu" aria-describedby="brand-help" required>
                        <datalist id="product-brands">@foreach($brands as $brand)<option value="{{ $brand }}">@endforeach</datalist>
                        <small id="brand-help" class="studio-field-help">Chọn gợi ý hoặc nhập tên một thương hiệu mới.</small>
                        @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5 form-group">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" name="category_id" class="form-control @error('category_id') is-invalid @enderror"><option value="">Chưa phân loại</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($value('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($categories->isEmpty())<small class="studio-field-help">Chưa có danh mục. Bạn vẫn có thể lưu và phân loại sau.</small>@endif
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="gender">Dành cho <span class="text-danger">*</span></label>
                        <select id="gender" name="gender" class="form-control @error('gender') is-invalid @enderror" required>@foreach(['nam' => 'Nam', 'nu' => 'Nữ', 'unisex' => 'Unisex'] as $key => $label)<option value="{{ $key }}" @selected($value('gender', 'nam') === $key)>{{ $label }}</option>@endforeach</select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="concentration">Nồng độ</label>
                        <input id="concentration" name="concentration" class="form-control @error('concentration') is-invalid @enderror" value="{{ $value('concentration', 'EDP') }}" maxlength="50" placeholder="EDP, EDT, Parfum…">
                        @error('concentration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label for="description">Câu chuyện & mô tả hương thơm</label>
                    <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="5" maxlength="5000" placeholder="Mô tả các tầng hương, phong cách và thời điểm sử dụng…">{{ $value('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </section>
            <section class="admin-card studio-form-section" aria-labelledby="product-pricing">
                <span class="studio-form-kicker">02 / GIÁ & QUY CÁCH</span><h3 id="product-pricing">Giá bán và dung tích</h3>
                <div class="row">
                    @foreach([
                        ['price', 'Giá niêm yết (₫)', '', 0, 999999999999, true],
                        ['sale_price', 'Giá khuyến mãi (₫)', '', 0, 999999999999, false],
                        ['volume_ml', 'Dung tích chai gốc (ml)', 100, 1, 5000, true],
                        ['weight', 'Khối lượng tính phí (gram)', 200, 1, 50000, true],
                    ] as [$field, $label, $default, $min, $max, $required])
                    <div class="col-sm-6 form-group">
                        <label for="{{ $field }}">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
                        <input type="number" id="{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ $value($field, $default) }}" min="{{ $min }}" max="{{ $max }}" step="1" @required($required)>
                        @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($field === 'sale_price')<small class="studio-field-help">Để trống nếu không ưu đãi. Không vượt giá niêm yết.</small>@endif
                        @if($field === 'volume_ml')<div class="d-flex flex-wrap mt-2">@foreach([10, 30, 50, 75, 100, 125, 200] as $volume)<button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1" data-product-preset="volume_ml" data-value="{{ $volume }}" aria-pressed="false">{{ $volume }}ml</button>@endforeach</div>@endif
                        @if($field === 'weight')<small class="studio-field-help">Khối lượng dùng để tính phí vận chuyển.</small><div class="d-flex flex-wrap mt-2">@foreach([100, 200, 350, 500] as $weight)<button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1" data-product-preset="weight" data-value="{{ $weight }}" aria-pressed="false">{{ $weight }}g</button>@endforeach</div>@endif
                    </div>
                    @endforeach
                </div>
                @if($editing)<p class="studio-field-help mb-0"><i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i> Sản phẩm đã có đơn hàng cần giữ nguyên dung tích chai gốc để bảo toàn lịch sử kho.</p>@endif
            </section>
            <section class="admin-card studio-form-section" aria-labelledby="product-stock">
                <span class="studio-form-kicker">03 / TỒN KHO</span><h3 id="product-stock">Số lượng từng dung tích</h3><p class="studio-field-help">Nhập số chai thực tế của từng dung tích. Hệ thống không tự quy đổi từ chai lớn.</p>
                <div class="row">
                    @foreach(['stock' => 'Kho dung tích gốc', 'stock_5ml' => 'Kho mẫu thử 5ml', 'stock_10ml' => 'Kho chiết 10ml', 'stock_50ml' => 'Kho chai 50ml'] as $field => $label)
                    <div class="col-sm-6 col-xl-3 form-group mb-3">
                        <label for="{{ $field }}">{{ $label }} @if($field === 'stock')<span class="text-danger">*</span>@endif</label>
                        <input type="number" id="{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ $value($field, $field === 'stock' ? 10 : 0) }}" min="0" max="999999999" step="1" @required($field === 'stock')>
                        @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endforeach
                </div>
            </section>
        </div>
        <aside class="studio-form-aside">
            <section class="admin-card studio-form-section" aria-labelledby="product-media">
                <span class="studio-form-kicker">HÌNH ẢNH & NỘI DUNG</span><h3 id="product-media">Ảnh & video sản phẩm</h3>
                <div class="studio-product-preview mb-3"><img id="product-image-preview" @if($previewUrl) src="{{ $previewUrl }}" @else hidden @endif alt="Ảnh xem trước sản phẩm"><span id="product-image-placeholder" @if($previewUrl) hidden @endif><i class="fa-regular fa-image" aria-hidden="true"></i> Xem trước hình ảnh</span></div>
                <div class="form-group">
                    <label for="image_file">Tải ảnh sản phẩm</label>
                    <input type="file" id="image_file" name="image_file" accept="image/jpeg,image/png,image/webp" class="form-control-file @error('image_file') is-invalid @enderror" aria-describedby="image-help">
                    <small class="studio-field-help" id="image-help">JPG, PNG hoặc WEBP · Tối đa 5 MB. Tệp tải lên được ưu tiên hơn đường dẫn.</small>
                    @error('image_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="image_url">Hoặc dùng đường dẫn ảnh</label>
                    <input id="image_url" name="image_url" class="form-control @error('image_url') is-invalid @enderror" value="{{ $value('image_url') }}" maxlength="2048" placeholder="https://… hoặc images/…">
                    @error('image_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group mb-0">
                    <label for="video_url">Video review (tùy chọn)</label>
                    <input id="video_url" name="video_url" class="form-control @error('video_url') is-invalid @enderror" value="{{ $value('video_url') }}" maxlength="2048" placeholder="Liên kết YouTube hoặc Shorts">
                    @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </section>
            <section class="admin-card studio-form-section" aria-labelledby="product-visibility">
                <span class="studio-form-kicker">XUẤT BẢN</span><h3 id="product-visibility">Hiển thị trên cửa hàng</h3>
                <input type="hidden" name="is_active" value="0">
                <div class="custom-control custom-switch"><input type="checkbox" id="is_active" name="is_active" class="custom-control-input" value="1" @checked($value('is_active', true))><label for="is_active" class="custom-control-label">Mở bán sản phẩm</label></div>
                <p class="studio-field-help mt-3 mb-0">Tắt để lưu sản phẩm ở trạng thái ẩn. Bạn có thể mở bán khi đã hoàn thiện nội dung.</p>
            </section>
        </aside>
    </div>
    <div class="studio-form-actions"><span class="studio-field-help">Kiểm tra giá và tồn kho trước khi lưu.</span><div><a href="{{ $editing ? route('admin.products.show', $product) : route('admin.products.index') }}" class="btn btn-outline-secondary mr-2">Hủy thay đổi</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-check mr-1" aria-hidden="true"></i> {{ $editing ? 'Lưu thay đổi' : 'Thêm sản phẩm' }}</button></div></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('studio-product-form');
    const syncPresets = () => form.querySelectorAll('[data-product-preset]').forEach(button => {
        const selected = form.elements.namedItem(button.dataset.productPreset).value === button.dataset.value;
        button.classList.toggle('btn-primary', selected);
        button.classList.toggle('btn-outline-secondary', !selected);
        button.setAttribute('aria-pressed', String(selected));
    });
    form.querySelectorAll('[data-product-preset]').forEach(button => button.addEventListener('click', () => {
        const input = form.elements.namedItem(button.dataset.productPreset);
        input.value = button.dataset.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }));
    form.querySelectorAll('#volume_ml, #weight').forEach(input => input.addEventListener('input', syncPresets));
    syncPresets();
    const brand = form.querySelector('#brand');
    form.querySelector('#name').addEventListener('blur', event => {
        if (brand.value.trim()) return;
        const name = event.target.value.toLocaleLowerCase();
        const match = [...form.querySelectorAll('#product-brands option')]
            .sort((a, b) => b.value.length - a.value.length)
            .find(option => name.includes(option.value.toLocaleLowerCase()));
        if (match) brand.value = match.value;
    });
    const image = form.querySelector('#product-image-preview');
    const placeholder = form.querySelector('#product-image-placeholder');
    const fileInput = form.querySelector('#image_file');
    const urlInput = form.querySelector('#image_url');
    let objectUrl;
    function preview() {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        const file = fileInput.files[0];
        let source = urlInput.value.trim();
        if (file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) source = objectUrl = URL.createObjectURL(file);
        else if (/^\/?images\//i.test(source)) source = new URL(source.replace(/^\//, ''), @json(rtrim(url('/'), '/') . '/')).href;
        else if (!/^https?:\/\//i.test(source)) source = '';
        image.hidden = !source;
        placeholder.hidden = !!source;
        if (source) image.src = source;
        else image.removeAttribute('src');
    }
    image.addEventListener('error', () => { image.hidden = true; placeholder.hidden = false; });
    fileInput.addEventListener('change', preview);
    urlInput.addEventListener('change', preview);
});
</script>
