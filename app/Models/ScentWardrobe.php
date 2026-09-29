<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScentWardrobe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'perfume_id',
        'occasion',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function perfume(): BelongsTo
    {
        return $this->belongsTo(Perfume::class);
    }

    public static function occasionLabels(): array
    {
        return [
            'work' => 'Đi làm & Công sở',
            'date' => 'Hẹn hò & Lãng mạn',
            'party' => 'Tiệc tùng & Sự kiện',
            'casual' => 'Hằng ngày & Dạo phố',
        ];
    }

    public function getOccasionLabelAttribute(): string
    {
        return self::occasionLabels()[$this->occasion] ?? 'Mùi hương yêu thích';
    }

    public function getOccasionIconAttribute(): string
    {
        return match ($this->occasion) {
            'work' => 'briefcase',
            'date' => 'glass',
            'party' => 'crown',
            default => 'leaf',
        };
    }
}
