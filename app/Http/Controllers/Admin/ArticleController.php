<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles.index', ['articles' => Article::latest()->paginate(20)]);
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['is_published'] = $request->boolean('is_published');
        Article::create($data);
        return redirect()->route('admin.articles.index')->with('success', 'Đã tạo bài viết.');
    }

    public function edit(Article $article)
    {
        return view('admin.articles.form', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        $data = $this->validated($request);
        if ($article->title !== $data['title']) $data['slug'] = $this->uniqueSlug($data['title'], $article->id);
        $data['is_published'] = $request->boolean('is_published');
        $article->update($data);
        return redirect()->route('admin.articles.index')->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Article $article)
    {
        $article->delete();
        return redirect()->route('admin.articles.index')->with('success', 'Đã xóa bài viết.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'excerpt' => 'required|string|max:500',
            'body' => 'required|string|min:50',
            'image_url' => ['nullable', 'string', 'max:255', 'regex:/^images\/[a-zA-Z0-9\/._-]+$/'],
            'is_published' => 'nullable|boolean',
        ]);
    }

    private function uniqueSlug(string $title, ?int $except = null): string
    {
        $base = Str::slug($title) ?: 'bai-viet';
        $slug = $base;
        $i = 2;
        while (Article::where('slug', $slug)->when($except, fn ($query) => $query->whereKeyNot($except))->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
}
