<?php

namespace App\Http\Controllers;

use App\Models\Livestream;
use App\Models\Perfume;
use App\Services\LivekitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LivekitRoomController extends Controller
{
    public function studio(Livestream $livestream, LivekitTokenService $livekit): View
    {
        abort_unless($livestream->source === 'browser', 404);

        return view('admin.livestreams.studio', [
            'livestream' => $livestream->load('products'),
            'configured' => $livekit->configured(),
            'livekitUrl' => $livekit->url(),
            'perfumes' => Perfume::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function hostToken(Request $request, Livestream $livestream, LivekitTokenService $livekit): JsonResponse
    {
        abort_unless($livestream->source === 'browser' && $livestream->status !== 'ended', 409);
        abort_unless($livekit->configured(), 503, __('Máy chủ video chưa được cấu hình.'));
        abort_if(
            $livestream->isBrowserOnAir() && $livestream->presenter_id !== $request->user()->id,
            409,
            __('Đã có nhân viên khác đang phát buổi này.')
        );

        return response()->json([
            'server_url' => $livekit->url(),
            'participant_token' => $livekit->token($livestream, true, 'presenter-'.$request->user()->id),
        ]);
    }

    public function viewerToken(Livestream $livestream, LivekitTokenService $livekit): JsonResponse
    {
        abort_unless($livestream->isBrowserOnAir(), 409, __('Buổi phát hiện chưa trực tuyến.'));
        abort_unless($livekit->configured(), 503, __('Máy chủ video chưa sẵn sàng.'));

        return response()->json([
            'server_url' => $livekit->url(),
            'participant_token' => $livekit->token($livestream, false, 'viewer-'.Str::uuid()),
        ]);
    }

    public function begin(Request $request, Livestream $livestream, LivekitTokenService $livekit): JsonResponse
    {
        abort_unless($livekit->configured(), 503, __('Máy chủ video chưa được cấu hình.'));
        abort_unless($livestream->source === 'browser' && $livestream->status !== 'ended', 409);
        abort_if(
            $livestream->isBrowserOnAir() && $livestream->presenter_id !== $request->user()->id,
            409,
            __('Đã có nhân viên khác đang phát buổi này.')
        );
        abort_if(Livestream::where('status', 'live')->whereKeyNot($livestream->id)->exists(), 409, __('Hãy kết thúc buổi live đang phát trước.'));

        $livestream->update([
            'status' => 'live',
            'presenter_id' => $request->user()->id,
            'last_heartbeat_at' => now(),
        ]);

        return response()->json(['status' => 'live']);
    }

    public function heartbeat(Request $request, Livestream $livestream): JsonResponse
    {
        abort_unless($livestream->source === 'browser' && $livestream->status === 'live', 409);
        abort_unless($livestream->presenter_id === $request->user()->id, 403);
        $livestream->update(['last_heartbeat_at' => now()]);

        return response()->json(['status' => 'live']);
    }

    public function finish(Request $request, Livestream $livestream): JsonResponse
    {
        abort_unless($livestream->source === 'browser', 409);
        abort_unless($livestream->presenter_id === $request->user()->id || $request->user()->role === 'admin', 403);
        $livestream->update(['status' => 'ended', 'last_heartbeat_at' => null, 'presenter_id' => null]);

        return response()->json(['status' => 'ended']);
    }
}
