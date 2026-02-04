<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tool_calls' => 'array',
        'images' => 'array',
    ];

    protected $dispatchesEvents = [
        'created' => \App\Events\MessageCreated::class,
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
