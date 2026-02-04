<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('chat.' . $this->message->conversation_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $payload = $this->message->toArray();

        // Truncate content if too large (Pusher limit ~10KB)
        if (isset($payload['content']) && is_string($payload['content']) && strlen($payload['content']) > 8000) {
            $payload['content'] = substr($payload['content'], 0, 8000) . "\n... [Content Truncated due to size]";
        }

        // Truncate tool outputs as well if necessary
        // (Tool calls themselves are usually small, but let's be safe)
        if (isset($payload['tool_calls']) && is_array($payload['tool_calls'])) {
             // If tool calls array is massive, we might need to handle it, but usually content is the offender.
        }

        return $payload;
    }
}
