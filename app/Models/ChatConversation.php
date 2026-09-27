<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = ['user_id', 'human_mode'];

    protected $casts = ['human_mode' => 'boolean'];
}
