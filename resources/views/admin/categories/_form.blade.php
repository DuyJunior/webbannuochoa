@php($editing = isset($category))
<div class="studio-list-heading mb-4"><div><span class="studio-form-kicker">{{ __('DANH MỤC /') }} {{ $editing ? __('CHỈNH SỬA') : __('TẠO MỚI') }}</span><h2>{{ $editing ? $category->name : __('Thêm danh mục') }}</h2><p class="text-muted mb-0">{{ __('Đặt tên rõ ràng để phân loại bộ sưu tập.') }}</p></div><a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> {{ __('Danh sách danh mục') }}</a></div>
<form action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="studio-form-layout">
        <section class="admin-card studio-form-section"><h3>{{ __('Thông tin danh mục') }}</h3><div class="form-group mb-0"><label for="category-name">{{ __('Tên danh mục') }} <span class="text-danger">*</span></label><input id="category-name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $editing ? $category->name : '') }}" maxlength="255" placeholder="{{ __('Ví dụ: Nước hoa Niche') }}" aria-describedby="category-name-help" required><small id="category-name-help" class="studio-field-help">{{ __('Tên được hiển thị trong bộ lọc và thông tin sản phẩm.') }}</small>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
@include('partials.english-content-fields', ['englishRecord' => $category ?? new \App\Models\Category, 'englishFields' => ['name_en' => ['label' => 'Tên tiếng Anh']]])
</section>
        <aside class="studio-form-aside"><section class="admin-card studio-form-section"><span class="studio-form-kicker">{{ __('GỢI Ý SẮP XẾP') }}</span><h3>{{ __('Phân loại nhất quán') }}</h3><p class="studio-field-help">{{ __('Ưu tiên tên ngắn, dễ hiểu và cùng một cách phân nhóm cho toàn bộ cửa hàng.') }}</p><p class="studio-field-help mb-0">{{ $editing ? __('Đổi tên sẽ cập nhật cách hiển thị của các sản phẩm đã thuộc danh mục này.') : __('Sau khi tạo, chọn danh mục này khi thêm hoặc chỉnh sửa sản phẩm.') }}</p></section></aside>
    </div>
    <div class="studio-form-actions"><span class="studio-field-help">{{ __('Tên danh mục là trường bắt buộc.') }}</span><div><a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary mr-2">{{ __('Hủy thay đổi') }}</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-check mr-1" aria-hidden="true"></i> {{ $editing ? __('Lưu thay đổi') : __('Thêm danh mục') }}</button></div></div>
</form>
