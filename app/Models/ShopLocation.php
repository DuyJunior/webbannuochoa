<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopLocation extends Model
{
    public $incrementing = false;
    protected $fillable = ['id', 'name', 'address', 'latitude', 'longitude', 'hours', 'is_demo'];
    protected $casts = ['latitude' => 'float', 'longitude' => 'float', 'is_demo' => 'boolean'];
}
