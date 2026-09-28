@extends('layouts.admin')

@section('title', 'Thêm sản phẩm mới')
@section('page_title', 'Tạo sản phẩm nước hoa mới')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="font-weight-bold mb-0" style="color: #0f172a;">Thông tin sản phẩm</h5>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Quay lại danh sách
                </a>
            </div>

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="name" class="font-weight-bold" style="font-size:0.9rem;">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="Ví dụ: Dior Sauvage Eau De Parfum" required autofocus>
                    </div>
                    @php
                        $selectedBrand = old('brand');
                        $brandList = $brands ?? [];
                        $isCustomBrand = $selectedBrand && !in_array($selectedBrand, $brandList);
                    @endphp
                    <div class="col-md-6 form-group">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="brand_select" class="font-weight-bold mb-0" style="font-size:0.9rem;">Thương hiệu <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="toggleBrandBtn" style="font-size:0.83rem;">
                                <i class="fa-solid fa-pen-to-square mr-1"></i><span id="toggleBrandText">{{ $isCustomBrand ? 'Chọn từ danh sách' : 'Nhập hãng mới' }}</span>
                            </button>
                        </div>

                        {{-- Menu chọn thương hiệu sổ xuống --}}
                        <div id="brandSelectWrap" style="{{ $isCustomBrand ? 'display: none;' : '' }}">
                            <select id="brand_select" class="form-control" {{ $isCustomBrand ? '' : 'name="brand"' }} {{ $isCustomBrand ? '' : 'required' }}>
                                <option value="">-- Chọn thương hiệu nước hoa --</option>
                                @foreach ($brandList as $b)
                                    <option value="{{ $b }}" {{ (!$isCustomBrand && $selectedBrand == $b) ? 'selected' : '' }}>
                                        {{ $b }}
                                    </option>
                                @endforeach
                                <option value="__other__">+ Nhập thương hiệu khác...</option>
                            </select>
                        </div>

                        {{-- Ô nhập tay khi muốn thêm thương hiệu mới ngoài danh sách --}}
                        <div id="brandInputWrap" style="{{ $isCustomBrand ? '' : 'display: none;' }}">
                            <div class="input-group">
                                <input type="text" id="brand_custom" class="form-control" {{ $isCustomBrand ? 'name="brand"' : '' }} value="{{ $isCustomBrand ? $selectedBrand : '' }}" placeholder="Nhập tên thương hiệu mới (VD: Le Labo, Roja...)" maxlength="120" {{ $isCustomBrand ? 'required' : '' }}>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="btnBackToSelect" title="Quay lại danh sách chọn">
                                        <i class="fa-solid fa-list mr-1"></i> Danh sách
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Nhập tên thương hiệu nếu chưa có trong danh sách sổ xuống.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="category_id" class="font-weight-bold" style="font-size:0.9rem;">Danh mục</label>
                        <select name="category_id" id="category_id" class="form-control">
                            <option value="">-- Chọn danh mục --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="gender" class="font-weight-bold" style="font-size:0.9rem;">Giới tính <span class="text-danger">*</span></label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="nam" {{ old('gender') == 'nam' ? 'selected' : '' }}>Nam</option>
                            <option value="nu" {{ old('gender') == 'nu' ? 'selected' : '' }}>Nữ</option>
                            <option value="unisex" {{ old('gender') == 'unisex' ? 'selected' : '' }}>Unisex</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="concentration" class="font-weight-bold" style="font-size:0.9rem;">Nồng độ</label>
                        <input type="text" name="concentration" id="concentration" class="form-control" value="{{ old('concentration', 'EDP') }}" placeholder="EDP, EDT, Parfum...">
                    </div>
                </div>

                {{-- Price, Dung tích & Khối lượng section --}}
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label for="volume_ml" class="font-weight-bold" style="font-size:0.9rem;">
                            Dung tích chai gốc (ml) <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="volume_ml" id="volume_ml" class="form-control" value="{{ old('volume_ml', 100) }}" min="1" required>
                        
                        {{-- Nút chọn nhanh dung tích --}}
                        <div class="mt-2 d-flex flex-wrap gap-1" style="gap: 4px;">
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-volume" data-val="10">10ml</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-volume" data-val="30">30ml</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-volume" data-val="50">50ml</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-volume" data-val="75">75ml</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-volume active" data-val="100">100ml</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-volume" data-val="125">125ml</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-volume" data-val="200">200ml</button>
                        </div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="weight" class="font-weight-bold" style="font-size:0.9rem;">
                            Khối lượng tính phí (gram) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" name="weight" id="weight" class="form-control" value="{{ old('weight', 200) }}" min="1" max="50000" required placeholder="200">
                            <div class="input-group-append">
                                <span class="input-group-text font-weight-bold">g</span>
                            </div>
                        </div>
                        <div class="mt-2 d-flex flex-wrap gap-1" style="gap: 4px;">
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-weight" data-val="100">100g</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-weight active" data-val="200">200g</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-weight" data-val="350">350g</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 quick-weight" data-val="500">500g</button>
                        </div>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="price" class="font-weight-bold" style="font-size:0.9rem;">Giá niêm yết (₫) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="price" class="form-control" value="{{ old('price') }}" min="0" required placeholder="1500000">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="sale_price" class="font-weight-bold" style="font-size:0.9rem;">Giá khuyến mãi (₫)</label>
                        <input type="number" name="sale_price" id="sale_price" class="form-control" value="{{ old('sale_price') }}" min="0" placeholder="Để trống nếu không có">
                    </div>
                </div>

                {{-- Tồn kho section --}}
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="stock" class="font-weight-bold" style="font-size:0.9rem;">Kho 100ml <span class="text-danger">*</span></label>
                        <input type="number" name="stock" id="stock" class="form-control" value="{{ old('stock', 10) }}" min="0" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="stock_10ml" class="font-weight-bold" style="font-size:0.9rem;">Kho Chiết 10ml</label>
                        <input type="number" name="stock_10ml" id="stock_10ml" class="form-control" value="{{ old('stock_10ml') }}" min="0" placeholder="Chưa nhập kho: 0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="stock_50ml" class="font-weight-bold" style="font-size:0.9rem;">Kho Chai 50ml</label>
                        <input type="number" name="stock_50ml" id="stock_50ml" class="form-control" value="{{ old('stock_50ml') }}" min="0" placeholder="Chưa nhập kho: 0">
                <label style="display:block;margin-top:12px">Kho mẫu thử 5ml (Discovery Box)</label>
                <input class="form-control" type="number" name="stock_5ml" min="0" value="{{ old('stock_5ml', $product->stock_5ml ?? 0) }}">
                <small>Nhập số lượng thực tế từng dung tích. Không tự quy đổi từ chai lớn.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="image_url" class="font-weight-bold" style="font-size:0.9rem;">Đường dẫn ảnh (URL hoặc images/...)</label>
                        <input type="text" name="image_url" id="image_url" class="form-control" value="{{ old('image_url') }}" placeholder="images/products/amber-glass.jpg">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="image_file" class="font-weight-bold" style="font-size:0.9rem;">Hoặc tải tệp ảnh lên</label>
                        <input type="file" name="image_file" id="image_file" class="form-control-file">
                    </div>
                </div>

                <div class="form-group">
                    <label for="video_url" class="font-weight-bold" style="font-size:0.9rem;">
                        <i class="fa-solid fa-video text-danger mr-1"></i> Link Video Review / YouTube / Shorts (Tùy chọn)
                    </label>
                    <input type="text" name="video_url" id="video_url" class="form-control" value="{{ old('video_url') }}" placeholder="https://www.youtube.com/watch?v=... hoặc https://www.youtube.com/shorts/...">
                    <small class="text-muted">Khi thêm link video, khách hàng có thể xem video trải nghiệm mùi hương trực tiếp ngay tại trang sản phẩm.</small>
                </div>

                <div class="form-group">
                    <label for="description" class="font-weight-bold" style="font-size:0.9rem;">Mô tả sản phẩm</label>
                    <textarea name="description" id="description" rows="4" class="form-control" placeholder="Mô tả hương thơm, các tầng hương (top/heart/base note)...">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="is_active" id="is_active" class="custom-control-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="is_active">Kích hoạt hiển thị sản phẩm ngay sau khi tạo</label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4" style="gap:10px;">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-light px-4">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                        <i class="fa-solid fa-check mr-1"></i> Lưu sản phẩm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const volumeInput = document.getElementById('volume_ml');
    const quickButtons = document.querySelectorAll('.quick-volume');
    const weightInput = document.getElementById('weight');
    const quickWeightButtons = document.querySelectorAll('.quick-weight');

    function highlightActive() {
        const volume = parseInt(volumeInput ? volumeInput.value : 0) || 0;
        quickButtons.forEach(btn => {
            if (btn.getAttribute('data-val') == volume) {
                btn.classList.add('btn-primary', 'text-white');
                btn.classList.remove('btn-outline-secondary');
            } else {
                btn.classList.remove('btn-primary', 'text-white');
                btn.classList.add('btn-outline-secondary');
            }
        });

        const weight = parseInt(weightInput ? weightInput.value : 0) || 0;
        quickWeightButtons.forEach(btn => {
            if (btn.getAttribute('data-val') == weight) {
                btn.classList.add('btn-primary', 'text-white');
                btn.classList.remove('btn-outline-secondary');
            } else {
                btn.classList.remove('btn-primary', 'text-white');
                btn.classList.add('btn-outline-secondary');
            }
        });
    }

    quickButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            if (volumeInput) volumeInput.value = this.getAttribute('data-val');
            highlightActive();
        });
    });

    quickWeightButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            if (weightInput) weightInput.value = this.getAttribute('data-val');
            highlightActive();
        });
    });

    if (volumeInput) volumeInput.addEventListener('input', highlightActive);
    if (weightInput) weightInput.addEventListener('input', highlightActive);
    highlightActive();

    // Xử lý chuyển đổi Thương hiệu (Sổ xuống / Nhập tay)
    const brandSelect = document.getElementById('brand_select');
    const brandSelectWrap = document.getElementById('brandSelectWrap');
    const brandInputWrap = document.getElementById('brandInputWrap');
    const brandCustom = document.getElementById('brand_custom');
    const toggleBrandBtn = document.getElementById('toggleBrandBtn');
    const toggleBrandText = document.getElementById('toggleBrandText');
    const btnBackToSelect = document.getElementById('btnBackToSelect');

    function switchToCustom() {
        if (!brandSelectWrap || !brandInputWrap) return;
        brandSelectWrap.style.display = 'none';
        brandInputWrap.style.display = 'block';
        if (brandSelect) {
            brandSelect.removeAttribute('name');
            brandSelect.removeAttribute('required');
        }
        if (brandCustom) {
            brandCustom.setAttribute('name', 'brand');
            brandCustom.setAttribute('required', 'required');
            brandCustom.focus();
        }
        if (toggleBrandText) toggleBrandText.innerText = 'Chọn từ danh sách';
    }

    function switchToSelect() {
        if (!brandSelectWrap || !brandInputWrap) return;
        brandInputWrap.style.display = 'none';
        brandSelectWrap.style.display = 'block';
        if (brandCustom) {
            brandCustom.removeAttribute('name');
            brandCustom.removeAttribute('required');
        }
        if (brandSelect) {
            brandSelect.setAttribute('name', 'brand');
            brandSelect.setAttribute('required', 'required');
            if (brandSelect.value === '__other__') {
                brandSelect.value = '';
            }
        }
        if (toggleBrandText) toggleBrandText.innerText = 'Nhập hãng mới';
    }

    if (brandSelect) {
        brandSelect.addEventListener('change', function () {
            if (this.value === '__other__') {
                switchToCustom();
            }
        });
    }

    if (toggleBrandBtn) {
        toggleBrandBtn.addEventListener('click', function () {
            if (brandInputWrap.style.display === 'none') {
                switchToCustom();
            } else {
                switchToSelect();
            }
        });
    }

    if (btnBackToSelect) {
        btnBackToSelect.addEventListener('click', function () {
            switchToSelect();
        });
    }

    // Tự động nhận diện và chọn thương hiệu khi người dùng nhập Tên sản phẩm
    const nameInput = document.getElementById('name');
    if (nameInput && brandSelect) {
        let userHasManuallySelected = false;

        brandSelect.addEventListener('change', function () {
            if (this.value && this.value !== '__other__') {
                userHasManuallySelected = true;
            }
        });

        nameInput.addEventListener('input', function () {
            if (userHasManuallySelected) return;
            if (brandSelectWrap && brandSelectWrap.style.display === 'none') return;

            const text = this.value.toLowerCase().trim();
            if (!text) return;

            // Tìm thương hiệu khớp trong tên sản phẩm (ưu tiên tên thương hiệu dài trước)
            let matchedOption = null;
            let maxLen = 0;

            for (let i = 0; i < brandSelect.options.length; i++) {
                const opt = brandSelect.options[i];
                if (!opt.value || opt.value === '__other__') continue;
                const bVal = opt.value.toLowerCase();
                if (text.includes(bVal) && bVal.length > maxLen) {
                    matchedOption = opt.value;
                    maxLen = bVal.length;
                }
            }

            if (matchedOption) {
                brandSelect.value = matchedOption;
            }
        });
    }
});
</script>
@endsection
