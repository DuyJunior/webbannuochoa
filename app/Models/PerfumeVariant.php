<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfumeVariant extends Model
{
    protected $fillable = ['volume_ml', 'price', 'stock', 'weight', 'is_active'];

    protected function casts(): array
    {
        return ['perfume_id' => 'integer', 'volume_ml' => 'integer', 'price' => 'integer', 'stock' => 'integer', 'weight' => 'integer', 'is_active' => 'boolean'];
    }
}
