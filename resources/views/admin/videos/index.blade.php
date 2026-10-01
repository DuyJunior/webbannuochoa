@extends('layouts.admin')

@section('title', 'Quản lý Video & Shorts')
@section('page_title', 'Quản lý Video & Fragrance Shorts')

@section('content')
<div class="admin-videos-page">
    {{-- KPI Stats --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="admin-card d-flex align-items-center mb-0" style="padding: 18px 22px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #fff0f3; color: #db2777; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 16px;">
                    <i class="fa-solid fa-clapperboard"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">Tổng số Video</div>
                    <div style="font-size: 22px; font-weight: 800; color: #1e293b;">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="admin-card d-flex align-items-center mb-0" style="padding: 18px 22px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 16px;">
                    <i class="fa-solid fa-circle-play"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">Đang phát sóng</div>
                    <div style="font-size: 22px; font-weight: 800; color: #10b981;">{{ number_format($stats['active']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-card d-flex align-items-center mb-0" style="padding: 18px 22px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 16px;">
                    <i class="fa-solid fa-fire"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">Tổng lượt xem</div>
                    <div style="font-size: 22px; font-weight: 800; color: #3b82f6;">{{ number_format($stats['total_views']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Container Card --}}
    <div class="admin-card">
        {{-- Header & Actions --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h5 class="font-weight-bold mb-1" style="color: #0f172a;">Danh sách Video Review & Trải Nghiệm</h5>
                <p class="text-muted mb-0 small">Quản lý các video ngắn shorts trên Trang chủ và video review cận cảnh trên Trang chi tiết sản phẩm</p>
            </div>
            <a href="{{ route('admin.videos.create') }}" class="btn btn-primary px-3 py-2 font-weight-bold" style="border-radius: 8px;">
                <i class="fa-solid fa-plus mr-1"></i> Thêm video mới
            </a>
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('admin.videos.index') }}" class="mb-4">
            <div class="form-row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="small font-weight-bold text-muted mb-1">Tìm kiếm</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        </div>
                        <input type="text" name="search" class="form-control border-left-0" placeholder="Tên video, sản phẩm..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="small font-weight-bold text-muted mb-1">Vị trí hiển thị</label>
                    <select name="placement" class="form-control form-control-sm">
                        <option value="all_filter" {{ request('placement') === 'all_filter' || !request('placement') ? 'selected' : '' }}>Tất cả vị trí</option>
                        <option value="home" {{ request('placement') === 'home' ? 'selected' : '' }}>Trang chủ (Shorts)</option>
                        <option value="product" {{ request('placement') === 'product' ? 'selected' : '' }}>Chi tiết sản phẩm</option>
                        <option value="all" {{ request('placement') === 'all' ? 'selected' : '' }}>Cả 2 vị trí (Toàn sàn)</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="small font-weight-bold text-muted mb-1">Trạng thái</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hiển thị</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm flex-fill font-weight-bold">
                        <i class="fa-solid fa-filter mr-1"></i> Lọc
                    </button>
                    @if(request()->hasAny(['search', 'placement', 'status']))
                        <a href="{{ route('admin.videos.index') }}" class="btn btn-outline-secondary btn-sm" title="Đặt lại bộ lọc">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th width="110">Ảnh Bìa</th>
                        <th width="280">Tiêu đề Video</th>
                        <th width="180">Nước hoa gắn kèm</th>
                        <th width="140">Vị trí</th>
                        <th width="120" class="text-center">Lượt xem</th>
                        <th width="120" class="text-center">Trạng thái</th>
                        <th width="130" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($videos as $video)
                        <tr>
                            <td>
                                <div class="position-relative" style="width: 80px; height: 100px; border-radius: 8px; overflow: hidden; background: #000; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <img src="{{ $video->thumbnail_src }}" alt="{{ $video->title }}" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.88;">
                                    <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.75); color: #fff; font-size: 10px; font-weight: 600; padding: 1px 5px; border-radius: 4px;">
                                        {{ $video->duration ?: '0:45' }}
                                    </span>
                                    <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; font-size: 16px; opacity: 0.9;">
                                        @include('partials.icon', ['name' => 'play', 'size' => '1em'])
                                    </span>
                                </div>
                            </td>
                            <td>
                                <strong style="color: #0f172a; font-size: 0.92rem; display: block; line-height: 1.4;">{{ $video->title }}</strong>
                                @if($video->description)
                                    <p class="text-muted small mb-1 text-truncate" style="max-width: 260px;">{{ $video->description }}</p>
                                @endif
                                <a href="{{ $video->video_url }}" target="_blank" class="small font-weight-bold text-truncate d-inline-block" style="color: #db2777; max-width: 260px;" title="{{ $video->video_url }}">
                                    <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> {{ Str::limit($video->video_url, 35) }}
                                </a>
                            </td>
                            <td>
                                @if($video->perfume)
                                    <a href="{{ route('admin.products.edit', $video->perfume) }}" class="font-weight-bold text-dark small d-block">
                                        {{ $video->perfume->name }}
                                    </a>
                                    <span class="badge badge-light border text-muted" style="font-size: 10.5px;">{{ $video->perfume->brand }}</span>
                                @else
                                    <span class="text-muted small font-italic">Không gắn cụ thể</span>
                                @endif
                            </td>
                            <td>
                                @if($video->placement === 'home')
                                    <span class="badge badge-info" style="font-size: 11px; padding: 4px 8px;">@include('partials.icon', ['name' => 'home', 'size' => '1em']) Trang chủ</span>
                                @elseif($video->placement === 'product')
                                    <span class="badge badge-warning text-dark" style="font-size: 11px; padding: 4px 8px;">@include('partials.icon', ['name' => 'bottle', 'size' => '1em']) Trang SP</span>
                                @else
                                    <span class="badge badge-primary" style="font-size: 11px; padding: 4px 8px; background: #8b5cf6;">@include('partials.icon', ['name' => 'globe', 'size' => '1em']) Toàn sàn</span>
                                @endif
                            </td>
                            <td class="text-center font-weight-bold" style="color: #334155; font-size: 0.9rem;">
                                @include('partials.icon', ['name' => 'flame', 'size' => '1em']) {{ $video->formatted_views }}
                            </td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('admin.videos.toggle', $video) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Bấm để bật/tắt">
                                        @if($video->is_active)
                                            <span class="badge-active cursor-pointer"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> Hiển thị</span>
                                        @else
                                            <span class="badge-inactive cursor-pointer"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> Tạm ẩn</span>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.videos.edit', $video) }}" class="btn btn-outline-warning btn-sm mr-1" title="Chỉnh sửa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form class="d-inline" method="POST" action="{{ route('admin.videos.destroy', $video) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa video này khỏi hệ thống?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Xóa">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-video-slash mb-2" style="font-size: 2.2rem; opacity: 0.35;"></i>
                                <div class="font-weight-bold">Chưa có video nào</div>
                                <div class="small">Bấm "+ Thêm Video Mới" để tải lên hoặc dán link YouTube/Shorts/TikTok review</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($videos->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top flex-wrap">
                <div class="text-muted small">
                    Hiển thị từ {{ $videos->firstItem() }} đến {{ $videos->lastItem() }} trên tổng số {{ $videos->total() }} video
                </div>
                <div>
                    {{ $videos->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
