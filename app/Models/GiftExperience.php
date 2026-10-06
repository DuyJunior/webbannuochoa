<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftExperience extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['pin_hash', 'photo_path', 'audio_path'];

    protected function casts(): array
    {
        return ['access_version' => 'integer', 'expires_at' => 'datetime', 'opened_at' => 'datetime', 'thanked_at' => 'datetime',
            'message' => 'encrypted', 'thank_you' => 'encrypted'];
    }

    public function perfume(): BelongsTo
    {
        return $this->belongsTo(Perfume::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
