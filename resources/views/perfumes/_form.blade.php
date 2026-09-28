@php($editing = isset($perfume))

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Vui lòng kiểm tra lại thông tin:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-layout">
    <section class="form-card">
        <div class="form-card-heading">
            <h2>Thông tin cơ bản</h2>
        </div>
        <div class="form-grid">
            <label class="field field-wide">
                <span>Tên nước hoa <b>*</b></span>
                <input type="text" name="name" value="{{ old('name', $perfume->name ?? '') }}" maxlength="255" required placeholder="Ví dụ: Bleu de Chanel Eau de Parfum">
                @error('name')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Thương hiệu <b>*</b></span>
                <input type="text" name="brand" value="{{ old('brand', $perfume->brand ?? '') }}" maxlength="120" required placeholder="Chanel">
                @error('brand')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Danh mục</span>
                <select name="category_id">
                    <option value="">Chưa phân loại</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $perfume->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @if ($categories->isEmpty())
                    <small>Chưa có danh mục. <a href="{{ route('categories.create') }}">Tạo danh mục</a></small>
                @endif
                @error('category_id')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Giới tính <b>*</b></span>
                <select name="gender" required>
                    <option value="nam" @selected(old('gender', $perfume->gender ?? '') === 'nam')>Nam</option>
                    <option value="nu" @selected(old('gender', $perfume->gender ?? '') === 'nu')>Nữ</option>
                    <option value="unisex" @selected(old('gender', $perfume->gender ?? 'unisex') === 'unisex')>Unisex</option>
                </select>
                @error('gender')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Nồng độ</span>
                <input type="text" name="concentration" value="{{ old('concentration', $perfume->concentration ?? '') }}" maxlength="50" placeholder="EDP, EDT, Parfum...">
                @error('concentration')<small class="field-error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="form-card">
        <div class="form-card-heading">
            <h2>Giá và kho hàng</h2>
        </div>
        <div class="form-grid form-grid-three">
            <label class="field">
                <span>Dung tích (ml) <b>*</b></span>
                <input type="number" name="volume_ml" value="{{ old('volume_ml', $perfume->volume_ml ?? 100) }}" min="1" max="5000" required>
                @error('volume_ml')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Khối lượng tính phí GHN (gram) <b>*</b></span>
                <input type="number" name="weight" value="{{ old('weight', $perfume->weight ?? 200) }}" min="1" max="50000" required placeholder="200">
                @error('weight')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Giá niêm yết (₫) <b>*</b></span>
                <input type="number" name="price" value="{{ old('price', $perfume->price ?? '') }}" min="0" required placeholder="2500000">
                @error('price')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Giá khuyến mãi (₫)</span>
                <input type="number" name="sale_price" value="{{ old('sale_price', $perfume->sale_price ?? '') }}" min="0" placeholder="Để trống nếu không giảm">
                @error('sale_price')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Tồn kho Fullbox (100ml) <b>*</b></span>
                <input type="number" name="stock" value="{{ old('stock', $perfume->stock ?? 0) }}" min="0" required>
                @error('stock')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Tồn kho Chiết (10ml)</span>
                <input type="number" name="stock_10ml" value="{{ old('stock_10ml', $perfume->stock_10ml ?? '') }}" min="0" placeholder="Chưa nhập kho: 0">
                @error('stock_10ml')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Tồn kho Chai vừa (50ml)</span>
                <input type="number" name="stock_50ml" value="{{ old('stock_50ml', $perfume->stock_50ml ?? '') }}" min="0" placeholder="Chưa nhập kho: 0">
                <label style="display:block;margin-top:12px">Kho mẫu thử 5ml (Discovery Box)</label>
                <input class="form-control" type="number" name="stock_5ml" min="0" value="{{ old('stock_5ml', $perfume->stock_5ml ?? 0) }}">
                <small>Nhập số lượng thực tế từng dung tích. Không tự quy đổi từ chai lớn.</small>
                @error('stock_50ml')<small class="field-error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="form-card">
        <div class="form-card-heading">
            <h2>Hình ảnh và mô tả</h2>
        </div>
        <div class="form-grid">
            <label class="field field-wide">
                <span>Tải ảnh sản phẩm từ máy</span>
                <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
                <small>Định dạng JPG, PNG hoặc WEBP, dung lượng tối đa 5MB.</small>
                @error('image_file')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field field-wide">
                <span>Đường dẫn hình ảnh</span>
                <input type="text" name="image_url" value="{{ old('image_url', $perfume->image_url ?? '') }}" placeholder="https://example.com/perfume.jpg hoặc images/products/ten-anh.jpg">
                <small>Không bắt buộc nếu bạn đã chọn ảnh từ máy.</small>
                @error('image_url')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field field-wide">
                <span>Mô tả sản phẩm</span>
                <textarea name="description" rows="6" maxlength="5000" placeholder="Mô tả hương đầu, hương giữa, hương cuối và phong cách...">{{ old('description', $perfume->description ?? '') }}</textarea>
                @error('description')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="checkbox-field field-wide">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $perfume->is_active ?? true))>
                <span>Hiển thị sản phẩm trên cửa hàng</span>
            </label>
        </div>
    </section>
</div>

<div class="form-actions">
    <a class="btn btn-ghost" href="{{ $editing ? route('perfumes.show', $perfume) : route('perfumes.index') }}">Hủy</a>
    <button class="btn btn-primary" type="submit">{{ $editing ? 'Lưu thay đổi' : 'Thêm sản phẩm' }}</button>
</div>
