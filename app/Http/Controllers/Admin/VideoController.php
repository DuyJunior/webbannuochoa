<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perfume;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(Request $request): View
    {
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

        $video = Video::create($data);

        // Đồng bộ video_url sang sản phẩm nếu có chọn sản phẩm và sản phẩm chưa có video
        if ($video->perfume_id) {
            $perfume = Perfume::find($video->perfume_id);
            if ($perfume && empty($perfume->video_url)) {
                $perfume->update(['video_url' => $video->video_url]);
            }
        }

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

        $video->update($data);

        // Đồng bộ video_url sang sản phẩm nếu có chọn sản phẩm
        if ($video->perfume_id) {
            $perfume = Perfume::find($video->perfume_id);
            if ($perfume) {
                $perfume->update(['video_url' => $video->video_url]);
            }
        }

        return redirect()->route('admin.videos.index')
            ->with('success', 'Đã cập nhật thông tin video thành công.');
    }

    public function destroy(Video $video): RedirectResponse
    {
        $video->delete();
        return redirect()->route('admin.videos.index')
            ->with('success', 'Đã xóa video khỏi hệ thống.');
    }

    public function toggle(Video $video): RedirectResponse
    {
        $video->update(['is_active' => ! $video->is_active]);
        $statusText = $video->is_active ? 'Hiển thị' : 'Tạm ẩn';
        return back()->with('success', "Đã chuyển trạng thái video sang: {$statusText}.");
    }

    private function validatedData(Request $request, ?Video $video = null): array
    {
        return $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'video_url'      => ['required', 'string', 'max:2048'],
            'perfume_id'     => ['nullable', 'exists:perfumes,id'],
            'thumbnail_url'  => ['nullable', 'string', 'max:2048'],
            'thumbnail_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'duration'       => ['nullable', 'string', 'max:20'],
            'views_count'    => ['nullable', 'integer', 'min:0'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'placement'      => ['required', 'in:home,product,all'],
            'sort_order'     => ['nullable', 'integer'],
            'is_active'      => ['nullable', 'boolean'],
        ], [
            'title.required'     => 'Vui lòng nhập tiêu đề cho video.',
            'video_url.required' => 'Vui lòng nhập đường dẫn video (YouTube, TikTok hoặc MP4).',
            'placement.required' => 'Vui lòng chọn vị trí hiển thị video.',
            'thumbnail_file.image' => 'Ảnh bìa tải lên phải là định dạng hình ảnh.',
            'thumbnail_file.max' => 'Ảnh bìa không được vượt quá 5MB.',
        ]);
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
