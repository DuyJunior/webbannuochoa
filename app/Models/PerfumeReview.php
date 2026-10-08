<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfumeReview extends Model
{
    public const TAGS = ['packaging' => 'Đóng gói đẹp', 'description' => 'Đúng mô tả', 'delivery' => 'Giao nhanh', 'scent' => 'Mùi hương yêu thích'];

    protected $fillable = ['user_id', 'perfume_id', 'order_item_id', 'rating', 'body', 'image_path', 'images', 'tags', 'seller_reply', 'replied_at'];

    protected function casts(): array
    {
        return ['images' => 'array', 'tags' => 'array', 'replied_at' => 'datetime'];
    }

    public function getPhotosAttribute(): array
    {
        return $this->images ?: ($this->image_path ? [$this->image_path] : []);
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function perfume(): BelongsTo { return $this->belongsTo(Perfume::class); }
}
