<div class="admin-video-form-page">
    <div class="admin-card">
        <div class="studio-page-intro d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 pb-3 border-bottom">
            <div><h2 class="h5 font-weight-bold mb-1">{{ $video->exists ? 'Thông tin video' : 'Thêm video trải nghiệm' }}</h2><p class="text-muted small mb-0">Liên kết YouTube, Shorts, TikTok hoặc video MP4 với sản phẩm và vị trí hiển thị.</p></div>
            <a href="{{ route('admin.videos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Danh sách video</a>
        </div>

        @if($video->exists && $video->is_youtube && str_starts_with($video->embed_url, 'https://www.youtube.com/embed/'))
            <details class="mb-4 p-3 bg-light rounded border">
                <summary class="font-weight-bold small">Xem video hiện tại</summary>
                <div class="mt-3" style="max-width: 480px; aspect-ratio: 16/9;">
                    <iframe title="Xem trước {{ $video->title }}" src="{{ str_replace('autoplay=1', 'autoplay=0', $video->embed_url) }}" style="width:100%;height:100%;border:0;border-radius:8px;" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>
                </div>
            </details>
        @endif
        <form method="POST" action="{{ $video->exists ? route('admin.videos.update', $video) : route('admin.videos.store') }}" enctype="multipart/form-data">
            @csrf
            @if($video->exists) @method('PUT') @endif
            <div class="form-group">
                <label for="video-title" class="font-weight-bold small">Tiêu đề video <span class="text-danger">*</span></label>
                <input id="video-title" type="text" name="title" maxlength="255" class="form-control @error('title') is-invalid @enderror" placeholder="VD: Miss Dior — Hương hoa hồng đầu mùa" value="{{ old('title', $video->title) }}" required @error('title') aria-invalid="true" aria-describedby="video-title-error" @enderror>
                @error('title')<div id="video-title-error" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="video-url" class="font-weight-bold small">Đường dẫn video <span class="text-danger">*</span></label>
                <input id="video-url" type="text" name="video_url" maxlength="2048" class="form-control @error('video_url') is-invalid @enderror" placeholder="https://www.youtube.com/watch?v=…" value="{{ old('video_url', $video->video_url) }}" required aria-describedby="video-url-hint @error('video_url') video-url-error @enderror" @error('video_url') aria-invalid="true" @enderror>
                @error('video_url')<div id="video-url-error" class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted" id="video-url-hint">Dán liên kết HTTP/HTTPS hoặc đường dẫn tệp như videos/review.mp4.</small>
            </div>
            <div class="form-row">
                <div class="col-md-6 form-group">
                    <label for="video-perfume" class="font-weight-bold small">Sản phẩm liên kết</label>
                    <select id="video-perfume" name="perfume_id" class="form-control @error('perfume_id') is-invalid @enderror" @error('perfume_id') aria-invalid="true" aria-describedby="video-perfume-error" @enderror>
                        <option value="">Không gắn sản phẩm</option>
                        @foreach($perfumes as $perfume)<option value="{{ $perfume->id }}" @selected(old('perfume_id', $video->perfume_id) == $perfume->id)>{{ $perfume->name }} · {{ $perfume->brand }}{{ $perfume->is_active ? '' : ' · Đang ẩn' }}</option>@endforeach
                    </select>
                    @error('perfume_id')<div id="video-perfume-error" class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Video được đồng bộ sang sản phẩm liên kết khi lưu.</small>
                </div>
                <div class="col-md-6 form-group">
                    <label for="video-placement" class="font-weight-bold small">Vị trí hiển thị <span class="text-danger">*</span></label>
                    <select id="video-placement" name="placement" class="form-control @error('placement') is-invalid @enderror" required @error('placement') aria-invalid="true" aria-describedby="video-placement-error" @enderror>
                        <option value="all" @selected(old('placement', $video->placement ?? 'all') === 'all')>Trang chủ và chi tiết sản phẩm</option>
                        <option value="home" @selected(old('placement', $video->placement) === 'home')>Trang chủ · Fragrance Shorts</option>
                        <option value="product" @selected(old('placement', $video->placement) === 'product')>Chi tiết sản phẩm</option>
                    </select>
                    @error('placement')<div id="video-placement-error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-row">
                @foreach([
                    ['duration', 'Thời lượng video', 'text', '0:45', 'VD: 0:45 hoặc 2:30.'],
                    ['views_count', 'Lượt xem hiển thị', 'number', 1200, 'Số lượt xem hiển thị cho khách hàng.'],
                    ['sort_order', 'Thứ tự ưu tiên', 'number', 0, 'Số nhỏ hơn được hiển thị trước.'],
                ] as [$field, $label, $type, $default, $hint])
                    <div class="col-md-4 form-group">
                        <label for="video-{{ $field }}" class="font-weight-bold small">{{ $label }}</label>
                        <input id="video-{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $video->exists ? $video->{$field} : $default) }}" @if($field === 'duration') maxlength="20" @else step="1" @endif @if($field === 'views_count') min="0" @endif aria-describedby="video-{{ $field }}-hint @error($field) video-{{ $field }}-error @enderror" @error($field) aria-invalid="true" @enderror>
                        @error($field)<div id="video-{{ $field }}-error" class="invalid-feedback">{{ $message }}</div>@enderror
                        <small id="video-{{ $field }}-hint" class="form-text text-muted">{{ $hint }}</small>
                    </div>
                @endforeach
            </div>
            <div class="form-row">
                <div class="col-md-6 form-group">
                    <label for="video-thumbnail-file" class="font-weight-bold small">Tải ảnh bìa</label>
                    <input id="video-thumbnail-file" type="file" name="thumbnail_file" class="form-control-file border p-2 rounded w-100 @error('thumbnail_file') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" aria-describedby="video-thumbnail-file-hint @error('thumbnail_file') video-thumbnail-file-error @enderror" @error('thumbnail_file') aria-invalid="true" @enderror>
                    @error('thumbnail_file')<div id="video-thumbnail-file-error" class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <small id="video-thumbnail-file-hint" class="form-text text-muted">JPG, PNG, WEBP · tối đa 5 MB. Ảnh tải lên được ưu tiên hơn đường dẫn ảnh.</small>
                    @if($video->thumbnail_url)<img class="mt-2 rounded" src="{{ $video->thumbnail_src }}" alt="Ảnh bìa hiện tại của {{ $video->title }}" width="64" height="64" style="object-fit:cover;">@endif
                </div>
                <div class="col-md-6 form-group">
                    <label for="video-thumbnail-url" class="font-weight-bold small">Hoặc đường dẫn ảnh bìa</label>
                    <input id="video-thumbnail-url" type="text" name="thumbnail_url" maxlength="2048" class="form-control @error('thumbnail_url') is-invalid @enderror" placeholder="images/products/… hoặc https://…" value="{{ old('thumbnail_url', $video->thumbnail_url) }}" @error('thumbnail_url') aria-invalid="true" aria-describedby="video-thumbnail-url-error" @enderror>
                    @error('thumbnail_url')<div id="video-thumbnail-url-error" class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Để trống để dùng ảnh sản phẩm liên kết hoặc ảnh mặc định.</small>
                </div>
            </div>
            <div class="form-group">
                <label for="video-description" class="font-weight-bold small">Mô tả ngắn / Đánh giá mùi hương</label>
                <textarea id="video-description" name="description" rows="4" maxlength="2000" class="form-control @error('description') is-invalid @enderror" placeholder="Cảm nhận về các nốt hương và trải nghiệm sử dụng…" @error('description') aria-invalid="true" aria-describedby="video-description-error" @enderror>{{ old('description', $video->description) }}</textarea>
                @error('description')<div id="video-description-error" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="custom-control custom-switch my-4">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $video->exists ? $video->is_active : true))>
                <label class="custom-control-label font-weight-bold" for="is_active">Hiển thị trên website sau khi lưu</label>
            </div>
            <div class="studio-form-actions d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('admin.videos.index') }}" class="btn btn-outline-secondary">Hủy bỏ</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> {{ $video->exists ? 'Lưu thay đổi' : 'Thêm video' }}</button>
            </div>
        </form>
    </div>
</div>
