@extends('layouts.store')

@section('title', 'Trắc Nghiệm Mùi Hương · Tìm Dấu Ấn Hương Thơm Của Bạn | Soopi')
@section('meta_description', 'Khám phá mùi hương hoàn hảo dành riêng cho bạn qua bài trắc nghiệm tính cách, thời tiết, dịp dùng và nhóm hương ưa thích.')

@section('content')
<div class="store-container ht-quiz-page">
    <header class="ht-quiz-hero">
        <span class="ht-badge-pill">@include('partials.icon', ['name' => 'flower', 'size' => '1em']) TRẮC NGHIỆM CHỌN HƯƠNG</span>
        <h1 class="ht-quiz-title">Tìm <em>Dấu Ấn Mùi Hương</em> Thuộc Về Riêng Bạn</h1>
        <p class="ht-quiz-subtitle">Chỉ 4 câu hỏi trực giác trong 60 giây, thuật toán mùi hương của Soopi sẽ tìm ra những chai nước hoa hòa hợp nhất với thần thái và tâm hồn bạn.</p>
    </header>

    @if(!$hasResult)
    {{-- Form Quiz Step-by-Step --}}
    <div class="ht-quiz-card">
        <div class="ht-quiz-progress-bar">
            <div class="ht-quiz-progress-fill" id="quizProgress" style="width: 25%"></div>
        </div>
        <div class="ht-quiz-steps-indicator">
            <span class="step-dot active" data-step="1">1. Thần Thái</span>
            <span class="step-dot" data-step="2">2. Thời Tiết</span>
            <span class="step-dot" data-step="3">3. Dịp Dùng</span>
            <span class="step-dot" data-step="4">4. Nốt Hương</span>
        </div>

        <form action="{{ route('store.quiz') }}" method="GET" id="quizForm">
            {{-- Bước 1: Tính cách & Thần thái --}}
            <div class="ht-quiz-step-pane active" id="paneStep1">
                <div class="ht-quiz-step-header">
                    <span class="step-number">CÂU 01 / 04</span>
                    <h2>Bạn muốn người khác cảm nhận thần thái nào nhất ở bạn?</h2>
                </div>
                <div class="ht-quiz-grid">
                    <label class="ht-quiz-option">
                        <input type="radio" name="personality" value="charming" required>
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'flower', 'size' => '1em'])</span>
                            <strong>Quyến Rũ & Bí Ẩn</strong>
                            <p>Cuốn hút, gợi cảm, để lại vương vấn khó quên trong tâm trí người đối diện.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="personality" value="elegant">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'leaf', 'size' => '1em'])</span>
                            <strong>Tinh Tế & Thanh Lịch</strong>
                            <p>Nhẹ nhàng, tao nhã, toát lên phong thái chỉn chu và gu thẩm mỹ đẳng cấp.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="personality" value="fresh">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'citrus', 'size' => '1em'])</span>
                            <strong>Tươi Vui & Năng Động</strong>
                            <p>Sảng khoái, tràn đầy năng lượng tích cực, tự do như làn gió mùa hạ.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="personality" value="warm">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'tree', 'size' => '1em'])</span>
                            <strong>Trầm Ấm & Uy Quyền</strong>
                            <p>Chững chạc, tin cậy, vững vàng và mang chiều sâu của sự từng trải.</p>
                        </div>
                    </label>
                </div>
                <div class="ht-quiz-actions">
                    <span></span>
                    <button type="button" class="ht-button ht-button-primary next-step-btn" data-next="2">Tiếp Tục →</button>
                </div>
            </div>

            {{-- Bước 2: Thời tiết / Môi trường --}}
            <div class="ht-quiz-step-pane" id="paneStep2">
                <div class="ht-quiz-step-header">
                    <span class="step-number">CÂU 02 / 04</span>
                    <h2>Không gian hoặc tiết trời bạn hay xịt nước hoa nhất?</h2>
                </div>
                <div class="ht-quiz-grid">
                    <label class="ht-quiz-option">
                        <input type="radio" name="weather" value="cool" required>
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'snowflake', 'size' => '1em'])</span>
                            <strong>Mát Mẻ & Se Lạnh</strong>
                            <p>Gió mùa thu đông, những ngày mưa bay hoặc buổi tối trời lành lạnh.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="weather" value="hot">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'sun', 'size' => '1em'])</span>
                            <strong>Nắng Ấm & Nhiệt Đới</strong>
                            <p>Thời tiết năng động, cần mùi hương nhẹ mát, không gây nồng gắt.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="weather" value="ac">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'building', 'size' => '1em'])</span>
                            <strong>Phòng Máy Lạnh Suốt Ngày</strong>
                            <p>Môi trường kín, điều hòa 24-26°C, cần mùi hương vừa đủ lan tỏa dễ chịu.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="weather" value="night">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'moon', 'size' => '1em'])</span>
                            <strong>Không Gian Đêm Thoáng Đãng</strong>
                            <p>Những buổi dạo phố, ngắm thành phố về đêm dưới ánh đèn lung linh.</p>
                        </div>
                    </label>
                </div>
                <div class="ht-quiz-actions">
                    <button type="button" class="ht-button ht-button-outline prev-step-btn" data-prev="1">← Quay Lại</button>
                    <button type="button" class="ht-button ht-button-primary next-step-btn" data-next="3">Tiếp Tục →</button>
                </div>
            </div>

            {{-- Bước 3: Dịp dùng & Đối tượng --}}
            <div class="ht-quiz-step-pane" id="paneStep3">
                <div class="ht-quiz-step-header">
                    <span class="step-number">CÂU 03 / 04</span>
                    <h2>Dịp sử dụng quan trọng nhất mà bạn đang tìm kiếm?</h2>
                </div>
                <div class="ht-quiz-grid">
                    <label class="ht-quiz-option">
                        <input type="radio" name="occasion" value="work" required>
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'briefcase', 'size' => '1em'])</span>
                            <strong>Công Sở & Đi Làm Hằng Ngày</strong>
                            <p>Chuyên nghiệp, lịch sự, tôn trọng không gian chung của đồng nghiệp.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="occasion" value="date">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'glass', 'size' => '1em'])</span>
                            <strong>Hẹn Hò & Gặp Gỡ Người Ấy</strong>
                            <p>Ngọt ngào, gần gũi, khiến người bên cạnh chỉ muốn tựa sát vào.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="occasion" value="party">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'crown', 'size' => '1em'])</span>
                            <strong>Dạ Tiệc & Sự Kiện Sang Trọng</strong>
                            <p>Tỏa hương xa, nổi bật giữa đám đông, xứng tầm trang phục lộng lẫy.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="occasion" value="casual">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'beach', 'size' => '1em'])</span>
                            <strong>Dạo Phố & Du Lịch Cuối Tuần</strong>
                            <p>Thư thái, xả stress, mang lại cảm giác giải phóng tâm trí và tự do.</p>
                        </div>
                    </label>
                </div>
                <div class="ht-quiz-actions">
                    <button type="button" class="ht-button ht-button-outline prev-step-btn" data-prev="2">← Quay Lại</button>
                    <button type="button" class="ht-button ht-button-primary next-step-btn" data-next="4">Tiếp Tục →</button>
                </div>
            </div>

            {{-- Bước 4: Nhóm hương ưu tiên & Giới tính --}}
            <div class="ht-quiz-step-pane" id="paneStep4">
                <div class="ht-quiz-step-header">
                    <span class="step-number">CÂU 04 / 04</span>
                    <h2>Nốt hương nào khiến mũi bạn cảm thấy xiêu lòng nhất?</h2>
                </div>
                <div class="ht-quiz-grid">
                    <label class="ht-quiz-option">
                        <input type="radio" name="note" value="floral" required>
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'flower', 'size' => '1em'])</span>
                            <strong>Hương Hoa Tươi Sáng (Floral)</strong>
                            <p>Hoa hồng Damask, hoa nhài Sambac, mẫu đơn, hoa linh lan kiều diễm.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="note" value="woody">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'tree', 'size' => '1em'])</span>
                            <strong>Hương Gỗ Trầm Ấm (Woody)</strong>
                            <p>Tuyết tùng Virginia, đàn hương Mysore, hổ phách và cỏ hương bài sâu lắng.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="note" value="citrus">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'citrus', 'size' => '1em'])</span>
                            <strong>Cam Chanh Thanh Mát (Citrus & Fresh)</strong>
                            <p>Cam Bergamot Calabria, bưởi hồng, chanh vàng và hương biển khoáng đạt.</p>
                        </div>
                    </label>
                    <label class="ht-quiz-option">
                        <input type="radio" name="note" value="sweet">
                        <div class="option-card">
                            <span class="option-icon">@include('partials.icon', ['name' => 'candy', 'size' => '1em'])</span>
                            <strong>Vani & Ngọt Ấm (Gourmand / Amber)</strong>
                            <p>Hạt vani Madagascar, hạnh nhân, caramel và đậu Tonka béo ngậy êm ái.</p>
                        </div>
                    </label>
                </div>

                <div class="ht-quiz-gender-select">
                    <p><strong>Ưu tiên dòng sản phẩm:</strong></p>
                    <div class="gender-radio-group">
                        <label><input type="radio" name="gender" value="" checked> Tất cả (Nam / Nữ / Unisex)</label>
                        <label><input type="radio" name="gender" value="nu"> Dành riêng Nữ</label>
                        <label><input type="radio" name="gender" value="nam"> Dành riêng Nam</label>
                        <label><input type="radio" name="gender" value="unisex"> Unisex Phóng Khoáng</label>
                    </div>
                </div>

                <div class="ht-quiz-actions">
                    <button type="button" class="ht-button ht-button-outline prev-step-btn" data-prev="3">← Quay Lại</button>
                    <button type="submit" class="ht-button ht-button-primary">Khám Phá Mùi Hương Của Bạn →</button>
                </div>
            </div>
        </form>
    </div>
    @else
    {{-- KẾT QUẢ QUIZ --}}
    <div class="ht-quiz-results-wrap">
        <div class="ht-quiz-result-hero">
            <span class="result-celebration">@include('partials.icon', ['name' => 'party', 'size' => '1em']) CHÚC MỪNG BẠN!</span>
            <h2>Soopi Đã Tìm Thấy Mùi Hương Hoàn Hảo Cho Bạn</h2>
            <p class="result-analysis">
                Dựa trên lựa chọn của bạn: phong cách <strong>{{ match($personality) { 'charming' => 'Quyến rũ bí ẩn', 'elegant' => 'Tinh tế thanh lịch', 'fresh' => 'Tươi vui năng động', default => 'Trầm ấm uy quyền' } }}</strong>, 
                thích hợp trong tiết trời <strong>{{ match($weather) { 'cool' => 'mát mẻ se lạnh', 'hot' => 'nắng ấm', 'ac' => 'phòng điều hòa', default => 'buổi tối thoáng đãng' } }}</strong> 
                và dịp <strong>{{ match($occasion) { 'work' => 'công sở', 'date' => 'hẹn hò', 'party' => 'dạ tiệc', default => 'thường ngày' } }}</strong>.
            </p>
            <div class="result-actions-top">
                <a href="{{ route('store.quiz') }}" class="ht-button ht-button-outline">↺ Làm lại trắc nghiệm</a>
                <a href="{{ route('store.discovery-box') }}" class="ht-button ht-button-secondary">@include('partials.icon', ['name' => 'box', 'size' => '1em']) Tạo Hộp Thử Mùi cho các mùi này</a>
            </div>
        </div>

        <div class="ht-quiz-results-grid">
            @foreach($recommendations as $index => $perfume)
            <article class="ht-quiz-item-card {{ $index === 0 ? 'top-match' : '' }}">
                @if($index === 0)
                <div class="top-match-badge">@include('partials.icon', ['name' => 'trophy', 'size' => '1em']) TƯƠNG THÍCH NHẤT DÀNH CHO BẠN</div>
                @endif
                <div class="item-card-inner">
                    <div class="item-card-img">
                        <img src="{{ $perfume->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $perfume->name }}" loading="lazy">
                        <span class="match-score-badge">{{ $perfume->match_score }}% Hòa Hợp</span>
                    </div>
                    <div class="item-card-content">
                        <span class="item-brand">{{ $perfume->brand }}</span>
                        <h3 class="item-title"><a href="{{ route('perfumes.show', $perfume) }}">{{ $perfume->name }}</a></h3>
                        <p class="item-category">{{ $perfume->category->name ?? 'Nước hoa cao cấp' }} · {{ ucfirst($perfume->gender) }}</p>
                        <p class="item-desc">{{ Str::limit(strip_tags($perfume->description), 110) }}</p>
                        
                        <div class="item-notes-preview">
                            <span class="note-pill">Hương đầu: {{ Str::limit($perfume->scent_profile['top']['notes'] ?? 'Tươi mát', 35) }}</span>
                            <span class="note-pill">@include('partials.icon', ['name' => 'leaf', 'size' => '1em']) Độ lưu: {{ $perfume->scent_profile['longevity']['text'] ?? '8h' }}</span>
                        </div>

                        <div class="item-card-bottom">
                            <div class="item-price">
                                <span class="current-price">{{ number_format($perfume->sale_price ?? $perfume->price, 0, ',', '.') }}₫</span>
                                @if($perfume->sale_price)
                                <del class="old-price">{{ number_format($perfume->price, 0, ',', '.') }}₫</del>
                                @endif
                            </div>
                            <div class="item-buttons">
                                <a href="{{ route('perfumes.show', $perfume) }}" class="ht-button ht-button-light">Chi Tiết</a>
                                <form action="{{ route('cart.add', $perfume) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="ht-button ht-button-primary">Chọn Mua</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
    </div>
    @endif
</div>

<style>
.ht-quiz-page {
    padding: 40px 20px 80px;
    max-width: 1040px;
    margin: 0 auto;
}
.ht-quiz-hero {
    text-align: center;
    margin-bottom: 36px;
}
.ht-quiz-title {
    font: 400 clamp(28px, 5vw, 42px) 'Playfair Display', Georgia, serif;
    color: #2b1f26;
    margin: 12px 0 10px;
}
.ht-quiz-title em {
    color: #c2476a;
    font-style: italic;
}
.ht-quiz-subtitle {
    color: #6d5b64;
    font-size: 16px;
    max-width: 680px;
    margin: 0 auto;
    line-height: 1.6;
}
.ht-quiz-card {
    background: #ffffff;
    border: 1px solid #fce7f3;
    border-radius: 24px;
    padding: 36px 32px;
    box-shadow: 0 16px 40px rgba(194, 71, 106, 0.08);
}
.ht-quiz-progress-bar {
    height: 6px;
    background: #f8e8ee;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 20px;
}
.ht-quiz-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f472b6, #c2476a);
    transition: width 0.35s ease;
}
.ht-quiz-steps-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    border-bottom: 1px solid #fdf2f8;
    padding-bottom: 14px;
    font-size: 13px;
    color: #a08894;
}
.ht-quiz-steps-indicator .step-dot.active {
    color: #c2476a;
    font-weight: 700;
}
.ht-quiz-step-pane {
    display: none;
}
.ht-quiz-step-pane.active {
    display: block;
    animation: fadeInStep 0.3s ease;
}
@keyframes fadeInStep {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.ht-quiz-step-header {
    margin-bottom: 24px;
}
.step-number {
    font-size: 11px;
    letter-spacing: 2px;
    font-weight: 700;
    color: #c2476a;
    display: block;
    margin-bottom: 6px;
}
.ht-quiz-step-header h2 {
    font: 600 clamp(20px, 3.5vw, 26px) Georgia, serif;
    color: #33242c;
    margin: 0;
}
.ht-quiz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.ht-quiz-option {
    cursor: pointer;
    position: relative;
}
.ht-quiz-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}
.option-card {
    border: 2px solid #f3e5eb;
    border-radius: 16px;
    padding: 22px 18px;
    background: #fffafa;
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
}
.ht-quiz-option input[type="radio"]:checked + .option-card {
    border-color: #c2476a;
    background: #fff0f5;
    box-shadow: 0 8px 24px rgba(194, 71, 106, 0.16);
    transform: translateY(-3px);
}
.option-icon {
    font-size: 32px;
    margin-bottom: 12px;
    display: block;
}
.option-card strong {
    font-size: 16px;
    color: #33242c;
    margin-bottom: 8px;
    display: block;
}
.option-card p {
    font-size: 13px;
    color: #7d6b74;
    line-height: 1.45;
    margin: 0;
}
.ht-quiz-gender-select {
    background: #faf4f7;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 24px;
}
.ht-quiz-gender-select p {
    margin: 0 0 10px;
    color: #4a3842;
    font-size: 14px;
}
.gender-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    font-size: 14px;
    color: #55444e;
}
.gender-radio-group label {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ht-quiz-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 1px solid #fbf0f4;
}

/* Results layout */
.ht-quiz-result-hero {
    text-align: center;
    background: linear-gradient(135deg, #fff0f5, #ffe4e6);
    border-radius: 24px;
    padding: 36px 24px;
    border: 1px solid #fbcfe8;
    margin-bottom: 40px;
}
.result-celebration {
    display: inline-block;
    background: #c2476a;
    color: #fff;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 12px;
}
.ht-quiz-result-hero h2 {
    font: 600 clamp(24px, 4vw, 32px) Georgia, serif;
    color: #36222c;
    margin: 0 0 10px;
}
.result-analysis {
    color: #664d5a;
    font-size: 15px;
    max-width: 640px;
    margin: 0 auto 20px;
    line-height: 1.6;
}
.result-actions-top {
    display: flex;
    justify-content: center;
    gap: 14px;
    flex-wrap: wrap;
}
.ht-quiz-results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}
.ht-quiz-item-card {
    background: #fff;
    border: 1px solid #fce7f3;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
}
.ht-quiz-item-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(194, 71, 106, 0.12);
}
.ht-quiz-item-card.top-match {
    border: 2px solid #c2476a;
}
.top-match-badge {
    background: linear-gradient(90deg, #c2476a, #e11d48);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    text-align: center;
    padding: 6px;
    letter-spacing: 1px;
}
.item-card-inner {
    padding: 24px;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.item-card-img {
    position: relative;
    text-align: center;
    margin-bottom: 16px;
    background: #fdf2f8;
    border-radius: 14px;
    padding: 16px;
}
.item-card-img img {
    max-height: 180px;
    object-fit: contain;
    transition: transform 0.2s ease;
}
.item-card-img:hover img {
    transform: scale(1.05);
}
.match-score-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #15803d;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(21, 128, 61, 0.25);
}
.item-brand {
    font-size: 12px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #c2476a;
    font-weight: 700;
}
.item-title {
    font: 600 20px Georgia, serif;
    margin: 4px 0 6px;
}
.item-title a {
    color: #33242c;
    text-decoration: none;
}
.item-category {
    font-size: 13px;
    color: #8b7782;
    margin-bottom: 10px;
}
.item-desc {
    font-size: 13.5px;
    color: #55444e;
    line-height: 1.5;
    margin-bottom: 14px;
    flex-grow: 1;
}
.item-notes-preview {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}
.note-pill {
    background: #fdf4f7;
    font-size: 12px;
    color: #705864;
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid #fce7f3;
}
.item-card-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f8e8ee;
    padding-top: 14px;
}
.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #c2476a;
    display: block;
}
.old-price {
    font-size: 12px;
    color: #a8949f;
}
.item-buttons {
    display: flex;
    gap: 8px;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const panes = {
        1: document.getElementById('paneStep1'),
        2: document.getElementById('paneStep2'),
        3: document.getElementById('paneStep3'),
        4: document.getElementById('paneStep4'),
    };
    const progress = document.getElementById('quizProgress');
    const stepDots = document.querySelectorAll('.ht-quiz-steps-indicator .step-dot');

    function goToStep(step) {
        Object.keys(panes).forEach(k => {
            if (panes[k]) panes[k].classList.remove('active');
        });
        if (panes[step]) panes[step].classList.add('active');

        if (progress) progress.style.width = (step * 25) + '%';
        stepDots.forEach(dot => {
            const dStep = parseInt(dot.dataset.step);
            dot.classList.toggle('active', dStep <= step);
        });

        window.scrollTo({ top: document.querySelector('.ht-quiz-card').offsetTop - 60, behavior: 'smooth' });
    }

    document.querySelectorAll('.next-step-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const currentPane = this.closest('.ht-quiz-step-pane');
            const selected = currentPane.querySelector('input[type="radio"]:checked');
            if (!selected) {
                alert('Vui lòng chọn 1 câu trả lời trước khi tiếp tục.');
                return;
            }
            goToStep(parseInt(this.dataset.next));
        });
    });

    document.querySelectorAll('.prev-step-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            goToStep(parseInt(this.dataset.prev));
        });
    });
});
</script>
@endpush
@endsection
