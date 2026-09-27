<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Livestream;
use App\Models\Perfume;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $perfumes = Perfume::query()
            ->where('is_active', true)
            ->when($request->filled('search'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('search'));
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('brand', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('gender'), fn ($query) => $query->where('gender', $request->input('gender')))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('min_price'), fn ($query) => $query->whereRaw('COALESCE(sale_price, price) >= ?', [max(0, (int) $request->input('min_price'))]))
            ->when($request->filled('max_price'), fn ($query) => $query->whereRaw('COALESCE(sale_price, price) <= ?', [max(0, (int) $request->input('max_price'))]))
            ->when($request->filled('concentration'), fn ($query) => $query->where('concentration', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $request->input('concentration')).'%'))
            ->when($request->input('sort') === 'sale', fn ($query) => $query->whereNotNull('sale_price'))
            ->when($request->input('sort') === 'price_asc', fn ($query) => $query->orderByRaw('COALESCE(sale_price, price) asc'))
            ->when($request->input('sort') === 'price_desc', fn ($query) => $query->orderByRaw('COALESCE(sale_price, price) desc'))
            ->latest()
            ->get();

        if ($request->filled('note')) {
            $needle = mb_strtolower(trim((string) $request->input('note')));
            $perfumes = $perfumes->filter(fn (Perfume $perfume) => str_contains(mb_strtolower((string) $perfume->description), $needle));
        }
        if ($request->filled('style')) {
            $needle = mb_strtolower(trim((string) $request->input('style')));
            $perfumes = $perfumes->filter(fn (Perfume $perfume) => str_contains(mb_strtolower((string) $perfume->description), $needle));
        }
        if ($request->filled('longevity')) {
            $range = (string) $request->input('longevity');
            $perfumes = $perfumes->filter(function (Perfume $perfume) use ($range) {
                $estimate = (int) $perfume->scent_profile['longevity']['percent'];
                return match ($range) {
                    'light' => $estimate < 80,
                    'medium' => $estimate >= 80 && $estimate < 90,
                    'strong' => $estimate >= 90,
                    default => true,
                };
            });
        }
        $hasFilters = $request->hasAny(['search', 'gender', 'category', 'sort', 'min_price', 'max_price', 'concentration', 'note', 'style', 'longevity']);
        $perfumes = $hasFilters ? $perfumes->values() : $perfumes->take(12);

        $categories = Category::query()
            ->withCount(['perfumes' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('id')
            ->take(6)
            ->get();

        $genderCounts = Perfume::query()
            ->where('is_active', true)
            ->selectRaw('gender, count(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $totalPerfumes = $genderCounts->sum();

        $livestream = Livestream::query()->where('status', 'live')->latest('id')->first();
        $onAir = $livestream && ($livestream->source === 'youtube' || $livestream->isBrowserOnAir());

        return view('home', compact('perfumes', 'categories', 'genderCounts', 'totalPerfumes', 'livestream', 'onAir'));
    }
}
