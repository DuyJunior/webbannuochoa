<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Concerns\HasLocalizedContent;

    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'title_en', 'excerpt_en', 'body_en', 'image_url', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
