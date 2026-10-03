@if(isset($inventoryMovements))
<section class="card mb-4">
    <div class="card-header"><h2 class="h5 mb-0">{{ __('Nhật ký kho của đơn hàng') }}</h2></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>{{ __('Thời gian') }}</th><th>{{ __('Mã sản phẩm') }}</th><th>{{ __('Ngăn kho') }}</th><th>{{ __('Thao tác') }}</th><th>{{ __('Thay đổi') }}</th><th>{{ __('Tồn sau') }}</th></tr></thead>
            <tbody>
                @forelse($inventoryMovements as $movement)
                    <tr><td>{{ $movement->created_at }}</td><td>#{{ $movement->perfume_id }}</td>
                    <td>{{ !empty($movement->volume_ml) ? $movement->volume_ml.' ml' : (['stock'=>__('Chai gốc'),'stock_5ml'=>__('Mẫu 5ml'),'stock_10ml'=>__('Chiết 10ml'),'stock_50ml'=>'Chai 50ml'][$movement->stock_column] ?? $movement->stock_column) }}</td>
                    <td>{{ $movement->operation === 'reserve' ? __('Giữ hàng cho đơn') : __('Hoàn kho') }}</td>
                    <td>{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td>{{ $movement->balance_after }}</td></tr>
                @empty
                    <tr><td colspan="6">{{ __('Chưa có chứng từ kho ghi nhận bởi phiên bản mới. Không tự tạo lịch sử cho dữ liệu cũ.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
