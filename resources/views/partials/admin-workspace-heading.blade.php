@php
    $workspaces = [
        'admin.products.index' => ['BỘ SƯU TẬP', 'Sản phẩm & hương thơm', 'Chăm chút bộ sưu tập, giá bán và từng dung tích trong kho.', 'spray-can-sparkles', 'admin.products.create', 'Thêm sản phẩm'],
        'admin.orders.index' => ['VẬN HÀNH CỬA HÀNG', 'Mỗi đơn hàng, một trải nghiệm.', 'Theo dõi, xử lý và chăm sóc đơn hàng trong một không gian.', 'bag-shopping', null, null],
        'admin.users.index' => ['KẾT NỐI & CHĂM SÓC', 'Khách hàng & đội ngũ', 'Tìm đúng người, chăm sóc khách hàng và quản lý quyền truy cập.', 'users', 'admin.users.create', 'Thêm tài khoản'],
        'admin.categories.index' => ['SẮP XẾP BỘ SƯU TẬP', 'Danh mục nước hoa', 'Một bộ sưu tập rõ ràng giúp khách tìm thấy mùi hương phù hợp.', 'layer-group', 'admin.categories.create', 'Thêm danh mục'],
        'admin.articles.index' => ['CÂU CHUYỆN THƯƠNG HIỆU', 'Cẩm nang hương thơm', 'Biến kiến thức về nước hoa thành những câu chuyện có giá trị.', 'book-open', null, null],
        'admin.videos.index' => ['NỘI DUNG & CẢM HỨNG', 'Video & Fragrance Shorts', 'Quản lý video, sản phẩm được giới thiệu và vị trí hiển thị.', 'clapperboard', null, null],
        'admin.coupons.index' => ['CHĂM SÓC KHÁCH HÀNG', 'Ưu đãi có chủ đích', 'Tạo và quản lý mã ưu đãi với điều kiện, thời hạn rõ ràng.', 'ticket', null, null],
        'admin.reports.index' => ['HIỆU QUẢ KINH DOANH', 'Nhìn rõ từng kết quả.', 'Theo dõi số liệu, so sánh các giai đoạn và xuất báo cáo.', 'chart-line', null, null],
        'admin.reports.charts' => ['HIỆU QUẢ KINH DOANH', 'Nhịp kinh doanh', 'Đọc xu hướng doanh thu qua những biểu đồ trực quan.', 'chart-simple', null, null],
        'admin.finance.index' => ['TÀI CHÍNH CỬA HÀNG', 'Tổng quan tài chính', 'Giá trị đơn hàng và trạng thái thanh toán trong cùng một góc nhìn.', 'wallet', null, null],
        'admin.finance.transactions' => ['THANH TOÁN & ĐỐI SOÁT', 'Quản lý giao dịch', 'Tra cứu thanh toán và ghi nhận đối soát theo từng đơn hàng.', 'money-bill-transfer', null, null],
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
