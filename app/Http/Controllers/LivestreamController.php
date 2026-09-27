<?php

namespace App\Http\Controllers;

use App\Models\Livestream;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LivestreamController extends Controller
{
    public function show(): View
    {
        $livestream = $this->current(true);
        return view('livestream.show', [
            'livestream' => $livestream,
            'products' => $livestream?->products()->where('is_active', true)->get()
                ->sortByDesc(fn ($product) => $product->id === $livestream->pinned_perfume_id)->values() ?? collect(),
        ]);
    }

    public function state(): JsonResponse
    {
        $livestream = $this->current();
        $onAir = $livestream && (
            ($livestream->source === 'youtube' && $livestream->status === 'live')
            || $livestream->isBrowserOnAir()
        );

        return response()->json([
            'livestream_id' => $livestream?->id,
            'on_air' => (bool) $onAir,
            'title' => $onAir ? $livestream->title : null,
            'source' => $onAir ? $livestream->source : null,
            'viewer_token_url' => $onAir && $livestream->source === 'browser' ? route('livestream.viewer-token', $livestream) : null,
            'embed_url' => $onAir && $livestream->source === 'youtube' ? $livestream->embed_url : null,
        ]);
    }

    private function current(bool $withPerfume = false): ?Livestream
    {
        $localNow = now('Asia/Ho_Chi_Minh');
        $nowString = $localNow->format('Y-m-d H:i:s');
        $overdueCutoff = $localNow->copy()->subHours(2)->format('Y-m-d H:i:s');

        return Livestream::query()
            ->when($withPerfume, fn ($query) => $query->with('perfume'))
            ->where(function ($query) use ($overdueCutoff) {
                $query->where('status', 'live')
                    ->orWhere(function ($scheduled) use ($overdueCutoff) {
                        $scheduled->where('status', 'scheduled')
                            ->where(function ($date) use ($overdueCutoff) {
                                $date->whereNull('starts_at')->orWhere('starts_at', '>=', $overdueCutoff);
                            });
                    });
            })
            ->orderByRaw(
                "CASE WHEN status = 'live' THEN 0 WHEN starts_at <= ? THEN 1 WHEN starts_at IS NOT NULL THEN 2 ELSE 3 END",
                [$nowString]
            )
            ->orderByRaw("CASE WHEN starts_at <= ? THEN starts_at END DESC", [$nowString])
            ->orderBy('starts_at')
            ->first();
    }
}
