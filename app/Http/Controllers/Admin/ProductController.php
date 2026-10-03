<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Rules\SafeVideoUrl;
use App\Services\ProductPhotoService;
use App\Services\StockAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'gender' => ['nullable', 'in:nam,nu,unisex'], 'status' => ['nullable', 'in:active,inactive']]);
        $products = Product::query()
            ->with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('search'));
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('brand', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->input('gender')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->input('status') === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'low_stock' => Product::where('stock', '<=', 5)->count(),
        ];

        return view('admin.products.index', compact('products', 'stats'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $brands = $this->getAvailableBrands();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $this->saveWithPhotos($request, new Product, $data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã thêm sản phẩm mới thành công.');
    }

    public function show(Product $product): View
    {
        $product->load('category');

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product->load('shopPhotos');
        $categories = Category::orderBy('name')->get();
        $brands = $this->getAvailableBrands();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request);
        if ((int) $data['volume_ml'] !== (int) $product->volume_ml && OrderItem::where('perfume_id', $product->id)->exists()) {
            throw ValidationException::withMessages(['volume_ml' => 'Sản phẩm đã có đơn hàng. Hãy tạo sản phẩm mới nếu thay đổi dung tích gốc để giữ đúng lịch sử kho.']);
        }
        if ($product->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $data['is_active'] = $request->boolean('is_active');
        $previousStock = $product->stock;
        $this->saveWithPhotos($request, $product, $data);
        StockAlertService::notifyIfRestocked($product, $previousStock);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Đã cập nhật sản phẩm thành công.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã xóa sản phẩm khỏi danh sách.');
    }

    // Hiển thị chi tiết sản phẩm cho người dùng thường
    public function show_normal(Product $product): View
    {
        $product->load('category');

        return view('products.show', compact('product'));
    }

    private function validatedData(Request $request): array
    {
        $brand = $request->input('brand');
        $name = $request->input('name');
        if (($brand === null || (is_string($brand) && in_array(trim($brand), ['', '__other__'], true))) && is_string($name)) {
            // Tự động trích xuất thương hiệu từ tên sản phẩm nếu người dùng chưa chọn
            $detectedBrand = $this->detectBrandFromName($name);
            $request->merge(['brand' => $detectedBrand]);
        }

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:120'],
            'gender' => ['required', Rule::in(['nam', 'nu', 'unisex'])],
            'concentration' => ['nullable', 'string', 'max:50'],
            'volume_ml' => ['required', 'integer', 'min:1', 'max:5000'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:50000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999999'],
            'stock_5ml' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'stock_10ml' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'stock_50ml' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'image_url' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/?images\/)/i'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'shop_photos' => ['sometimes', 'array', 'max:6', function ($attribute, $files, $fail) {
                if (is_array($files) && array_sum(array_map(
                    fn ($file) => $file instanceof UploadedFile ? $file->getSize() : 0, $files
                )) > 18 * 1024 * 1024) {
                    $fail('Tổng dung lượng ảnh thực tế trong một lần tải không được vượt quá 18 MB. Hãy tải thành nhiều lần.');
                }
            }],
            'shop_photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000'],
            'remove_shop_photos' => ['sometimes', 'array', 'max:6'],
            'remove_shop_photos.*' => ['required', 'integer', 'distinct', 'min:1'],
            'video_url' => ['bail', 'nullable', 'string', 'max:2048', new SafeVideoUrl],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'brand.required' => 'Vui lòng chọn hoặc nhập thương hiệu cho sản phẩm.',
            'gender.required' => 'Vui lòng chọn giới tính.',
            'volume_ml.required' => 'Vui lòng nhập dung tích chai nước hoa (ml).',
            'volume_ml.integer' => 'Dung tích phải là một số nguyên hợp lệ.',
            'volume_ml.min' => 'Dung tích tối thiểu phải từ 1 ml trở lên.',
            'weight.integer' => 'Khối lượng phải là số nguyên (gram).',
            'weight.min' => 'Khối lượng tối thiểu phải từ 1 gram trở lên.',
            'price.required' => 'Vui lòng nhập giá bán sản phẩm.',
            'price.numeric' => 'Giá sản phẩm phải là định dạng số.',
            'price.min' => 'Giá sản phẩm không được là số âm.',
            'stock.required' => 'Vui lòng nhập số lượng hàng trong kho.',
            'stock.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'stock.min' => 'Số lượng tồn kho không được là số âm.',
            'sale_price.lte' => 'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá niêm yết.',
            'image_url.regex' => 'Ảnh phải là URL http/https hoặc đường dẫn trong thư mục images.',
            'image_file.image' => 'Tệp tải lên phải là hình ảnh.',
            'image_file.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc WEBP.',
            'image_file.max' => 'Ảnh không được vượt quá dung lượng 5MB.',
            'shop_photos.array' => 'Vui lòng chọn các tệp ảnh thực tế hợp lệ.',
            'shop_photos.max' => 'Mỗi sản phẩm có tối đa 6 ảnh thực tế.',
            'shop_photos.*.image' => 'Ảnh thực tế phải là tệp hình ảnh.',
            'shop_photos.*.mimes' => 'Ảnh thực tế phải có định dạng JPG, PNG hoặc WEBP.',
            'shop_photos.*.extensions' => 'Ảnh thực tế phải có phần mở rộng JPG, PNG hoặc WEBP.',
            'shop_photos.*.max' => 'Mỗi ảnh thực tế không được vượt quá 5 MB.',
            'shop_photos.*.dimensions' => 'Chiều rộng và chiều cao ảnh không được vượt quá 8.000 pixel.',
        ]);

        $validated['stock_5ml'] = (int) ($validated['stock_5ml'] ?? 0);
        $validated['weight'] = (int) ($validated['weight'] ?? 200) ?: 200;

        $s = (int) ($validated['stock'] ?? 0);
        if (! isset($validated['stock_10ml']) || $validated['stock_10ml'] === null) {
            $validated['stock_10ml'] = 0;
        }
        if (! isset($validated['stock_50ml']) || $validated['stock_50ml'] === null) {
            $validated['stock_50ml'] = 0;
        }

        unset($validated['shop_photos'], $validated['remove_shop_photos']);

        return $validated;
    }

    private function saveWithPhotos(Request $request, Product $product, array $data): void
    {
        $uploadedCatalogPath = null;

        try {
            $data = $this->storeUploadedImage($request, $data);
            if ($request->hasFile('image_file')) {
                $uploadedCatalogPath = $data['image_url'];
            }

            app(ProductPhotoService::class)->save(
                $product, $data, $request->file('shop_photos', []), $request->input('remove_shop_photos', [])
            );
        } catch (Throwable $exception) {
            if ($uploadedCatalogPath !== null) {
                File::delete(public_path($uploadedCatalogPath));
            }
            throw $exception;
        }
    }

    private function storeUploadedImage(Request $request, array $data): array
    {
        unset($data['image_file']);

        if (! $request->hasFile('image_file')) {
            return $data;
        }

        $file = $request->file('image_file');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('images/products'), $filename);
        $data['image_url'] = 'images/products/'.$filename;

        return $data;
    }

    private function uniqueSlug(string $name, ?int $exceptId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'nuoc-hoa';
        $slug = $baseSlug;
        $suffix = 2;

        while (Product::withTrashed()->where('slug', $slug)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function getAvailableBrands(): array
    {
        $defaultBrands = [
            'Chanel',
            'Dior',
            'Gucci',
            'Tom Ford',
            'Versace',
            'Yves Saint Laurent',
            'Giorgio Armani',
            'Creed',
            'Guerlain',
            'Parfums de Marly',
            'Narciso Rodriguez',
            'Burberry',
            'Bvlgari',
            'Calvin Klein',
            'Hermes',
            'Jo Malone',
            'Kilian',
            'Le Labo',
            'Maison Francis Kurkdjian',
            'Maison Margiela',
            'Prada',
            'Valentino',
            'Dolce & Gabbana',
            'Jean Paul Gaultier',
            'Acqua di Parma',
        ];

        $existingBrands = Product::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand')
            ->toArray();

        return collect(array_merge($defaultBrands, $existingBrands))
            ->map(fn ($b) => trim($b))
            ->filter()
            ->unique(fn ($b) => mb_strtolower($b))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function detectBrandFromName(string $name): ?string
    {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            return null;
        }

        $brands = $this->getAvailableBrands();
        // Sắp xếp thương hiệu có độ dài dài hơn lên trước (ví dụ 'Yves Saint Laurent' trước 'Laurent')
        usort($brands, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($brands as $b) {
            if (mb_stripos($trimmedName, $b) !== false) {
                return $b;
            }
        }

        // Nếu tên sản phẩm bắt đầu bằng một từ (ví dụ "Roja Elysium" -> "Roja")
        $words = preg_split('/\s+/', $trimmedName);
        if (! empty($words[0]) && mb_strlen($words[0]) >= 2) {
            // Nếu là từ đầu tiên hợp lệ, có thể cân nhắc hoặc giữ nguyên
        }

        return null;
    }
}
