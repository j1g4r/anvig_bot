<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ToolExecuting implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $traceId,
        public int $conversationId,
        public int $agentId,
        public string $toolName,
        public array $input
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('monitoring'),
        ];
    }
}
