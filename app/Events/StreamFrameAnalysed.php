<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamFrameAnalysed implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $broadcastQueue = 'events';

    public function __construct(
        public readonly string $sessionId,
        public readonly string $frameId,
        public readonly array $result,
        public readonly ?float $processingTime = null,
        public readonly string $timestamp = ''
    ) {
        $this->timestamp = $timestamp ?: now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vision.stream.' . $this->sessionId),
            new PrivateChannel('user.vision.updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stream.frame.analysed';
    }

    /**
     * Compact payload to stay under 10KB Pusher limit
     */
    public function broadcastWith(): array
    {
        $desc = $this->result['description'] ?? '';
        if (strlen($desc) > 500) {
            $desc = substr($desc, 0, 497) . '...';
        }

        return [
            'session_id' => $this->sessionId,
            'frame_id' => $this->frameId,
            'timestamp' => $this->timestamp,
            'processing_time' => $this->processingTime,
            'result' => [
                'description' => $desc,
                'confidence' => $this->result['structured']['confidence'] ?? null,
                'tags' => $this->result['structured']['tags'] ?? [],
            ],
        ];
    }
}
