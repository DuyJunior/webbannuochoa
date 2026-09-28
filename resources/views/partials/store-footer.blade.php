<footer class="ht-footer">
    <div class="store-container ht-footer-grid">
        <div class="ht-footer-about">
            <a class="ht-brand" href="{{ route('home') }}"><span class="ht-brand-name">Soopi<span>PERFUME STUDIO</span></span></a>
            <p>Hương thơm là cách dịu dàng nhất để kể câu chuyện của riêng bạn.</p>
            <span class="ht-footer-signature">Chọn hương. Chọn chính mình.</span>
            {{-- Social links --}}
            <div class="ht-footer-social">
                <a href="https://zalo.me/0123456789" target="_blank" rel="noopener" aria-label="Zalo Soopi" class="ht-footer-social-btn ht-social-zalo">
                    <svg width="16" height="16" viewBox="0 0 40 40" fill="none"><text x="4" y="28" font-size="22" font-family="Arial" font-weight="bold" fill="currentColor">Z</text></svg>
                    Zalo
                </a>
                <a href="https://facebook.com/hathu.perfume" target="_blank" rel="noopener" aria-label="Facebook Soopi" class="ht-footer-social-btn ht-social-fb">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </a>
                <a href="https://instagram.com/hathu.perfume" target="_blank" rel="noopener" aria-label="Instagram Soopi" class="ht-footer-social-btn ht-social-ig">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    Instagram
                </a>
            </div>
        </div>
        <div><h2>Khám phá</h2><a href="{{ route('home', ['gender' => 'nu']) }}#san-pham">Nước hoa nữ</a><a href="{{ route('home', ['gender' => 'nam']) }}#san-pham">Nước hoa nam</a><a href="{{ route('home', ['gender' => 'unisex']) }}#san-pham">Nước hoa unisex</a><a href="{{ route('home', ['sort' => 'sale']) }}#san-pham">Ưu đãi hiện có</a><a href="{{ route('store.faq') }}">Câu hỏi thường gặp</a></div>
        <div><h2>Dành cho bạn</h2><a href="{{ route('orders.tracking') }}">Theo dõi đơn hàng</a><a href="{{ route('orders.index') }}">Lịch sử mua hàng</a><a href="{{ route('cart.index') }}">Giỏ hàng của bạn</a><a href="{{ route('register') }}">Trở thành thành viên</a><a href="{{ route('store.wishlist') }}">Yêu thích</a><a href="{{ route('store.member') }}">Điểm thành viên</a><a href="{{ route('store.journal') }}">Cẩm nang mùi hương</a></div>
        <div class="ht-footer-help"><span class="ht-eyebrow">MỘT CHÚT THẤU HIỂU</span><h2>Tìm hương thơm<br>hợp với bạn.</h2><p>Bắt đầu từ thương hiệu bạn yêu, hay một mùi hương bạn muốn khám phá.</p><a class="ht-text-link" href="{{ route('store.finder') }}">Tìm hương phù hợp @include('partials.icon', ['name' => 'arrow', 'size' => 18])</a></div>
    </div>
    <div class="store-container ht-footer-bottom"><span>© {{ date('Y') }} Soopi.</span><span>Được chăm chút, từ hương thơm đến trải nghiệm.</span><a href="#main-content">Về đầu trang ↑</a></div>
</footer>

<style>
.ht-footer-social {
    display: flex;
    gap: 8px;
    margin-top: 18px;
    flex-wrap: wrap;
}
.ht-footer-social-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 700;
    text-decoration: none;
    transition: transform .18s, opacity .18s;
    border: 1.5px solid transparent;
}
.ht-footer-social-btn:hover { transform: translateY(-2px); opacity: .88; }
.ht-social-zalo  { background: #0068FF; color: #fff; }
.ht-social-fb    { background: #1877F2; color: #fff; }
.ht-social-ig    { background: linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); color: #fff; }
</style>