<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        if (request()->user()?->role !== 'admin') {
            $categories = Category::query()
                ->whereHas('perfumes', fn ($query) => $query->where('is_active', true))
                ->withCount(['perfumes' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get();

            return view('store.categories', compact('categories'));
        }

        if (request()->routeIs('admin.*')) {
            request()->validate(['search' => ['nullable', 'string', 'max:100']]);
            $categories = Category::withCount('perfumes')
                ->when(request()->filled('search'), fn ($query) => $query->where('name', 'like', '%'.trim((string) request('search')).'%'))
                ->latest()->orderByDesc('id')->paginate(15)->withQueryString();
            $stats = [
                'total' => Category::count(),
                'populated' => Category::has('perfumes')->count(),
                'empty' => Category::doesntHave('perfumes')->count(),
            ];

            return view('admin.categories.index', compact('categories', 'stats'));
        }

        $categories = Category::withCount('perfumes')->latest()->get();

        return view(request()->routeIs('admin.*') ? 'admin.categories.index' : 'categories.index', compact('categories'));
    }

    public function create()
    {
        return view(request()->routeIs('admin.*') ? 'admin.categories.create' : 'categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);

        Category::create($validated);

        $targetRoute = $request->routeIs('admin.*') ? 'admin.categories.index' : 'categories.index';

        return redirect()->route($targetRoute)
            ->with('success', __('Đã thêm danh mục mới thành công.'));
    }

    public function show(Category $category)
    {
        if (request()->user()?->role !== 'admin') {
            return redirect()->to(route('home', ['category' => $category->id]).'#san-pham');
        }

        if (request()->routeIs('admin.*')) {
            $products = $category->perfumes()->latest()->orderByDesc('id')->paginate(15);

            return view('admin.categories.show', compact('category', 'products'));
        }

        $category->load(['perfumes' => fn ($query) => $query->latest()]);

        return view(request()->routeIs('admin.*') ? 'admin.categories.show' : 'categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        return view(request()->routeIs('admin.*') ? 'admin.categories.edit' : 'categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);

        $category->update($validated);

        $targetRoute = $request->routeIs('admin.*') ? 'admin.categories.index' : 'categories.index';

        return redirect()->route($targetRoute)
            ->with('success', __('Đã cập nhật danh mục thành công.'));
    }

    public function destroy(Category $category)
    {
        $category->delete();

        $targetRoute = request()->routeIs('admin.*') ? 'admin.categories.index' : 'categories.index';

        return redirect()->route($targetRoute)
            ->with('success', __('Đã xóa danh mục thành công.'));
    }
}
