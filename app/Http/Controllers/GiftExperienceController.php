<?php

namespace App\Http\Controllers;

use App\Models\GiftExperience;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GiftExperienceController extends Controller
{
    public function index(Request $request)
    {
        $gifts = GiftExperience::where('user_id', $request->user()->id)->with('perfume')->latest()->paginate(12);

        return view('gifts.index', compact('gifts'));
    }

    public function create(Request $request)
    {
        $gift = new GiftExperience(['sender_name' => $request->user()->name, 'perfume_id' => $request->integer('perfume_id') ?: null]);
        if ($request->filled('order_id')) {
            $order = Order::where('user_id', $request->user()->id)->where('status', '!=', 'cancelled')->findOrFail($request->integer('order_id'));
            $gift->order_id = $order->id;
            $gift->message = $order->gift_message ?? '';
        }

        return $this->editor($gift);
    }

    public function edit(Request $request, GiftExperience $gift)
    {
        $this->owner($request, $gift);

        return $this->editor($gift);
    }

    private function editor(GiftExperience $gift)
    {
        $query = Perfume::where(fn ($query) => $query->where('is_active', true)->orWhere('id', $gift->perfume_id));
        if ($gift->order_id) {
            $ids = $gift->order->items->flatMap(fn ($item) => $item->reviewProductIds());
            $query->whereIn('id', $ids);
        }
        $perfumes = $query->orderBy('name')->get();

        return view('gifts.edit', compact('gift', 'perfumes'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new GiftExperience);
    }

    public function update(Request $request, GiftExperience $gift)
    {
        $this->owner($request, $gift);

        return $this->save($request, $gift);
    }

    private function save(Request $request, GiftExperience $gift)
    {
        $data = $request->validate([
            'sender_name' => 'required|string|max:80', 'recipient_name' => 'required|string|max:80',
            'message' => 'required|string|max:3000',
            'perfume_id' => ['nullable', 'integer', Rule::exists('perfumes', 'id')->whereNull('deleted_at')],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')->where('user_id', $request->user()->id)->where('status', '!=', 'cancelled')],
            'pin' => [$gift->exists ? 'nullable' : 'required', 'regex:/^[0-9]{6}$/'],
            'days' => [$gift->exists ? 'nullable' : 'required', Rule::in([30, 90, 180])],
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000',
            'audio' => 'nullable|file|mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/aac,audio/ogg,application/ogg,audio/wav,audio/x-wav,audio/webm,video/webm|max:10240',
            'remove_photo' => 'nullable|boolean', 'remove_audio' => 'nullable|boolean',
        ]);
        if (! empty($data['order_id']) && ! empty($data['perfume_id'])) {
            $order = Order::with('items')->findOrFail($data['order_id']);
            $ids = $order->items->flatMap(fn ($item) => $item->reviewProductIds());
            if (! $ids->contains((int) $data['perfume_id'])) {
                throw ValidationException::withMessages(['perfume_id' => __('Hãy chọn sản phẩm có trong đơn hàng này.')]);
            }
        }
        $newFiles = [];
        $oldFiles = [];
        try {
            DB::transaction(function () use ($request, $data, &$gift, &$newFiles, &$oldFiles) {
                // Serialize creation to enforce the media quota, and edits to avoid orphaned uploads.
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                if ($gift->exists) {
                    $gift = GiftExperience::whereKey($gift->id)->lockForUpdate()->firstOrFail();
                } else {
                    if (GiftExperience::where('user_id', $request->user()->id)->count() >= 20) {
                        throw ValidationException::withMessages(['message' => __('Bạn có thể lưu tối đa 20 trang quà. Hãy xóa trang không còn dùng trước khi tạo mới.')]);
                    }
                    $gift->user_id = $request->user()->id;
                    $gift->token = Str::random(48);
                    $gift->access_version = 1;
                }
                $gift->fill(collect($data)->only(['sender_name', 'recipient_name', 'message', 'perfume_id', 'order_id'])->all());
                if (! empty($data['pin'])) {
                    $gift->pin_hash = Hash::make($data['pin']);
                    $gift->access_version++;
                }
                if (! empty($data['days'])) {
                    $gift->expires_at = now()->addDays((int) $data['days']);
                }
                foreach (['photo', 'audio'] as $kind) {
                    if ($request->hasFile($kind) || $request->boolean('remove_'.$kind)) {
                        if ($gift->{$kind.'_path'}) {
                            $oldFiles[] = $gift->{$kind.'_path'};
                        }
                        $gift->{$kind.'_path'} = null;
                        if ($request->hasFile($kind)) {
                            $path = $request->file($kind)->store($gift->token, 'gifts');
                            $newFiles[] = $path;
                            $gift->{$kind.'_path'} = $path;
                        }
                    }
                }
                $gift->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('gifts')->delete($newFiles);
            throw $e;
        }
        Storage::disk('gifts')->delete($oldFiles);

        return redirect()->route('gifts.edit', $gift)->with('success', __('Đã lưu trang quà. Bạn có thể tải QR và gửi mã riêng cho người nhận.'));
    }

    public function destroy(Request $request, GiftExperience $gift)
    {
        $this->owner($request, $gift);
        DB::transaction(function () use ($gift) {
            $gift = GiftExperience::whereKey($gift->id)->lockForUpdate()->firstOrFail();
            Storage::disk('gifts')->delete(array_filter([$gift->photo_path, $gift->audio_path]));
            $gift->delete();
        });

        return redirect()->route('gifts.index')->with('success', __('Đã xóa trang quà, ảnh và ghi âm. QR cũ không còn sử dụng được.'));
    }

    public function preview(Request $request, GiftExperience $gift)
    {
        $this->owner($request, $gift);

        return view('gifts.open', ['gift' => $gift->load('perfume'), 'preview' => true]);
    }

    public function card(Request $request, GiftExperience $gift)
    {
        $this->owner($request, $gift);

        return view('gifts.card', compact('gift'));
    }

    public function show(Request $request, string $token)
    {
        $gift = $this->active($token);
        if (! $this->unlocked($request, $gift)) {
            return view('gifts.locked', compact('token'));
        }

        return view('gifts.open', ['gift' => $gift->load('perfume'), 'preview' => false]);
    }

    public function unlock(Request $request, string $token)
    {
        $gift = $this->active($token);
        $data = $request->validate(['pin' => ['required', 'regex:/^[0-9]{6}$/']]);
        $key = 'gift-unlock:'.hash('sha256', $token.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['pin' => __('Bạn đã thử nhiều lần. Vui lòng đợi 10 phút rồi thử lại.')]);
        }
        RateLimiter::hit($key, 600);
        if (! Hash::check($data['pin'], $gift->pin_hash)) {
            throw ValidationException::withMessages(['pin' => __('Mã mở quà chưa đúng. Hãy hỏi lại người tặng.')]);
        }
        RateLimiter::clear($key);
        $request->session()->put('gift_access.'.$gift->id, ['version' => $gift->access_version, 'until' => now()->addHour()->timestamp]);
        GiftExperience::whereKey($gift->id)->whereNull('opened_at')->update(['opened_at' => now()]);

        return redirect()->route('gifts.open', $token);
    }

    public function lock(Request $request, string $token)
    {
        $gift = $this->active($token);
        $request->session()->forget('gift_access.'.$gift->id);

        return redirect()->route('gifts.open', $token);
    }

    public function thank(Request $request, string $token)
    {
        $gift = $this->active($token);
        abort_unless($this->unlocked($request, $gift), 403);
        $data = $request->validate(['thank_you' => 'required|string|min:3|max:1000']);
        DB::transaction(function () use ($request, $gift, $data) {
            $locked = GiftExperience::whereKey($gift->id)->lockForUpdate()->firstOrFail();
            abort_unless(! $locked->isExpired() && $this->unlocked($request, $locked), 403);
            if (! $locked->thanked_at) {
                $locked->update(['thank_you' => $data['thank_you'], 'thanked_at' => now()]);
            }
        });

        return redirect()->route('gifts.open', $token)->with('gift_thanked', true);
    }

    public function media(Request $request, string $token, string $kind)
    {
        $gift = GiftExperience::where('token', $token)->firstOrFail();
        $isOwner = $request->user() && $request->user()->id === $gift->user_id;
        abort_unless($isOwner || (! $gift->isExpired() && $this->unlocked($request, $gift)), 403);
        abort_unless(in_array($kind, ['photo', 'audio'], true) && $gift->{$kind.'_path'}, 404);
        $path = $gift->{$kind.'_path'};
        abort_unless(Storage::disk('gifts')->exists($path), 404);

        return response()->file(Storage::disk('gifts')->path($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function active(string $token): GiftExperience
    {
        $gift = GiftExperience::where('token', $token)->firstOrFail();
        if ($gift->isExpired()) {
            abort(response()->view('gifts.expired', [], 410)->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow')->header('Referrer-Policy', 'no-referrer'));
        }

        return $gift;
    }

    private function unlocked(Request $request, GiftExperience $gift): bool
    {
        $access = $request->session()->get('gift_access.'.$gift->id, []);

        return ($access['version'] ?? 0) === $gift->access_version && ($access['until'] ?? 0) > now()->timestamp;
    }

    private function owner(Request $request, GiftExperience $gift): void
    {
        abort_unless($gift->user_id === $request->user()->id, 404);
    }
}
