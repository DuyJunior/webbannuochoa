<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perfume;
use App\Models\Video;
use App\Rules\SafeVideoUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'placement' => ['nullable', 'in:all_filter,home,product,all'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $videos = Video::query()
            ->with('perfume')
            ->when($request->filled('search'), function ($query) use ($request) {
                $keyword = trim((string) $request->input('search'));
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhereHas('perfume', fn ($pq) => $pq->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->when($request->filled('placement') && $request->input('placement') !== 'all_filter', function ($query) use ($request) {
                $query->where('placement', $request->input('placement'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'active');
            })
            ->orderBy('sort_order', 'asc')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total'       => Video::count(),
            'active'      => Video::where('is_active', true)->count(),
            'total_views' => Video::sum('views_count'),
        ];

        return view('admin.videos.index', compact('videos', 'stats'));
    }

    public function create(): View
    {
        $perfumes = Perfume::orderBy('name')->get();
        return view('admin.videos.create', compact('perfumes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data = $this->handleThumbnailUpload($request, $data);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['views_count'] = (int) ($data['views_count'] ?? 1000);

        DB::transaction(function () use ($data) {
            $video = Video::create($data);
            $this->syncProductVideo($video);
        });

        return redirect()->route('admin.videos.index')
            ->with('success', 'Đã thêm video trải nghiệm / review mới thành công.');
    }

    public function edit(Video $video): View
    {
        $perfumes = Perfume::orderBy('name')->get();
        return view('admin.videos.edit', compact('video', 'perfumes'));
    }

    public function update(Request $request, Video $video): RedirectResponse
    {
        $data = $this->validatedData($request, $video);
        $data = $this->handleThumbnailUpload($request, $data);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['views_count'] = (int) ($data['views_count'] ?? 0);

        DB::transaction(function () use ($video, $data) {
            $this->clearCopiedProductVideo($video);
            $video->update($data);
            $this->syncProductVideo($video);
        });

        return redirect()->route('admin.videos.index')
            ->with('success', 'Đã cập nhật thông tin video thành công.');
    }

    public function destroy(Video $video): RedirectResponse
    {
        DB::transaction(function () use ($video) {
            $this->clearCopiedProductVideo($video);
            $video->delete();
        });
        return redirect()->route('admin.videos.index')
            ->with('success', 'Đã xóa video khỏi hệ thống.');
    }

    public function toggle(Video $video): RedirectResponse
    {
        DB::transaction(function () use ($video) {
            $this->clearCopiedProductVideo($video);
            $video->update(['is_active' => ! $video->is_active]);
            $this->syncProductVideo($video);
        });
        $statusText = $video->is_active ? 'Hiển thị' : 'Tạm ẩn';
        return back()->with('success', "Đã chuyển trạng thái video sang: {$statusText}.");
    }

    private function validatedData(Request $request, ?Video $video = null): array
    {
        return $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'video_url'      => ['bail', 'required', 'string', 'max:2048', new SafeVideoUrl],
            'perfume_id'     => ['nullable', 'exists:perfumes,id'],
            'thumbnail_url'  => ['nullable', 'string', 'max:2048'],
            'thumbnail_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'duration'       => ['nullable', 'string', 'max:20'],
            'views_count'    => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'placement'      => ['required', 'in:home,product,all'],
            'sort_order'     => ['nullable', 'integer', 'between:-2147483648,2147483647'],
            'is_active'      => ['nullable', 'boolean'],
        ], [
            'title.required'     => 'Vui lòng nhập tiêu đề cho video.',
            'video_url.required' => 'Vui lòng nhập đường dẫn video (YouTube, TikTok hoặc MP4).',
            'placement.required' => 'Vui lòng chọn vị trí hiển thị video.',
            'thumbnail_file.image' => 'Ảnh bìa tải lên phải là định dạng hình ảnh.',
            'thumbnail_file.max' => 'Ảnh bìa không được vượt quá 5MB.',
        ]);
    }

    private function clearCopiedProductVideo(Video $video): void
    {
        if ($video->perfume_id) {
            // Only remove a copied URL; preserve an independently configured product video.
            Perfume::whereKey($video->perfume_id)->where('video_url', $video->video_url)->update(['video_url' => null]);
        }
    }

    private function syncProductVideo(Video $video): void
    {
        if ($video->perfume_id && $video->is_active && in_array($video->placement, ['product', 'all'], true)) {
            Perfume::whereKey($video->perfume_id)
                ->where(fn ($query) => $query->whereNull('video_url')->orWhere('video_url', ''))
                ->update(['video_url' => $video->video_url]);
        }
    }

    private function handleThumbnailUpload(Request $request, array $data): array
    {
        unset($data['thumbnail_file']);

        if (!$request->hasFile('thumbnail_file')) {
            return $data;
        }

        $file = $request->file('thumbnail_file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $destination = public_path('images/videos');
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }
        $file->move($destination, $filename);
        $data['thumbnail_url'] = 'images/videos/' . $filename;

        return $data;
    }
}
