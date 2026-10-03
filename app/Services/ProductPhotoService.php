<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProductPhotoService
{
    /** Save catalog fields and actual shop photos together, keeping existing photos on failure. */
    public function save(Product $product, array $data, array $uploads = [], array $removeIds = [], array $variants = []): Product
    {
        $storedPaths = [];
        $removedPaths = [];

        try {
            DB::transaction(function () use ($product, $data, $uploads, $removeIds, $variants, &$storedPaths, &$removedPaths) {
                // Serialize gallery updates on the parent, including updates to an empty gallery.
                if ($product->exists) {
                    Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                }

                $photos = $product->exists ? $product->shopPhotos()->get() : collect();
                $removeIds = array_map('intval', $removeIds);
                if (array_diff($removeIds, $photos->pluck('id')->all())) {
                    throw ValidationException::withMessages([
                        'remove_shop_photos' => 'Chỉ có thể xóa ảnh thực tế thuộc sản phẩm đang chỉnh sửa.',
                    ]);
                }

                if ($photos->count() - count($removeIds) + count($uploads) > ProductPhoto::MAX_PER_PRODUCT) {
                    throw ValidationException::withMessages([
                        'shop_photos' => 'Mỗi sản phẩm có tối đa 6 ảnh thực tế. Hãy chọn xóa bớt ảnh trước khi tải thêm.',
                    ]);
                }

                foreach ($uploads as $file) {
                    $path = $file->store('products/photos', 'public');
                    if (! is_string($path) || $path === '') {
                        throw new RuntimeException('Không thể lưu ảnh sản phẩm. Vui lòng thử lại.');
                    }
                    $storedPaths[] = $path;
                }

                $product->fill($data)->save();
                app(ProductVariantService::class)->save($product, $variants);
                $removedPaths = $photos->whereIn('id', $removeIds)->pluck('path')->all();
                $product->shopPhotos()->whereIn('id', $removeIds)->delete();
                foreach ($storedPaths as $path) {
                    $product->shopPhotos()->create(['path' => $path]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        // Only remove files after the database changes have succeeded.
        try {
            if (! Storage::disk('public')->delete($removedPaths)) {
                report(new RuntimeException('Could not clean up removed product photo files.'));
            }
        } catch (Throwable $exception) {
            // The gallery is already committed; do not report a failed save or remove new files.
            report($exception);
        }

        return $product;
    }
}
