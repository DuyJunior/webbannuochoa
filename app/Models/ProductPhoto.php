<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductPhoto extends Model
{
    public const MAX_PER_PRODUCT = 6;

    protected $fillable = ['path'];

    public function perfume(): BelongsTo
    {
        return $this->belongsTo(Perfume::class)->withTrashed();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
