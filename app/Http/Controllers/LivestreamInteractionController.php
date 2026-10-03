<?php

namespace App\Http\Controllers;

use App\Models\Livestream;
use App\Services\LivestreamVisitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LivestreamInteractionController extends Controller
{
    public function __construct(private readonly LivestreamVisitor $visitor) {}

    public function messages(Request $request, Livestream $livestream): JsonResponse
    {
        $staff = (bool) $request->user()?->canManageLivestreams();
        abort_unless($staff || $this->onAir($livestream), 409, __('Buổi live chưa bắt đầu.'));

        $messages = DB::table('livestream_messages')
            ->where('livestream_id', $livestream->id)
            ->where('is_hidden', false)
            ->latest('id')->limit(60)->get(['id', 'display_name', 'body', 'is_staff', 'created_at'])
            ->reverse()->values();

        return response()->json([
            'messages' => $messages,
            'watching' => DB::table('livestream_viewers')
                ->where('livestream_id', $livestream->id)
                ->where('last_seen_at', '>=', now()->subSeconds(45))->count(),
            'on_air' => $this->onAir($livestream),
        ])->header('Cache-Control', 'no-store');
    }

    public function send(Request $request, Livestream $livestream): JsonResponse
    {
        abort_unless($this->onAir($livestream), 409, __('Buổi live chưa bắt đầu hoặc đã kết thúc.'));
        $request->merge(['body' => trim((string) $request->input('body'))]);
        $data = $request->validate(['body' => ['required', 'string', 'max:300']]);
        $staff = (bool) $request->user()?->canManageLivestreams();
        $name = $request->user()?->name ?: 'Khách #'.strtoupper(substr($this->visitor->hash($request), 0, 4));

        $id = DB::table('livestream_messages')->insertGetId([
            'livestream_id' => $livestream->id,
            'user_id' => $request->user()?->id,
            'display_name' => mb_substr($name, 0, 60),
            'body' => $data['body'],
            'is_staff' => $staff,
            'is_hidden' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $id], 201);
    }

    public function hide(Livestream $livestream, int $message): JsonResponse
    {
        $changed = DB::table('livestream_messages')
            ->where('livestream_id', $livestream->id)->where('id', $message)
            ->update(['is_hidden' => true, 'updated_at' => now()]);
        abort_unless($changed, 404);

        return response()->json(['hidden' => true]);
    }

    public function presence(Request $request, Livestream $livestream): JsonResponse
    {
        abort_unless($this->onAir($livestream), 409, __('Buổi live chưa bắt đầu.'));
        $now = now();
        DB::table('livestream_viewers')->upsert([[
            'livestream_id' => $livestream->id,
            'session_hash' => $this->visitor->hash($request),
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['livestream_id', 'session_hash'], ['last_seen_at', 'updated_at']);

        return response()->json(['ok' => true]);
    }

    private function onAir(Livestream $livestream): bool
    {
        return $livestream->status === 'live'
            && ($livestream->source === 'youtube' || $livestream->isBrowserOnAir());
    }
}
