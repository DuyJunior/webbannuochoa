@php
    $baseVolume = (int) ($product->volume_ml ?: 100);
    $volumes = collect(array_merge([5], $product->saleVolumes()))->unique()->sort()->values();
    $isDiscounted = $product->sale_price !== null && $product->sale_price < $product->price;
@endphp
<tr class="catalog-row">
    <td class="catalog-identity-cell">
        <div class="catalog-identity">
            <a class="catalog-photo" href="{{ route('admin.products.show', $product) }}" tabindex="-1" aria-hidden="true">
                @if($product->image_url)
                    <img src="{{ $product->image_src }}" alt="" width="72" height="88" loading="lazy">
                @else
                    @include('partials.brand-mark', ['size' => 32])
                @endif
            </a>
            <div class="catalog-description">
                <span class="catalog-brand">{{ $product->brand ?: __('Chưa có thương hiệu') }}</span>
                <a class="catalog-name" href="{{ route('admin.products.show', $product) }}">{{ $product->name }}</a>
                <div class="catalog-specs"><span>{{ $baseVolume }} ml</span><span>{{ ['nam' => 'Nam', 'nu' => __('Nữ'), 'unisex' => 'Unisex'][$product->gender] ?? $product->gender }}</span>@if($product->weight)<span>{{ $product->weight }} g</span>@endif</div>
                <span class="catalog-reference">#{{ str_pad((string) $product->id, 3, '0', STR_PAD_LEFT) }} · {{ optional($product->category)->name ?? __('Chưa phân loại') }}</span>
            </div>
        </div>
    </td>
    <td class="catalog-price-cell">
        <span class="catalog-field-label">{{ __('Giá bán') }}</span>
        <div class="catalog-price"><strong>{{ number_format($product->sale_price ?? $product->price, 0, ',', '.') }}<span>₫</span></strong>
            @if($isDiscounted)<del>{{ number_format($product->price, 0, ',', '.') }}₫</del>@endif
        </div>
    </td>
    <td class="catalog-stock-cell">
        <span class="catalog-field-label">{{ __('Tồn kho') }} <small>{{ __('· số chai') }}</small></span>
        <dl class="catalog-stock" aria-label="{{ __('Tồn kho theo dung tích') }}">
            @foreach($volumes as $volume)
                @php
                    $stock = $product->getStockForVolume($volume);
                    $stockState = $stock <= 0 ? 'empty' : ($stock <= 5 ? 'low' : 'available');
                    $stockLabel = $stock <= 0 ? __('Hết hàng') : ($stock <= 5 ? __('Sắp hết') : __('Còn hàng'));
                @endphp
                <div class="catalog-stock-item {{ $volume === $baseVolume ? 'is-original' : '' }}" data-stock-state="{{ $stockState }}" title="{{ $volume }} ml{{ $volume === $baseVolume ? ' · Chai gốc' : '' }}: {{ $stock }} {{ __('chai') }} · {{ $stockLabel }}">
                    <dt>{{ $volume }}<small>ml</small>@if($volume === $baseVolume)<span class="catalog-original-mark" aria-label="{{ __('Chai gốc') }}">•</span>@endif</dt>
                    <dd>{{ $stock }}<span class="sr-only"> chai · {{ $stockLabel }}</span></dd>
                </div>
            @endforeach
        </dl>
    </td>
    <td class="catalog-status-cell">
        <span class="catalog-field-label">{{ __('Hiển thị') }}</span>
        <span class="catalog-status {{ $product->is_active ? 'is-active' : 'is-hidden' }}"><i aria-hidden="true"></i>{{ $product->is_active ? __('Đang bán') : __('Đã ẩn') }}</span>
    </td>
    <td class="catalog-actions-cell">
        <div class="catalog-actions">
            <a href="{{ route('admin.products.show', $product) }}" class="catalog-action" title="{{ __('Xem chi tiết') }}" aria-label="Xem {{ $product->name }}">@include('partials.icon', ['name' => 'eye', 'size' => 18])</a>
            <a href="{{ route('admin.products.edit', $product) }}" class="catalog-action catalog-action-edit" title="{{ __('Chỉnh sửa') }}" aria-label="Chỉnh sửa {{ $product->name }}">@include('partials.icon', ['name' => 'pen', 'size' => 17])</a>
            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" data-confirm="Xóa {{ $product->name }} khỏi bộ sưu tập? Sản phẩm sẽ ngừng hiển thị và không thể mua mới. Lịch sử đơn hàng được giữ lại.">
                @csrf
                @method('DELETE')
                <button type="submit" class="catalog-action catalog-action-delete" title="{{ __('Xóa sản phẩm') }}" aria-label="Xóa {{ $product->name }}">@include('partials.icon', ['name' => 'trash', 'size' => 17])</button>
            </form>
        </div>
    </td>
</tr>
