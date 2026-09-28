<?php

namespace App\Models;

use App\Services\FragranceProfileService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Perfume extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'brand', 'gender', 'concentration',
        'volume_ml', 'weight', 'price', 'sale_price', 'stock', 'stock_5ml', 'stock_10ml', 'stock_50ml', 'image_url', 'video_url',
        'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:0',
            'sale_price' => 'decimal:0',
            'stock' => 'integer',
            'stock_5ml' => 'integer',
            'stock_10ml' => 'integer',
            'stock_50ml' => 'integer',
            'weight' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getStock10mlAttribute(): int
    {
        if (isset($this->attributes['stock_10ml']) && $this->attributes['stock_10ml'] !== null) {
            return (int) $this->attributes['stock_10ml'];
        }

        return 0;
    }

    public function getStock50mlAttribute(): int
    {
        if (isset($this->attributes['stock_50ml']) && $this->attributes['stock_50ml'] !== null) {
            return (int) $this->attributes['stock_50ml'];
        }

        return 0;
    }

    public function getStock100mlAttribute(): int
    {
        return (int) ($this->attributes['stock'] ?? 0);
    }

    public function getStockForVolume(?int $volume = null): int
    {
        $v = (int) ($volume ?: ($this->volume_ml ?: 100));
        if ($v === (int) ($this->volume_ml ?: 100)) {
            return (int) $this->stock;
        }
        if ($v === 5) {
            return (int) $this->stock_5ml;
        }
        if ($v === 10) {
            return $this->stock_10ml;
        }
        if ($v === 50) {
            return $this->stock_50ml;
        }

        return 0;
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PerfumeReview::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'perfume_id');
    }

    public function getEmbedVideoUrlAttribute(): ?string
    {
        if (! $this->video_url) {
            return null;
        }
        $url = trim((string) $this->video_url);
        if (preg_match('/(?:youtube\.com\/shorts\/|youtu\.be\/shorts\/)([a-zA-Z0-9_-]+)/i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1].'?autoplay=1&rel=0';
        }
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/i', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1].'?autoplay=1&rel=0';
        }
        if (preg_match('/tiktok\.com\/@[^\/]+\/video\/(\d+)/i', $url, $m)) {
            return 'https://www.tiktok.com/player/v1/'.$m[1];
        }

        return $url;
    }

    public function getImageSrcAttribute(): ?string
    {
        if (! $this->image_url) {
            return null;
        }

        return Str::startsWith($this->image_url, ['http://', 'https://'])
            ? $this->image_url
            : asset(ltrim($this->image_url, '/'));
    }

    public function getScentProfileAttribute(): array
    {
        return FragranceProfileService::getProfile($this);
    }

    public function getWeightAttribute(): int
    {
        return (int) ($this->attributes['weight'] ?? 200);
    }

    public function getWeightForVolume(?int $volume = null): int
    {
        $baseWeight = (int) ($this->attributes['weight'] ?? 200);
        if ($baseWeight <= 0) {
            $baseWeight = 200;
        }
        $v = (int) ($volume ?: ($this->volume_ml ?: 100));
        if ($v === 10) {
            return max(50, (int) round($baseWeight * 0.25));
        }
        if ($v === 50) {
            return max(100, (int) round($baseWeight * 0.65));
        }

        return $baseWeight;
    }
}
