<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\PerfumeVariant;
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
            $variants = PerfumeVariant::whereIn('perfume_id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($products as $product) {
                $product->setRelation('variants', $variants->where('perfume_id', $product->id)->values());
            }
            $volumes = [];
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
                    $column ??= $product->stockBucketForVolume($volume);
                    $variant = is_string($column) && preg_match('/^variant:([1-9][0-9]*)$/', $column, $match)
                        ? $variants->get((int) $match[1]) : null;
                    $validVariant = $variant && $variant->perfume_id === $id && ($release || ($variant->is_active && $variant->volume_ml === $volume));
                    if (! $validVariant && ! in_array($column, ['stock', 'stock_5ml', 'stock_10ml', 'stock_50ml'], true)) {
                        throw ValidationException::withMessages(['cart' => 'Thông tin kho của đơn hàng không hợp lệ.']);
                    }
                    $volumes[$id][$column] = $volume;
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
                    $variant = str_starts_with($column, 'variant:') ? $variants->get((int) substr($column, 8)) : null;
                    $available = $variant ? $variant->stock : (int) ($product->getRawOriginal($column) ?? 0);
                    if (! $release && $available < $quantity) {
                        throw ValidationException::withMessages(['cart' => "{$product->name}: biến thể đã chọn chỉ còn {$available} sản phẩm."]);
                    }
                    $balance = $available + ($release ? $quantity : -$quantity);
                    if ($variant) {
                        $variant->update(['stock' => $balance]);
                    } else {
                        $product->setAttribute($column, $balance);
                        $product->save();
                    }
                    DB::table('inventory_movements')->insert([
                        'order_id' => $locked->id, 'perfume_id' => $id,
                        'stock_column' => $column, 'operation' => $release ? 'release' : 'reserve',
                        'quantity_change' => $release ? $quantity : -$quantity,
                        'balance_after' => $balance, 'volume_ml' => $volumes[$id][$column], 'created_at' => now(),
                    ]);
                }
            }
            $locked->update(['inventory_status' => $release ? 'released' : 'reserved']);
            $order->setAttribute('inventory_status', $locked->inventory_status);
        });
    }
}
