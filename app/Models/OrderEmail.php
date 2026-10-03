<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEmail extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'details' => 'array', 'attempts' => 'integer',
            'available_at' => 'datetime', 'queued_at' => 'datetime', 'processing_at' => 'datetime',
            'sent_at' => 'datetime', 'skipped_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusAttribute(): string
    {
        return $this->sent_at ? 'sent' : ($this->skipped_at ? 'skipped' : 'pending');
    }
}
