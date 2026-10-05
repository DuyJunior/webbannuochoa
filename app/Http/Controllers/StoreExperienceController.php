<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\PerfumeReview;
use App\Models\ScentWardrobe;
use App\Models\User;
use App\Services\DiscoveryBoxService;
use App\Services\FragranceEditorialService;
use App\Services\LoyaltyService;
use App\Services\ScentFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreExperienceController extends Controller
{
    public function giftFinder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'occasion' => 'nullable|in:hang-ngay,hen-ho,tiec',
            'gender' => 'nullable|in:nam,nu,unisex',
            'max_price' => 'nullable|integer|min:0|max:1000000000',
            'gift_message' => 'nullable|string|max:120',
        ]);
        $request->session()->put('gift_finder_message', trim($data['gift_message'] ?? ''));
        unset($data['gift_message']);

        return redirect()->route('store.finder', ['gift' => 1, ...array_filter($data, fn ($value) => $value !== null && $value !== '')]);
    }

    public function finder(Request $request): View
    {
        $data = $request->validate([
            'style' => 'nullable|in:hoa,go,vanilla,tuoi,am',
            'occasion' => 'nullable|in:hang-ngay,hen-ho,tiec',
            'gender' => 'nullable|in:nam,nu,unisex',
            'max_price' => 'nullable|integer|min:0|max:1000000000',
            'gift' => 'nullable|boolean',
        ]);
        $giftMode = $request->boolean('gift');
        $recommended = count($data) ? ScentFinder::recommendations(
            Perfume::where('is_active', true)
                ->when($giftMode, fn ($query) => $query->where('stock', '>', 0))
                ->when($giftMode && $request->filled('gender'), fn ($query) => $query->whereIn('gender', array_unique([$data['gender'], 'unisex'])))
                ->when($request->filled('max_price'), fn ($q) => $q->whereRaw('COALESCE(sale_price, price) <= ?', [(int) $data['max_price']]))->get(),
            $data['style'] ?? null, $data['occasion'] ?? null, $data['gender'] ?? null
        ) : collect();

        return view('store.finder', compact('recommended', 'giftMode'));
    }

    /**
     * Tính năng 1: Quiz chọn nước hoa tương tác (Tính cách, thời tiết, dịp dùng, nốt hương)
     */
    public function quiz(Request $request): View
    {
        $data = $request->validate([
            'step' => 'nullable|integer|between:1,4',
            'personality' => 'nullable|string|in:charming,elegant,fresh,warm',
            'weather' => 'nullable|string|in:cool,hot,ac,night',
            'occasion' => 'nullable|string|in:work,date,party,casual',
            'note' => 'nullable|string|in:floral,woody,citrus,sweet',
            'gender' => 'nullable|string|in:nam,nu,unisex',
        ]);
        $step = (int) ($data['step'] ?? 1);
        $personality = $data['personality'] ?? null;
        $weather = $data['weather'] ?? null;
        $occasion = $data['occasion'] ?? null;
        $note = $data['note'] ?? null;
        $gender = $data['gender'] ?? null;

        $hasResult = collect([$personality, $weather, $occasion, $note])->every(fn ($answer) => filled($answer));
        $recommendations = collect();

        if ($hasResult) {
            $perfumes = Perfume::where('is_active', true)->with('category')
                ->when($gender, fn ($query) => $query->whereIn('gender', array_unique([$gender, 'unisex'])))
                ->get();
            $recommendations = $perfumes->map(function (Perfume $p) use ($personality, $weather, $occasion, $note, $gender) {
                $text = mb_strtolower($p->name.' '.$p->brand.' '.$p->description.' '.($p->category->name ?? ''));
                $score = 0; // Ranking only, not a probability of compatibility.

                // Gender match
                if ($gender && ($p->gender === $gender || $p->gender === 'unisex')) {
                    $score += 10;
                }

                // Personality match
                if ($personality === 'charming' && (str_contains($text, 'hoa') || str_contains($text, 'ngọt') || str_contains($text, 'quyến rũ') || str_contains($text, 'vanilla'))) {
                    $score += 10;
                } elseif ($personality === 'elegant' && (str_contains($text, 'gỗ') || str_contains($text, 'thanh lịch') || str_contains($text, 'tinh tế') || str_contains($text, 'xạ hương'))) {
                    $score += 10;
                } elseif ($personality === 'fresh' && (str_contains($text, 'tươi') || str_contains($text, 'cam') || str_contains($text, 'năng động') || str_contains($text, 'chanh'))) {
                    $score += 10;
                } elseif ($personality === 'warm' && (str_contains($text, 'ấm') || str_contains($text, 'hổ phách') || str_contains($text, 'trầm') || str_contains($text, 'tuyết tùng'))) {
                    $score += 10;
                }

                // Weather match
                if ($weather === 'cool' && (str_contains($text, 'ấm') || str_contains($text, 'gỗ') || str_contains($text, 'ngọt') || str_contains($text, 'vanilla'))) {
                    $score += 8;
                } elseif ($weather === 'hot' && (str_contains($text, 'tươi') || str_contains($text, 'cam') || str_contains($text, 'biển') || str_contains($text, 'thảo mộc'))) {
                    $score += 8;
                } elseif ($weather === 'ac' && (str_contains($text, 'hoa') || str_contains($text, 'xạ hương') || str_contains($text, 'gỗ'))) {
                    $score += 8;
                }

                // Occasion match
                if ($occasion === 'work' && (str_contains($text, 'thanh') || str_contains($text, 'tươi') || str_contains($text, 'nhẹ'))) {
                    $score += 8;
                } elseif ($occasion === 'date' && (str_contains($text, 'ngọt') || str_contains($text, 'quyến rũ') || str_contains($text, 'hoa hồng') || str_contains($text, 'vanilla'))) {
                    $score += 8;
                } elseif ($occasion === 'party' && (str_contains($text, 'hổ phách') || str_contains($text, 'tỏa') || str_contains($text, 'nồng nàn') || str_contains($text, 'sang trọng'))) {
                    $score += 8;
                }

                // Note preference
                if ($note === 'floral' && (str_contains($text, 'hoa') || str_contains($text, 'rose') || str_contains($text, 'jasmine'))) {
                    $score += 10;
                } elseif ($note === 'woody' && (str_contains($text, 'gỗ') || str_contains($text, 'cedar') || str_contains($text, 'sandalwood'))) {
                    $score += 10;
                } elseif ($note === 'citrus' && (str_contains($text, 'cam') || str_contains($text, 'bergamot') || str_contains($text, 'chanh'))) {
                    $score += 10;
                } elseif ($note === 'sweet' && (str_contains($text, 'ngọt') || str_contains($text, 'vanilla') || str_contains($text, 'caramel'))) {
                    $score += 10;
                }

                $p->match_score = $score;

                return $p;
            })->sortByDesc('match_score')->take(4)->values();
        }

        return view('store.quiz', compact('hasResult', 'recommendations', 'step', 'personality', 'weather', 'occasion', 'note', 'gender'));
    }

    /**
     * Tính năng 2: Hộp thử mùi (Discovery Box) cho khách tự chọn 3-5 mẫu chiết
     */
    public function discoveryBox(Request $request): View
    {
        $data = $request->validate(['samples' => 'nullable|array|max:5', 'samples.*' => 'integer|distinct', 'size' => 'nullable|in:3,5']);
        $perfumes = Perfume::where('is_active', true)->with('category')->orderBy('brand')->get();
        $cart = $request->session()->get('cart', []);
        $sampleAvailability = $perfumes->mapWithKeys(fn ($perfume) => [$perfume->id => DiscoveryBoxService::remaining($perfume, $cart)]);
        $initialSize = (int) $request->old('size', $data['size'] ?? 3);
        $initialSize = in_array($initialSize, [3, 5], true) ? $initialSize : 3;
        $requestedIds = collect($request->old('perfume_ids', $data['samples'] ?? []))
            ->map(fn ($id) => (int) $id)->unique()->take($initialSize);
        $catalog = $perfumes->keyBy('id');
        $initialSamples = $requestedIds->map(fn ($id) => $catalog->get($id))
            ->filter(fn ($perfume) => $perfume && $sampleAvailability[$perfume->id] > 0)->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'brand' => $p->brand, 'img' => $p->image_src,
            ])->values();
        $unavailableSelectionCount = $requestedIds->count() - $initialSamples->count();

        return view('store.discovery-box', compact('perfumes', 'initialSize', 'initialSamples', 'sampleAvailability', 'unavailableSelectionCount'));
    }

    /**
     * Tính năng 4: Tủ nước hoa cá nhân (Scent Wardrobe)
     */
    public function wardrobe(Request $request): View
    {
        $user = $request->user();
        $wardrobeItems = ScentWardrobe::where('user_id', $user->id)
            ->whereHas('perfume', fn ($query) => $query->where('is_active', true))
            ->with('perfume')
            ->latest()
            ->get();

        $byOccasion = [
            'all' => $wardrobeItems,
            'work' => $wardrobeItems->where('occasion', 'work'),
            'date' => $wardrobeItems->where('occasion', 'date'),
            'party' => $wardrobeItems->where('occasion', 'party'),
            'casual' => $wardrobeItems->where('occasion', 'casual'),
        ];

        return view('store.wardrobe', compact('wardrobeItems', 'byOccasion'));
    }

    public function addToWardrobe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'perfume_id' => 'required|exists:perfumes,id',
            'occasion' => 'required|in:work,date,party,casual',
            'notes' => 'nullable|string|max:500',
        ]);

        ScentWardrobe::updateOrInsert(
            [
                'user_id' => $request->user()->id,
                'perfume_id' => $validated['perfume_id'],
                'occasion' => $validated['occasion'],
            ],
            [
                'notes' => $validated['notes'] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', __('Đã lưu mùi hương vào Tủ nước hoa cá nhân!'));
    }

    public function removeFromWardrobe(Request $request, int $id): RedirectResponse
    {
        ScentWardrobe::where('user_id', $request->user()->id)->where('id', $id)->delete();

        return back()->with('success', __('Đã xóa mùi hương khỏi Tủ cá nhân.'));
    }

    public function shareWardrobe(User $user): View
    {
        $wardrobeItems = ScentWardrobe::where('user_id', $user->id)
            ->whereHas('perfume', fn ($query) => $query->where('is_active', true))
            ->with('perfume')
            ->latest()
            ->get();

        return view('store.wardrobe-share', compact('user', 'wardrobeItems'));
    }

    /**
     * Tính năng 5: So sánh nước hoa theo các chỉ số trực quan
     */
    public function compare(Request $request): View
    {
        $ids = array_values(array_unique(array_map('intval', array_filter(explode(',', (string) $request->query('ids', ''))))));
        $perfumes = Perfume::where('is_active', true)->whereIn('id', array_slice($ids, 0, 3))->get()
            ->sortBy(fn ($perfume) => array_search($perfume->id, $ids, true))->values();

        // Tính các chỉ số so sánh (độ ngọt, độ tươi, độ lưu hương, tỏa hương, giá/ml)
        foreach ($perfumes as $perfume) {
            $desc = mb_strtolower($perfume->description.' '.$perfume->name.' '.($perfume->category->name ?? ''));

            // Sweetness (1-10)
            $sweet = 5;
            if (str_contains($desc, 'vanilla') || str_contains($desc, 'caramel') || str_contains($desc, 'ngọt') || str_contains($desc, 'kẹo')) {
                $sweet += 4;
            }
            if (str_contains($desc, 'hoa hồng') || str_contains($desc, 'mật ong')) {
                $sweet += 2;
            }
            if (str_contains($desc, 'tươi') || str_contains($desc, 'chanh') || str_contains($desc, 'cam')) {
                $sweet -= 2;
            }
            $perfume->metric_sweetness = max(2, min(10, $sweet));

            // Freshness (1-10)
            $fresh = 5;
            if (str_contains($desc, 'tươi') || str_contains($desc, 'cam') || str_contains($desc, 'chanh') || str_contains($desc, 'bergamot') || str_contains($desc, 'biển')) {
                $fresh += 4;
            }
            if (str_contains($desc, 'xanh') || str_contains($desc, 'bạc hà')) {
                $fresh += 2;
            }
            if (str_contains($desc, 'gỗ') || str_contains($desc, 'trầm') || str_contains($desc, 'ấm')) {
                $fresh -= 2;
            }
            $perfume->metric_freshness = max(2, min(10, $fresh));

            // Longevity
            $profile = $perfume->scent_profile;
            $perfume->metric_longevity_hours = $profile['longevity']['text'] ?? '6 - 8 giờ';
            $perfume->metric_longevity_percent = $profile['longevity']['percent'] ?? 75;

            // Sillage
            $perfume->metric_sillage = $profile['sillage']['text'] ?? '1 cánh tay';

            // Price per ml
            $price = (float) ($perfume->sale_price ?? $perfume->price);
            $vol = max(10, (int) ($perfume->volume_ml ?: 100));
            $perfume->metric_price_per_ml = round($price / $vol);
        }

        return view('store.compare', compact('perfumes'));
    }

    /**
     * Tính năng 8: Hệ thống hạng thành viên (Silver, Rose, Premium VIP)
     */
    public function member(Request $request): View
    {
        $userId = $request->user()->id;
        $points = LoyaltyService::balance($userId);
        $totalSpent = LoyaltyService::totalSpent($userId);
        $tier = LoyaltyService::tier($userId);
        $orders = $request->user()->orders()->latest()->take(5)->get();

        return view('store.member', compact('points', 'totalSpent', 'tier', 'orders'));
    }

    /**
     * Tính năng 11: Mùi hương hôm nay ("Scent of the Day")
     */
    public function scentOfTheDay(): View
    {
        $all = Perfume::where('is_active', true)->get();
        if ($all->isEmpty()) {
            abort(404);
        }

        // Chọn ổn định theo ngày hôm nay (mỗi ngày 1 chai duy nhất trên toàn quốc)
        $dayIndex = (int) (date('z') + date('Y'));
        $perfume = $all[$dayIndex % $all->count()];

        $quotes = [
            __('“Hương thơm là dấu ấn vô hình, nhưng sâu đậm nhất của một người khi bước vào căn phòng.”'),
            __('“Hãy khoác lên mình một mùi hương khiến bạn cảm thấy tự tin và đáng yêu nhất hôm nay.”'),
            __('“Mỗi giọt nước hoa là một nốt nhạc, và hôm nay là khúc ca của riêng bạn.”'),
            __('“Không gì khơi dậy ký ức ngọt ngào nhanh bằng một làn hương thân thuộc.”'),
            __('“Thơm tho không chỉ vì người khác nhìn ngắm, mà là để yêu chiều chính tâm hồn mình.”'),
        ];
        $quote = $quotes[$dayIndex % count($quotes)];

        $regularPrice = (int) ($perfume->sale_price ?? $perfume->price);
        $coupon = Coupon::where('code', 'TODAY10')->first();
        $coupon = $coupon?->isAvailableFor($regularPrice) ? $coupon : null;
        $todayCode = $coupon?->code;
        $dealPrice = $regularPrice - ($coupon?->discountFor($regularPrice) ?? 0);
        $dailyEditorial = FragranceEditorialService::forPerfume($perfume);

        return view('store.scent-of-the-day', compact('perfume', 'quote', 'todayCode', 'regularPrice', 'dealPrice', 'dailyEditorial'));
    }

    /**
     * Tính năng 13: Gửi link sản phẩm để tặng bạn bè ("Tặng quà cho bạn")
     */
    public function giftShare(Request $request): View
    {
        $data = $request->validate([
            'id' => 'nullable|integer|min:1',
            'from' => 'nullable|string|max:100',
            'to' => 'nullable|string|max:100',
            'card' => 'nullable|string|max:40',
            'msg' => 'nullable|string|max:1500',
        ]);
        $perfumeId = (int) ($data['id'] ?? 0);
        $perfume = Perfume::where('is_active', true)->find($perfumeId) ?: Perfume::where('is_active', true)->first();
        abort_unless($perfume, 404);

        $senderName = $data['from'] ?? 'Người bạn giấu tên';
        $recipientName = $data['to'] ?? 'Bạn thân mến';
        $cardType = $data['card'] ?? 'birthday';
        $message = $data['msg'] ?? __('Mong rằng món quà mùi hương ngọt ngào này sẽ mang lại cho bạn thật nhiều niềm vui và nụ cười rạng rỡ!');

        return view('store.gift-share', compact('perfume', 'senderName', 'recipientName', 'cardType', 'message'));
    }

    public function wishlist(Request $request): View
    {
        $ids = DB::table('wishlists')->where('user_id', $request->user()->id)->pluck('perfume_id');
        $perfumes = Perfume::with('variants')->whereIn('id', $ids)->where('is_active', true)->get();
        $alerts = DB::table('stock_alerts')->where('user_id', $request->user()->id)->pluck('perfume_id')->all();

        return view('store.wishlist', compact('perfumes', 'alerts'));
    }

    public function toggleWishlist(Request $request, Perfume $perfume): RedirectResponse|JsonResponse
    {
        abort_unless($perfume->is_active, 404);
        $data = $request->validate(['saved' => ['sometimes', 'required', 'boolean']]);
        $key = ['user_id' => $request->user()->id, 'perfume_id' => $perfume->id];
        $saved = array_key_exists('saved', $data)
            ? (bool) $data['saved']
            : ! DB::table('wishlists')->where($key)->exists();

        if ($saved) {
            DB::table('wishlists')->insertOrIgnore($key + ['created_at' => now(), 'updated_at' => now()]);
            $message = __('Đã lưu mùi hương yêu thích.');
        } else {
            DB::table('wishlists')->where($key)->delete();
            $message = __('Đã bỏ sản phẩm khỏi danh sách yêu thích.');
        }

        if ($request->expectsJson()) {
            return response()->json(['perfume_id' => $perfume->id, 'saved' => $saved, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function review(Request $request, Perfume $perfume): RedirectResponse
    {
        abort_unless($perfume->is_active, 404);
        $purchased = Order::where('user_id', $request->user()->id)
            ->where('status', '!=', 'cancelled')
            ->where(fn ($query) => $query->where('status', 'completed')->orWhere('shipping_status', 'delivered'))
            ->whereHas('items', fn ($items) => $items->where('perfume_id', $perfume->id))->exists();
        if (! $purchased) {
            return back()->withErrors(['review' => __('Bạn chỉ có thể đánh giá sản phẩm đã mua và nhận hàng thành công.')]);
        }
        $data = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'body' => 'required|string|min:10|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);
        $review = PerfumeReview::firstOrNew(['user_id' => $request->user()->id, 'perfume_id' => $perfume->id]);
        if ($request->hasFile('image')) {
            File::ensureDirectoryExists(public_path('images/reviews'));
            $name = Str::uuid().'.'.$request->file('image')->extension();
            $request->file('image')->move(public_path('images/reviews'), $name);
            $review->image_path = 'images/reviews/'.$name;
        }
        $review->fill(['rating' => $data['rating'], 'body' => $data['body']])->save();

        return back()->with('success', __('Cảm ơn bạn đã chia sẻ cảm nhận.'));
    }

    public function stockAlert(Request $request, Perfume $perfume): RedirectResponse
    {
        abort_unless($perfume->is_active, 404);
        if ($perfume->availableStock() > 0) {
            return back()->with('success', __('Sản phẩm đang có hàng, bạn có thể đặt ngay.'));
        }
        $key = ['user_id' => $request->user()->id, 'perfume_id' => $perfume->id];
        DB::table('stock_alerts')->updateOrInsert($key, ['notified_at' => null, 'updated_at' => now(), 'created_at' => now()]);

        return back()->with('success', __('Đã lưu yêu cầu. Chúng tôi sẽ báo khi sản phẩm có hàng.'));
    }

    public function faq(): View
    {
        return view('store.faq');
    }
}
