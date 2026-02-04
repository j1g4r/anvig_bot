<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_multi_agent' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function canvas()
    {
        return $this->hasOne(Canvas::class);
    }

    /**
     * Get all participant agents in this conversation.
     */
    public function participants()
    {
        return $this->hasMany(ChatParticipant::class)->orderBy('order');
    }

    /**
     * Get all agents participating in this conversation.
     */
    public function agents()
    {
        return $this->belongsToMany(Agent::class, 'chat_participants')
            ->withPivot('color', 'is_primary', 'order')
            ->orderBy('chat_participants.order');
    }

    /**
     * Add an agent as a participant.
     */
    public function addParticipant(Agent $agent, bool $isPrimary = false): ChatParticipant
    {
        $count = $this->participants()->count();
        return ChatParticipant::create([
            'conversation_id' => $this->id,
            'agent_id' => $agent->id,
            'color' => ChatParticipant::assignColor($count),
            'is_primary' => $isPrimary,
            'order' => $count,
        ]);
    }

    /**
     * Remove an agent participant.
     */
    public function removeParticipant(Agent $agent): void
    {
        $this->participants()->where('agent_id', $agent->id)->delete();
    }
}
