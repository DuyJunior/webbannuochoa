@php
    $workspaces = [
        'admin.products.index' => [__('BỘ SƯU TẬP'), __('Sản phẩm & hương thơm'), __('Chăm chút bộ sưu tập, giá bán và từng dung tích trong kho.'), 'spray-can-sparkles', 'admin.products.create', __('Thêm sản phẩm')],
        'admin.orders.index' => [__('VẬN HÀNH CỬA HÀNG'), __('Mỗi đơn hàng, một trải nghiệm.'), __('Theo dõi, xử lý và chăm sóc đơn hàng trong một không gian.'), 'bag-shopping', null, null],
        'admin.users.index' => [__('KẾT NỐI & CHĂM SÓC'), __('Khách hàng & đội ngũ'), __('Tìm đúng người, chăm sóc khách hàng và quản lý quyền truy cập.'), 'users', 'admin.users.create', __('Thêm tài khoản')],
        'admin.categories.index' => [__('SẮP XẾP BỘ SƯU TẬP'), __('Danh mục nước hoa'), __('Một bộ sưu tập rõ ràng giúp khách tìm thấy mùi hương phù hợp.'), 'layer-group', 'admin.categories.create', __('Thêm danh mục')],
        'admin.articles.index' => [__('CÂU CHUYỆN THƯƠNG HIỆU'), __('Cẩm nang hương thơm'), __('Biến kiến thức về nước hoa thành những câu chuyện có giá trị.'), 'book-open', null, null],
        'admin.videos.index' => [__('NỘI DUNG & CẢM HỨNG'), 'Video & Fragrance Shorts', __('Quản lý video, sản phẩm được giới thiệu và vị trí hiển thị.'), 'clapperboard', null, null],
        'admin.coupons.index' => [__('CHĂM SÓC KHÁCH HÀNG'), __('Ưu đãi có chủ đích'), __('Tạo và quản lý mã ưu đãi với điều kiện, thời hạn rõ ràng.'), 'ticket', null, null],
        'admin.reports.index' => [__('HIỆU QUẢ KINH DOANH'), __('Nhìn rõ từng kết quả.'), __('Theo dõi số liệu, so sánh các giai đoạn và xuất báo cáo.'), 'chart-line', null, null],
        'admin.reports.charts' => [__('HIỆU QUẢ KINH DOANH'), __('Nhịp kinh doanh'), __('Đọc xu hướng doanh thu qua những biểu đồ trực quan.'), 'chart-simple', null, null],
        'admin.finance.index' => [__('TÀI CHÍNH CỬA HÀNG'), __('Tổng quan tài chính'), __('Giá trị đơn hàng và trạng thái thanh toán trong cùng một góc nhìn.'), 'wallet', null, null],
        'admin.finance.transactions' => [__('THANH TOÁN & ĐỐI SOÁT'), __('Quản lý giao dịch'), __('Tra cứu thanh toán và ghi nhận đối soát theo từng đơn hàng.'), 'money-bill-transfer', null, null],
    ];
    $workspace = $workspaces[request()->route()?->getName()] ?? null;
@endphp
@if($workspace)
<section class="studio-workspace-heading" aria-labelledby="workspace-title">
    <div class="studio-workspace-mark" aria-hidden="true"><i class="fa-solid fa-{{ $workspace[3] }}"></i></div>
    <div class="studio-workspace-copy"><span>{{ $workspace[0] }}</span><h2 id="workspace-title">{{ $workspace[1] }}</h2><p>{{ $workspace[2] }}</p></div>
    @if($workspace[4])<a href="{{ route($workspace[4]) }}" class="btn btn-primary studio-workspace-action"><i class="fa-solid fa-plus mr-2" aria-hidden="true"></i>{{ $workspace[5] }}</a>@endif
</section>
@endif
