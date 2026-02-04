<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tools_config' => 'array',
    ];

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
