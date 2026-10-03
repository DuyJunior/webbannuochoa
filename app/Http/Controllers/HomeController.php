<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Livestream;
use App\Models\Perfume;
use App\Models\Video;
use App\Services\DiscoveryBoxService;
use App\Services\MoodCollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'in:nam,nu,unisex'],
            'category' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'in:sale,price_asc,price_desc'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0', ...($request->filled('min_price') ? ['gte:min_price'] : [])],
            'concentration' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:100'],
            'style' => ['nullable', 'string', 'max:100'],
            'longevity' => ['nullable', 'in:light,medium,strong'],
        ], ['max_price.gte' => __('Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.')]);

        $perfumes = Perfume::query()
            ->with('variants')
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
            ->when($request->input('sort') === 'sale', fn ($query) => $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price'))
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
        $hasFilters = $request->anyFilled(['search', 'gender', 'category', 'sort', 'min_price', 'max_price', 'concentration', 'note', 'style', 'longevity']);
        $moodCollections = $hasFilters ? [] : MoodCollectionService::from($perfumes);
        $homeSampleAvailability = $hasFilters ? collect() : $perfumes->mapWithKeys(fn (Perfume $item) => [
            $item->id => DiscoveryBoxService::remaining($item, $request->session()->get('cart', [])),
        ]);
        $sampleCandidates = $hasFilters ? collect() : $perfumes
            ->sortByDesc(fn (Perfume $item) => $homeSampleAvailability[$item->id] > 0)->take(8)->values();
        $perfumes = $hasFilters ? $perfumes->values() : $perfumes->take(12);
        $featuredOffer = $hasFilters ? null : Perfume::where('is_active', true)->whereNotNull('sale_price')
            ->where('price', '>', 0)->whereColumn('sale_price', '<', 'price')->latest()->first();

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

        $galleryFeatured = $hasFilters ? null : ($perfumes->firstWhere('slug', config('scent-gallery.featured')) ?? $perfumes->first());
        $gallerySelection = collect();
        if ($galleryFeatured) {
            $candidates = $perfumes->reject(fn (Perfume $item) => $item->id === $galleryFeatured->id);
            $priority = array_flip(config('scent-gallery.selection', []));
            $gallerySelection = $candidates->sortBy(fn (Perfume $item) => $priority[$item->slug] ?? PHP_INT_MAX)->take(4)->values();
        }
        $galleryIds = $gallerySelection->pluck('id')->when($galleryFeatured, fn ($ids) => $ids->push($galleryFeatured->id));
        $galleryRemaining = $perfumes->reject(fn (Perfume $item) => $galleryIds->contains($item->id))->values();
        $wishlistIds = $request->user()
            ? DB::table('wishlists')->where('user_id', $request->user()->id)->pluck('perfume_id')->all()
            : [];

        $journalArticles = $hasFilters ? collect() : Article::where('is_published', true)
            ->whereIn('slug', ['xit-nuoc-hoa-o-dau', 'hieu-ba-tang-huong', 'bao-quan-nuoc-hoa'])
            ->get()->keyBy('slug');
        $homeVideos = $hasFilters ? collect() : Video::with(['perfume' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)->whereIn('placement', ['home', 'all'])
            ->whereNotNull('video_url')->where('video_url', '!=', '')
            ->orderBy('sort_order')->orderBy('id')->take(4)->get();

        return view('home', compact('perfumes', 'categories', 'genderCounts', 'totalPerfumes', 'livestream', 'onAir', 'featuredOffer',
            'galleryFeatured', 'gallerySelection', 'galleryRemaining', 'wishlistIds', 'journalArticles', 'homeVideos', 'moodCollections', 'sampleCandidates', 'homeSampleAvailability'));
    }
}
