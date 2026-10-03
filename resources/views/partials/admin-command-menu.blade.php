<dialog id="studio-command-menu" class="studio-command-menu" aria-labelledby="studio-command-title">
    <div class="studio-command-head"><div><span class="studio-eyebrow">SOOPI STUDIO</span><h2 id="studio-command-title">{{ __('Bạn muốn làm gì?') }}</h2></div><button type="button" data-command-close aria-label="{{ __('Đóng tìm chức năng') }}"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
    <label class="studio-command-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" id="studio-command-input" placeholder="{{ __('Tìm đơn hàng, sản phẩm, livestream…') }}" aria-label="{{ __('Tìm chức năng quản trị') }}" autocomplete="off"></label>
    <nav id="studio-command-results" aria-label="{{ __('Kết quả tìm chức năng') }}"></nav>
    <p id="studio-command-empty" hidden>{{ __('Không tìm thấy chức năng phù hợp. Thử một từ khóa khác nhé.') }}</p>
    <footer><span>{{ __('Chỉ hiển thị chức năng trong quyền truy cập của bạn.') }}</span><kbd>Esc</kbd> {{ __('Đóng') }}</footer>
</dialog>
