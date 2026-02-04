<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ToolExecuted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $traceId,
        public $output,
        public int $durationMs,
        public string $status,
        public array $usage = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('monitoring'),
        ];
    }
}
