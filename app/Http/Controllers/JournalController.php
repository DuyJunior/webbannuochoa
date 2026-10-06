<?php

namespace App\Http\Controllers;

use App\Models\Article;

class JournalController extends Controller
{
    public function index()
    {
        $articles = Article::where('is_published', true)->latest()->orderByDesc('id')->paginate(9)
            ->fragment('thu-vien-cau-chuyen');
        $readingPaths = Article::where('is_published', true)
            ->whereIn('slug', ['hieu-ba-tang-huong', 'xit-nuoc-hoa-o-dau', 'bao-quan-nuoc-hoa'])
            ->get()->keyBy('slug');

        return view('store.journal', compact('articles', 'readingPaths'));
    }

    public function show(Article $article)
    {
        abort_unless($article->is_published, 404);
        $relatedArticles = Article::where('is_published', true)->whereKeyNot($article->id)
            ->latest()->orderByDesc('id')->limit(3)->get();

        return view('store.article', compact('article', 'relatedArticles'));
    }
}
