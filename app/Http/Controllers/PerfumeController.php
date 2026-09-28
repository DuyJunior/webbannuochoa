<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Perfume;
use App\Models\ScentWardrobe;
use App\Services\StockAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PerfumeController extends Controller
{
    public function index(Request $request): View
    {
        $perfumes = Perfume::query()
            ->with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('search'));
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('brand', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('gender'), fn ($query) => $query->where('gender', $request->input('gender')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Perfume::count(),
            'active' => Perfume::where('is_active', true)->count(),
            'low_stock' => Perfume::where('stock', '<=', 5)->count(),
        ];

        return view('perfumes.index', compact('perfumes', 'stats'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('perfumes.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data = $this->storeUploadedImage($request, $data);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        Perfume::create($data);

        return redirect()->route('perfumes.index')
            ->with('success', 'Đã thêm nước hoa mới thành công.');
    }

    public function show(Perfume $perfume): View
    {
        abort_unless($perfume->is_active || auth()->user()?->role === 'admin', 404);
        $perfume->load('category');
        $reviews = $perfume->reviews()->with('user:id,name')->latest()->paginate(5);
        $averageRating = round((float) $perfume->reviews()->avg('rating'), 1);
        $isFavorite = auth()->check() && DB::table('wishlists')
            ->where('user_id', auth()->id())->where('perfume_id', $perfume->id)->exists();

        // Related products: same category first, then same gender, exclude self
        $related = Perfume::where('is_active', true)
            ->where('id', '!=', $perfume->id)
            ->when($perfume->category_id, function ($q) use ($perfume) {
                $q->where('category_id', $perfume->category_id);
            }, function ($q) use ($perfume) {
                $q->where('gender', $perfume->gender);
            })
            ->inRandomOrder()
            ->take(4)
            ->get();

        // If fewer than 4 from same category, fill with same gender
        if ($related->count() < 4 && $perfume->category_id) {
            $existingIds = $related->pluck('id')->push($perfume->id);
            $extra = Perfume::where('is_active', true)
                ->whereNotIn('id', $existingIds)
                ->where('gender', $perfume->gender)
                ->inRandomOrder()
                ->take(4 - $related->count())
                ->get();
            $related = $related->concat($extra);
        }

        // Lưu lịch sử xem gần đây
        $recentIds = session()->get('recently_viewed', []);
        $recentIds = array_diff($recentIds, [$perfume->id]);
        array_unshift($recentIds, $perfume->id);
        $recentIds = array_slice($recentIds, 0, 8);
        session()->put('recently_viewed', $recentIds);

        // Lấy danh sách sản phẩm vừa xem (ngoại trừ chai hiện tại)
        $recentlyViewed = Perfume::whereIn('id', array_slice($recentIds, 1, 4))->where('is_active', true)->get();

        $inWardrobe = auth()->check() && ScentWardrobe::where('user_id', auth()->id())
            ->where('perfume_id', $perfume->id)->exists();

        return view('perfumes.show', compact('perfume', 'reviews', 'averageRating', 'isFavorite', 'related', 'recentlyViewed', 'inWardrobe'));
    }

    public function edit(Perfume $perfume): View
    {
        $categories = Category::orderBy('name')->get();

        return view('perfumes.edit', compact('perfume', 'categories'));
    }

    public function update(Request $request, Perfume $perfume): RedirectResponse
    {
        $data = $this->validatedData($request);
        if ((int) $data['volume_ml'] !== (int) $perfume->volume_ml && OrderItem::where('perfume_id', $perfume->id)->exists()) {
            throw ValidationException::withMessages(['volume_ml' => 'Sản phẩm đã có đơn hàng. Hãy tạo sản phẩm mới nếu thay đổi dung tích gốc để giữ đúng lịch sử kho.']);
        }
        $data = $this->storeUploadedImage($request, $data);

        if ($perfume->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $perfume->id);
        }

        $data['is_active'] = $request->boolean('is_active');
        $previousStock = $perfume->stock;
        $perfume->update($data);
        StockAlertService::notifyIfRestocked($perfume, $previousStock);

        return redirect()->route('perfumes.show', $perfume)
            ->with('success', 'Đã cập nhật nước hoa thành công.');
    }

    public function destroy(Perfume $perfume): RedirectResponse
    {
        $perfume->delete();

        return redirect()->route('perfumes.index')
            ->with('success', 'Đã xóa nước hoa khỏi danh sách.');
    }

    private function validatedData(Request $request): array
    {
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
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập tên nước hoa.',
            'brand.required' => 'Vui lòng chọn hoặc nhập thương hiệu cho nước hoa.',
            'gender.required' => 'Vui lòng chọn giới tính.',
            'volume_ml.required' => 'Vui lòng nhập dung tích chai nước hoa (ml).',
            'volume_ml.integer' => 'Dung tích phải là một số nguyên hợp lệ.',
            'volume_ml.min' => 'Dung tích tối thiểu phải từ 1 ml trở lên.',
            'weight.integer' => 'Khối lượng phải là số nguyên (gram).',
            'weight.min' => 'Khối lượng tối thiểu phải từ 1 gram trở lên.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.numeric' => 'Giá bán phải là định dạng số.',
            'price.min' => 'Giá bán không được là số âm.',
            'stock.required' => 'Vui lòng nhập số lượng hàng trong kho.',
            'stock.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'stock.min' => 'Số lượng tồn kho không được là số âm.',
            'sale_price.lte' => 'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá niêm yết.',
            'image_url.regex' => 'Ảnh phải là URL http/https hoặc đường dẫn trong thư mục images.',
            'image_file.image' => 'Tệp tải lên phải là hình ảnh.',
            'image_file.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc WEBP.',
            'image_file.max' => 'Ảnh không được lớn hơn 5MB.',
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

        return $validated;
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

        while (Perfume::withTrashed()->where('slug', $slug)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
