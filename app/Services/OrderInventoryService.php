<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Perfume;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderInventoryService
{
    public function reserve(Order $order): void
    {
        $this->change($order, false);
    }

    public function release(Order $order): void
    {
        $this->change($order, true);
    }

    private function change(Order $order, bool $release): void
    {
        DB::transaction(function () use ($order, $release) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($release ? $locked->inventory_status !== 'reserved' : $locked->inventory_status !== 'unreserved') {
                return;
            }
            $demands = [];
            $ids = $locked->items->flatMap(fn ($item) => array_column($item->stock_components ?: [['perfume_id' => $item->perfume_id]], 'perfume_id'));
            $products = Perfume::withTrashed()->whereKey($ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($locked->items as $item) {
                $components = $item->stock_components ?: [['perfume_id' => $item->perfume_id, 'volume_ml' => $item->volume_ml ?: 100]];
                foreach ($components as &$component) {
                    $id = (int) $component['perfume_id'];
                    $volume = (int) $component['volume_ml'];
                    $product = $products->get($id);
                    if (! $product || (! $release && ($product->trashed() || ! $product->is_active))) {
                        throw ValidationException::withMessages(['cart' => 'Sản phẩm không còn khả dụng.']);
                    }
                    // Store the physical bucket at reservation, never infer it again on release.
                    $column = $release ? ($component['stock_column'] ?? null) : null;
                    $column ??= $volume === (int) ($product->volume_ml ?: 100) ? 'stock' : match ($volume) {
                        5 => 'stock_5ml', 10 => 'stock_10ml', 50 => 'stock_50ml',
                        default => throw ValidationException::withMessages(['cart' => 'Dung tích không hợp lệ.']),
                    };
                    if (! in_array($column, ['stock', 'stock_5ml', 'stock_10ml', 'stock_50ml'], true)) {
                        throw ValidationException::withMessages(['cart' => 'Thông tin kho của đơn hàng không hợp lệ.']);
                    }
                    $component['stock_column'] = $column;
                    $component['product_name'] ??= $product->name;
                    $demands[$id][$column] = ($demands[$id][$column] ?? 0) + $item->quantity;
                }
                unset($component);
                $item->update(['stock_components' => $components]);
            }
            ksort($demands); // Consistent lock order reduces deadlocks.
            foreach ($demands as $id => $columns) {
                $product = $products->get($id);
                foreach ($columns as $column => $quantity) {
                    // Null means not yet stocked, not an invented amount of physical inventory.
                    $available = (int) ($product->getRawOriginal($column) ?? 0);
                    if (! $release && $available < $quantity) {
                        throw ValidationException::withMessages(['cart' => "{$product->name}: biến thể đã chọn chỉ còn {$available} sản phẩm."]);
                    }
                    $product->setAttribute($column, $available + ($release ? $quantity : -$quantity));
                    $product->save();
                    DB::table('inventory_movements')->insert([
                        'order_id' => $locked->id, 'perfume_id' => $id,
                        'stock_column' => $column, 'operation' => $release ? 'release' : 'reserve',
                        'quantity_change' => $release ? $quantity : -$quantity,
                        'balance_after' => $product->getAttribute($column), 'created_at' => now(),
                    ]);
                }
            }
            $locked->update(['inventory_status' => $release ? 'released' : 'reserved']);
            $order->setAttribute('inventory_status', $locked->inventory_status);
        });
    }
}
