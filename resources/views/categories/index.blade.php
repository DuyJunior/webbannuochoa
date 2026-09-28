@extends('layouts.store')

@section('title', 'Danh mục nước hoa · Soopi')

@section('content')
    <section class="store-container public-manage-page" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
        {{-- Header --}}
        <div class="public-page-heading manage-heading" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
            <div>
                <a class="back-link" href="{{ route('home') }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--pink-dark, #c94d68); text-decoration: none; margin-bottom: 8px; font-weight: 500;">
                    ← Quay lại cửa hàng
                </a>
                <span class="section-kicker" style="display: block; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--pink-main, #e8728a); margin-bottom: 4px;">
                    🌸 Tuyển tập nhóm hương đặc sắc
                </span>
                <h1 style="font-size: 1.85rem; font-weight: 700; color: #2d1a22; margin: 0 0 6px;">Quản lý danh mục</h1>
                <p style="color: #7a4b5a; font-size: 0.92rem; margin: 0;">Khám phá và quản lý các nhóm hương thơm độc đáo tại Soopi.</p>
            </div>
            <div class="manage-heading-actions" style="display: flex; gap: 10px;">
                <a class="btn" href="{{ route('perfumes.index') }}" style="background: #fff; border: 1.5px solid rgba(232,114,138,.3); color: #c94d68; padding: 10px 18px; border-radius: 999px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all .2s;">
                    Quản lý sản phẩm
                </a>
                <a class="public-primary-button" href="{{ route('categories.create') }}" style="background: linear-gradient(135deg, #c94d68, #e8728a); color: #fff; padding: 10px 22px; border-radius: 999px; font-size: 13px; font-weight: 600; text-decoration: none; box-shadow: 0 4px 14px rgba(232,114,138,.35); display: inline-flex; align-items: center; gap: 6px;">
                    <span>+ Thêm danh mục</span>
                </a>
            </div>
        </div>

        {{-- Category Card Grid --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; margin-bottom: 36px;">
            @php
                $catVisuals = [
                    ['icon' => '🌸', 'bg' => '#fff0f3', 'accent' => '#e8728a', 'tag' => 'Hương Hoa Cỏ', 'desc' => 'Dịu dàng, nữ tính & quyến rũ'],
                    ['icon' => '🌿', 'bg' => '#f0fdf4', 'accent' => '#059669', 'tag' => 'Thảo Mộc & Xanh', 'desc' => 'Tươi mát, thuần khiết & tự nhiên'],
                    ['icon' => '🪵', 'bg' => '#fdf4ea', 'accent' => '#b45309', 'tag' => 'Hương Gỗ Quý', 'desc' => 'Trầm ấm, chững chạc & cuốn hút'],
                    ['icon' => '🌊', 'bg' => '#eff6ff', 'accent' => '#2563eb', 'tag' => 'Hương Biển Sảng Khoái', 'desc' => 'Phóng khoáng, tự do & tươi mới'],
                    ['icon' => '💎', 'bg' => '#fdf2f8', 'accent' => '#db2777', 'tag' => 'Nước Hoa Niche', 'desc' => 'Độc bản, sang trọng & tinh hoa'],
                    ['icon' => '🌙', 'bg' => '#f5f3ff', 'accent' => '#7c3aed', 'tag' => 'Dạ Tiệc & Huyền Bí', 'desc' => 'Say đắm, nồng nàn & bí ẩn'],
                    ['icon' => '🍊', 'bg' => '#fff7ed', 'accent' => '#ea580c', 'tag' => 'Cam Chanh Tươi Mới', 'desc' => 'Năng động, căng tràn sức sống'],
                    ['icon' => '🔥', 'bg' => '#fff1f2', 'accent' => '#e11d48', 'tag' => 'Gia Vị Phương Đông', 'desc' => 'Ấm nồng, đam mê & cá tính'],
                ];
            @endphp

            @forelse ($categories as $index => $category)
                @php
                    $visual = $catVisuals[$index % count($catVisuals)];
                @endphp
                <div style="background: #fff; border-radius: 16px; border: 1.5px solid rgba(232,114,138,.2); padding: 22px; display: flex; flex-direction: column; justify-content: space-between; transition: all .25s ease; box-shadow: 0 4px 16px rgba(201,77,104,.06);"
                     onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(201,77,104,.14)'; this.style.borderColor='#e8728a';"
                     onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 16px rgba(201,77,104,.06)'; this.style.borderColor='rgba(232,114,138,.2)';">
                    
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
                            <div style="width: 46px; height: 46px; border-radius: 12px; background: {{ $visual['bg'] }}; display: flex; align-items: center; justify-content: center; font-size: 22px; border: 1px solid rgba(0,0,0,0.04);">
                                {{ $visual['icon'] }}
                            </div>
                            <span style="font-size: 11px; font-weight: 700; color: #c94d68; background: #fce8ed; padding: 3px 10px; border-radius: 999px;">
                                {{ $category->perfumes_count ?? $category->perfumes()->count() }} sản phẩm
                            </span>
                        </div>

                        <h3 style="font-size: 1.15rem; font-weight: 700; color: #2d1a22; margin: 0 0 6px;">
                            <a href="{{ route('categories.show', $category) }}" style="color: inherit; text-decoration: none;">
                                {{ $category->name }}
                            </a>
                        </h3>
                        <p style="font-size: 0.84rem; color: #8a6573; margin: 0 0 16px; line-height: 1.4;">
                            {{ $visual['desc'] }}
                        </p>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(232,114,138,.12); padding-top: 14px; margin-top: 8px;">
                        <a href="{{ route('home', ['category' => $category->id]) }}#san-pham" style="font-size: 12.5px; font-weight: 600; color: #c94d68; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            <span>Xem sản phẩm</span> →
                        </a>

                        <div style="display: flex; gap: 6px;">
                            <a href="{{ route('categories.edit', $category) }}" title="Sửa danh mục" style="width: 28px; height: 28px; border-radius: 8px; background: #fce8ed; color: #c94d68; display: flex; align-items: center; justify-content: center; font-size: 12px; text-decoration: none;">
                                ✎
                            </a>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa danh mục này? Sản phẩm sẽ chuyển về chưa phân loại.')" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Xóa danh mục" style="width: 28px; height: 28px; border-radius: 8px; background: #fff1f2; color: #e11d48; border: none; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer;">
                                    ✕
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; background: #fff; border-radius: 16px; border: 2px dashed rgba(232,114,138,.3); padding: 48px 20px; text-align: center;">
                    <div style="font-size: 42px; margin-bottom: 12px;">🌸</div>
                    <h3 style="color: #2d1a22; font-weight: 700; margin-bottom: 6px;">Chưa có danh mục nào</h3>
                    <p style="color: #7a4b5a; font-size: 0.9rem; margin-bottom: 20px;">Hãy tạo danh mục đầu tiên để phân loại các mùi hương tuyệt mỹ.</p>
                    <a href="{{ route('categories.create') }}" style="background: linear-gradient(135deg, #c94d68, #e8728a); color: #fff; padding: 11px 24px; border-radius: 999px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block;">
                        + Thêm danh mục ngay
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Detailed Table Section --}}
        <section class="panel public-manage-panel" style="background: #fff; border-radius: 16px; border: 1.5px solid rgba(232,114,138,.2); padding: 24px; box-shadow: 0 4px 16px rgba(201,77,104,.06);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid rgba(232,114,138,.15); padding-bottom: 14px;">
                <strong style="color: #2d1a22; font-size: 1.05rem;">Bảng dữ liệu danh mục chi tiết</strong>
                <span style="font-size: 12px; color: #7a4b5a;">Tổng cộng: {{ $categories->count() }} danh mục</span>
            </div>
            <div class="table-wrap" style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #fff5f7; border-bottom: 2px solid rgba(232,114,138,.2);">
                            <th style="padding: 12px 14px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #c94d68;">Tên danh mục</th>
                            <th style="padding: 12px 14px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #c94d68;">Số lượng sản phẩm</th>
                            <th style="padding: 12px 14px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #c94d68;">Ngày tạo</th>
                            <th class="text-right" style="padding: 12px 14px; text-align: right; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #c94d68;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr style="border-bottom: 1px solid rgba(232,114,138,.1);">
                                <td style="padding: 14px;">
                                    <div class="category-cell" style="display: flex; align-items: center; gap: 12px;">
                                        <span style="width: 36px; height: 36px; border-radius: 10px; background: #fce8ed; color: #c94d68; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                            {{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}
                                        </span>
                                        <div>
                                            <a href="{{ route('categories.show', $category) }}" style="color: #2d1a22; font-weight: 600; font-size: 14px; text-decoration: none;">
                                                {{ $category->name }}
                                            </a>
                                            <small style="display: block; color: #a37887; font-size: 11px;">Mã #{{ str_pad((string) $category->id, 3, '0', STR_PAD_LEFT) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 14px;">
                                    <span style="background: #fff0f3; color: #c94d68; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid rgba(232,114,138,.2);">
                                        {{ $category->perfumes_count ?? $category->perfumes()->count() }} sản phẩm
                                    </span>
                                </td>
                                <td style="padding: 14px;">
                                    <span style="font-size: 13px; color: #2d1a22; font-weight: 500;">{{ $category->created_at?->format('d/m/Y') }}</span>
                                    <small style="display: block; color: #a37887; font-size: 11px;">{{ $category->created_at?->format('H:i') }}</small>
                                </td>
                                <td style="padding: 14px; text-align: right;">
                                    <div style="display: inline-flex; gap: 8px;">
                                        <a href="{{ route('categories.show', $category) }}" style="padding: 4px 10px; border-radius: 6px; background: #fff5f7; color: #c94d68; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid rgba(232,114,138,.2);">
                                            Xem
                                        </a>
                                        <a href="{{ route('categories.edit', $category) }}" style="padding: 4px 10px; border-radius: 6px; background: #fff; color: #7a4b5a; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid rgba(232,114,138,.2);">
                                            Sửa
                                        </a>
                                        <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Bạn chắc chắn muốn xóa danh mục này?')" style="display:inline;margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="padding: 4px 10px; border-radius: 6px; background: #fff1f2; color: #e11d48; font-size: 12px; font-weight: 600; border: 1px solid rgba(225,29,72,.2); cursor: pointer;">
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #8a6573;">Chưa có dữ liệu danh mục.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
