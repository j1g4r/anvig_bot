<?php

declare(strict_types=1);

namespace App\Events\Vision;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stream frame analysis complete event.
 * 
 * Broadcasts when a vision analysis completes for real-time client updates.
 */
class StreamFrameAnalysed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $streamId;
    public int $frameNumber;
    public bool $success;
    public string $description;
    public array $objects;
    public string $model;

    public function __construct(string $streamId, int $frameNumber, array $result)
    {
        $this->streamId = $streamId;
        $this->frameNumber = $frameNumber;
        $this->success = $result['success'] ?? false;
        $this->model = $result['model'] ?? 'unknown';
        
        // Truncate for Pusher 10KB limit
        $this->description = mb_substr($result['description'] ?? '', 0, 400);
        $this->objects = array_slice($result['objects_detected'] ?? [], 0, 8);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('vision.streams.' . $this->streamId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'frame.analysed';
    }
}