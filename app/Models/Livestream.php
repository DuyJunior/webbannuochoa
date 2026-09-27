<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Livestream extends Model
{
    protected $fillable = ['title', 'description', 'youtube_video_id', 'source', 'status', 'starts_at', 'perfume_id', 'pinned_perfume_id', 'created_by', 'presenter_id', 'last_heartbeat_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'last_heartbeat_at' => 'datetime', 'pinned_perfume_id' => 'integer'];
    }

    public function isBrowserOnAir(): bool
    {
        return $this->source === 'browser'
            && $this->status === 'live'
            && $this->last_heartbeat_at?->greaterThan(now()->subSeconds(35));
    }

    public function isOverdue(): bool
    {
        return $this->status === 'scheduled'
            && $this->starts_at
            && $this->starts_at->format('Y-m-d H:i:s') <= now('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s');
    }

    public function perfume(): BelongsTo
    {
        return $this->belongsTo(Perfume::class);
    }

    public function pinnedProduct(): BelongsTo
    {
        return $this->belongsTo(Perfume::class, 'pinned_perfume_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Perfume::class, 'livestream_products')
            ->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEmbedUrlAttribute(): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$this->youtube_video_id;
    }
}
