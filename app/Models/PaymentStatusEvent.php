<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class PaymentStatusEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'payment_id', 'actor_id', 'actor_name', 'from_status', 'to_status',
        'order_status_before', 'order_status_after', 'manual_refund_reference', 'request_fingerprint', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Payment audit events are append-only.'));
        static::deleting(fn () => throw new LogicException('Payment audit events are append-only.'));
    }
}
