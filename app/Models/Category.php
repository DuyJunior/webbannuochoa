<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Concerns\HasLocalizedContent;
    use HasFactory;

    protected $fillable = ['name', 'name_en'];

    public function perfumes(): HasMany
    {
        return $this->hasMany(Perfume::class);
    }
}
