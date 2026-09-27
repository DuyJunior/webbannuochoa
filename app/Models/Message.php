<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'is_read',
        'is_ai',
        'reply_to_id',
        'ai_status',
    ];

    protected $casts = ['is_read' => 'boolean', 'is_ai' => 'boolean'];

    public function scopeConversation($query, int $userId)
    {
        return $query->where(function ($pair) use ($userId) {
            $pair->where(function ($outgoing) use ($userId) {
                $outgoing->where('sender_id', $userId)
                    ->whereHas('receiver', fn ($admin) => $admin->where('role', 'admin'));
            })->orWhere(function ($incoming) use ($userId) {
                $incoming->where('receiver_id', $userId)
                    ->whereHas('sender', fn ($admin) => $admin->where('role', 'admin'));
            });
        });
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
