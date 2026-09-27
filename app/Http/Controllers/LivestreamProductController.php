<?php

namespace App\Http\Controllers;

use App\Models\Livestream;
use App\Models\Perfume;
use App\Services\LivestreamVisitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LivestreamProductController extends Controller
{
    public function index(Livestream $livestream): JsonResponse
    {
        $products = $livestream->products()->where('is_active', true)->get()
            ->sortByDesc(fn ($product) => $product->id === $livestream->pinned_perfume_id)->values();
        $onAir = $livestream->status === 'live'
            && ($livestream->source === 'youtube' || $livestream->isBrowserOnAir());

        return response()->json([
            'html' => view('livestream.products', compact('livestream', 'products', 'onAir'))->render(),
        ]);
    }

    public function openProduct(Request $request, Livestream $livestream, Perfume $perfume): RedirectResponse
    {
        abort_unless($perfume->is_active && $livestream->products()->whereKey($perfume->id)->exists(), 404);

        if ($livestream->status === 'live'
            && ($livestream->source === 'youtube' || $livestream->isBrowserOnAir())) {
            DB::table('livestream_product_clicks')->insert([
                'livestream_id' => $livestream->id,
                'perfume_id' => $perfume->id,
                'session_hash' => app(LivestreamVisitor::class)->hash($request),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $request->session()->put('live_product', [
                'livestream_id' => $livestream->id,
                'perfume_id' => $perfume->id,
                'clicked_at' => now()->timestamp,
            ]);
        } else {
            $request->session()->forget('live_product');
        }

        return redirect()->route('perfumes.show', $perfume);
    }

    public function store(Request $request, Livestream $livestream): JsonResponse
    {
        abort_if($livestream->status === 'ended', 409, 'Buổi live đã kết thúc.');
        $data = $request->validate([
            'perfume_id' => ['required', 'integer', Rule::exists('perfumes', 'id')->where('is_active', true)],
        ]);

        DB::transaction(function () use ($livestream, $data) {
            $stream = Livestream::query()->lockForUpdate()->findOrFail($livestream->id);
            abort_if($stream->products()->whereKey($data['perfume_id'])->exists(), 409, 'Sản phẩm đã có trong buổi live.');
            abort_if($stream->products()->count() >= 50, 409, 'Mỗi buổi live tối đa 50 sản phẩm.');
            $nextPosition = (int) $stream->products()->max('sort_order') + 1;
            $stream->products()->attach($data['perfume_id'], ['sort_order' => $nextPosition]);
            if (!$stream->perfume_id) $stream->update(['perfume_id' => $data['perfume_id']]);
        });

        return $this->studioProducts($livestream);
    }

    public function destroy(Livestream $livestream, Perfume $perfume): JsonResponse
    {
        abort_if($livestream->status === 'ended', 409, 'Buổi live đã kết thúc.');
        $livestream->products()->detach($perfume->id);
        if ($livestream->pinned_perfume_id === (int) $perfume->id) {
            $livestream->update(['pinned_perfume_id' => null]);
        }
        if ($livestream->perfume_id === $perfume->id) {
            $livestream->update(['perfume_id' => $livestream->products()->first()?->id]);
        }

        return $this->studioProducts($livestream);
    }

    public function pin(Request $request, Livestream $livestream): JsonResponse
    {
        abort_if($livestream->status === 'ended', 409, 'Buổi live đã kết thúc.');
        $data = $request->validate(['perfume_id' => ['nullable', 'integer']]);
        $id = $data['perfume_id'] ?? null;
        abort_if($id && !$livestream->products()->whereKey($id)->exists(), 422, 'Sản phẩm không có trong buổi live.');
        $livestream->update(['pinned_perfume_id' => $id]);

        return $this->studioProducts($livestream);
    }

    private function studioProducts(Livestream $livestream): JsonResponse
    {
        return response()->json([
            'html' => view('admin.livestreams.products', [
                'livestream' => $livestream,
                'products' => $livestream->products()->get(),
            ])->render(),
        ]);
    }
}
