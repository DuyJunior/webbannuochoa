<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'perfume_id',
        'title',
        'video_url',
        'thumbnail_url',
        'duration',
        'views_count',
        'description',
        'placement',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'views_count' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function perfume(): BelongsTo
    {
        return $this->belongsTo(Perfume::class, 'perfume_id');
    }

    public function getThumbnailSrcAttribute(): string
    {
        if ($this->thumbnail_url) {
            return Str::startsWith($this->thumbnail_url, ['http://', 'https://'])
                ? $this->thumbnail_url
                : asset(ltrim($this->thumbnail_url, '/'));
        }

        if ($this->perfume && $this->perfume->image_src) {
            return $this->perfume->image_src;
        }

        return asset('images/perfume-default.jpg');
    }

    public function getFormattedViewsAttribute(): string
    {
        $views = (int) $this->views_count;
        if ($views >= 1000000) {
            return round($views / 1000000, 1) . 'M';
        }
        if ($views >= 1000) {
            return round($views / 1000, 1) . 'K';
        }
        return (string) $views;
    }

    /**
     * Chuyển đổi link YouTube (watch, youtu.be, shorts) sang Embed URL chuẩn
     */
    public function getEmbedUrlAttribute(): string
    {
        return \App\Support\VideoUrl::embed($this->video_url) ?? '';
    }

    public function getIsYoutubeAttribute(): bool
    {
        return Str::contains($this->video_url, ['youtube.com', 'youtu.be']);
    }

    public function getIsTiktokAttribute(): bool
    {
        return Str::contains($this->video_url, 'tiktok.com');
    }

    public function getIsDirectVideoAttribute(): bool
    {
        return preg_match('/\.(mp4|webm|ogg)$/i', $this->video_url) === 1;
    }
}
