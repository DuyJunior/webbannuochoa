<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Livestream;
use App\Models\Perfume;
use App\Services\LivekitTokenService;
use App\Services\LivestreamRevenueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LivestreamController extends Controller
{
    private const SCHEDULE_TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function index(Request $request, LivekitTokenService $livekit, LivestreamRevenueService $revenue): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:live,scheduled,ended'],
        ]);
        $search = trim($filters['search'] ?? '');
        $today = now(self::SCHEDULE_TIMEZONE)->startOfDay();

        return view('admin.livestreams.index', [
            'livestreams' => Livestream::with(['products', 'creator'])
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
                ->latest()->paginate(15)->withQueryString(),
            'onAir' => Livestream::where('status', 'live')->latest()->first(),
            'livekitConfigured' => $livekit->configured(),
            'liveRevenue' => $revenue->summaries(),
            'revenuePeriods' => [
                'today' => $revenue->summaries($today->copy()->utc())['total'],
                'week' => $revenue->summaries($today->copy()->subDays(6)->utc())['total'],
                'month' => $revenue->summaries($today->copy()->subDays(29)->utc())['total'],
            ],
            'stats' => [
                'live' => Livestream::where('status', 'live')->count(),
                'scheduled' => Livestream::where('status', 'scheduled')->count(),
                'ended' => Livestream::where('status', 'ended')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.livestreams.form', [
            'livestream' => new Livestream,
            'perfumes' => Perfume::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function report(Livestream $livestream, LivestreamRevenueService $revenue): View
    {
        $today = now(self::SCHEDULE_TIMEZONE)->startOfDay();
        $empty = ['revenue' => 0, 'orders' => 0, 'units' => 0, 'pending_value' => 0, 'pending_orders' => 0];
        $periods = [];
        foreach (['all' => null, 'today' => 0, 'week' => 6, 'month' => 29] as $name => $daysAgo) {
            $from = $daysAgo === null ? null : $today->copy()->subDays($daysAgo)->utc();
            $periods[$name] = $revenue->summaries($from)['by_stream'][$livestream->id] ?? $empty;
        }

        $clicks = DB::table('livestream_product_clicks')
            ->where('livestream_id', $livestream->id)
            ->selectRaw('perfume_id, count(*) as total')
            ->groupBy('perfume_id')->orderByDesc('total')->get();
        $perfumeNames = Perfume::whereIn('id', $clicks->pluck('perfume_id')->filter())->pluck('name', 'id');

        return view('admin.livestreams.report', [
            'livestream' => $livestream->load('products'),
            'periods' => $periods,
            'viewerTotal' => DB::table('livestream_viewers')->where('livestream_id', $livestream->id)->count(),
            'clickTotal' => $clicks->sum('total'),
            'clickedProducts' => $clicks->map(fn ($row) => [
                'name' => $perfumeNames[$row->perfume_id] ?? 'Sản phẩm đã xóa',
                'clicks' => (int) $row->total,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $launchMode = $request->input('launch_mode');
        $data['created_by'] = $request->user()->id;
        $this->ensureSingleLive($data['status']);
        $productIds = $data['perfume_ids'];
        unset($data['perfume_ids']);
        $livestream = DB::transaction(function () use ($data, $productIds) {
            $stream = Livestream::create($data);
            $this->syncProducts($stream, $productIds);

            return $stream;
        });

        if ($launchMode === 'schedule') {
            return redirect()->route('admin.livestreams.index')
                ->with('success', __('Đã đặt lịch livestream. Khi đến giờ, nhân viên vào studio để bắt đầu phát.'));
        }

        if ($livestream->source === 'browser') {
            return redirect()->route('admin.livestreams.studio', $livestream)
                ->with('success', __('Đã tạo buổi live. Bật camera và micro trong studio để lên sóng ngay.'));
        }

        return redirect()->route('admin.livestreams.index')->with('success', __('Đã tạo buổi livestream.'));
    }

    public function edit(Livestream $livestream): View
    {
        return view('admin.livestreams.form', [
            'livestream' => $livestream->load('products'),
            'perfumes' => Perfume::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Livestream $livestream): RedirectResponse
    {
        $data = $this->validatedData($request, $livestream);
        abort_if($livestream->isBrowserOnAir() && $data['source'] !== 'browser', 409);
        $this->ensureSingleLive($data['status'], $livestream->id);
        if ($data['status'] === 'ended') {
            $data['presenter_id'] = null;
            $data['last_heartbeat_at'] = null;
        }
        $productIds = $data['perfume_ids'];
        unset($data['perfume_ids']);
        DB::transaction(function () use ($livestream, $data, $productIds) {
            $livestream->update($data);
            $this->syncProducts($livestream, $productIds);
            if ($livestream->pinned_perfume_id && ! in_array($livestream->pinned_perfume_id, array_map('intval', $productIds), true)) {
                $livestream->update(['pinned_perfume_id' => null]);
            }
        });

        return redirect()->route('admin.livestreams.index')->with('success', __('Đã cập nhật buổi livestream.'));
    }

    public function changeStatus(Request $request, Livestream $livestream): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:live,ended']]);
        if ($livestream->source === 'browser' && $data['status'] === 'live') {
            throw ValidationException::withMessages(['status' => __('Hãy vào studio, bật camera và micro rồi bắt đầu phát.')]);
        }
        $this->ensureSingleLive($data['status'], $livestream->id);
        $livestream->update([
            'status' => $data['status'],
            'presenter_id' => $data['status'] === 'ended' ? null : $livestream->presenter_id,
            'last_heartbeat_at' => $data['status'] === 'ended' ? null : $livestream->last_heartbeat_at,
        ]);

        return redirect()->route('admin.livestreams.index')
            ->with('success', $data['status'] === 'live' ? __('Buổi phát đã hiển thị trên website.') : __('Buổi livestream đã kết thúc.'));
    }

    public function destroy(Livestream $livestream): RedirectResponse
    {
        if ($livestream->orderItems()->exists()) {
            throw ValidationException::withMessages(['livestream' => __('Buổi live đã có đơn hàng. Hãy giữ lại để xem báo cáo doanh thu.')]);
        }
        $livestream->delete();

        return redirect()->route('admin.livestreams.index')->with('success', __('Đã xóa buổi livestream.'));
    }

    private function validatedData(Request $request, ?Livestream $existing = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'in:browser,youtube'],
            'youtube_url' => ['required_if:source,youtube', 'nullable', 'url', 'max:2048'],
            'launch_mode' => ['nullable', 'in:now,schedule'],
            'status' => ['required', 'in:scheduled,live,ended'],
            'starts_at' => ['nullable', 'date'],
            'perfume_id' => ['nullable', 'exists:perfumes,id'],
            'perfume_ids' => ['nullable', 'array', 'max:50'],
            'perfume_ids.*' => ['integer', 'distinct', Rule::exists('perfumes', 'id')->where('is_active', true)],
        ]);
        $data['perfume_ids'] = $data['perfume_ids'] ?? (isset($data['perfume_id']) ? [$data['perfume_id']] : []);
        $data['perfume_id'] = $data['perfume_ids'][0] ?? null;
        $data['source'] = $data['source'] ?? 'youtube';

        if (! $existing && isset($data['launch_mode'])) {
            if ($data['launch_mode'] === 'schedule') {
                if (empty($data['starts_at'])) {
                    throw ValidationException::withMessages(['starts_at' => __('Vui lòng chọn ngày và giờ phát.')]);
                }
                $data['status'] = 'scheduled';
            } else {
                $data['status'] = $data['source'] === 'youtube' ? 'live' : 'scheduled';
                $data['starts_at'] = null;
            }
        }

        if (! empty($data['starts_at'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $data['starts_at'])) {
                throw ValidationException::withMessages(['starts_at' => __('Giờ phát phải đúng định dạng ngày và giờ địa phương.')]);
            }
            $localTime = Carbon::parse($data['starts_at'], self::SCHEDULE_TIMEZONE);
            $changed = ! $existing || $localTime->format('Y-m-d H:i') !== $existing->starts_at?->format('Y-m-d H:i');
            if ($data['status'] === 'scheduled' && $changed && $localTime->lessThanOrEqualTo(now(self::SCHEDULE_TIMEZONE))) {
                throw ValidationException::withMessages(['starts_at' => __('Giờ đặt lịch phải ở tương lai theo giờ Việt Nam (UTC+7).')]);
            }
            // The existing datetime column stores Vietnam local wall time without timezone.
            $data['starts_at'] = $localTime->format('Y-m-d H:i:s');
        }
        unset($data['launch_mode']);

        if ($data['source'] === 'browser') {
            if ($data['status'] === 'live' && ! ($existing?->source === 'browser' && $existing->status === 'live')) {
                throw ValidationException::withMessages(['status' => __('Livestream trên web chỉ bắt đầu sau khi camera và micro kết nối trong studio.')]);
            }
            unset($data['youtube_url']);
            $data['youtube_video_id'] = null;

            return $data;
        }

        if (empty($data['youtube_url'])) {
            throw ValidationException::withMessages(['youtube_url' => __('Vui lòng nhập liên kết YouTube Live.')]);
        }
        $parts = parse_url($data['youtube_url']);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $id = null;
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $id = $path === '/watch' ? ($query['v'] ?? null) : (preg_match('~^/(?:live|embed)/([A-Za-z0-9_-]{11})/?$~', $path, $m) ? $m[1] : null);
        } elseif ($host === 'youtu.be') {
            $id = trim($path, '/');
        }
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            throw ValidationException::withMessages(['youtube_url' => __('Vui lòng nhập link YouTube Live hợp lệ.')]);
        }

        unset($data['youtube_url']);
        $data['youtube_video_id'] = $id;

        return $data;
    }

    private function ensureSingleLive(string $status, ?int $exceptId = null): void
    {
        if ($status === 'live' && Livestream::where('status', 'live')->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))->exists()) {
            throw ValidationException::withMessages(['status' => __('Hãy kết thúc buổi đang phát trước khi mở buổi mới.')]);
        }
    }

    private function syncProducts(Livestream $livestream, array $productIds): void
    {
        $products = [];
        foreach ($productIds as $index => $id) {
            $products[(int) $id] = ['sort_order' => $index];
        }
        $livestream->products()->sync($products);
    }
}
