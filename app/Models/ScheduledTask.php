<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledTask extends Model
{
    protected $guarded = [];

    protected $casts = [
        'execute_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
