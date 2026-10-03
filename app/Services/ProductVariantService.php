<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductVariantService
{
    /** Called inside the product save transaction, with its parent row locked. */
    public function save(Product $product, array $rows): void
    {
        $existing = $product->variants()->lockForUpdate()->get()->keyBy('id');
        if ($existing->contains('volume_ml', (int) $product->volume_ml)) {
            throw ValidationException::withMessages(['volume_ml' => 'Dung tích gốc trùng với dung tích bổ sung đã lưu. Hãy giữ nguyên dung tích gốc.']);
        }
        $seen = [];
        foreach ($rows as $index => $row) {
            $volume = (int) $row['volume_ml'];
            $id = (int) ($row['id'] ?? 0);
            $variant = $id ? $existing->get($id) : null;
            $error = null;
            if ($id && ! $variant) {
                $error = 'Dung tích này không thuộc sản phẩm đang chỉnh sửa.';
            } elseif ($variant && $variant->volume_ml !== $volume) {
                $error = 'Dung tích đã lưu không được đổi số ml. Hãy thêm dung tích mới và tắt mở bán dòng cũ.';
            } elseif (in_array($volume, [5, 10, 50, (int) $product->volume_ml], true)) {
                $error = 'Dung tích này đã có ở phần kho hiện tại. Hãy nhập một dung tích khác, ví dụ 200 ml.';
            } elseif (isset($seen[$volume]) || $existing->contains(fn ($item) => $item->volume_ml === $volume && $item->id !== $id)) {
                $error = 'Mỗi dung tích chỉ được thêm một lần cho cùng sản phẩm.';
            }
            if ($error) {
                throw ValidationException::withMessages(["variants.$index.volume_ml" => $error]);
            }
            $seen[$volume] = true;
        }
        if ($existing->count() + collect($rows)->filter(fn ($row) => empty($row['id']))->count() > 20) {
            throw ValidationException::withMessages(['variants' => 'Mỗi sản phẩm có tối đa 20 dung tích bổ sung.']);
        }
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            unset($row['id']);
            if ($id) {
                $existing->get($id)->update($row);
            } else {
                $product->variants()->create($row);
            }
        }
        $product->unsetRelation('variants');
    }
}
