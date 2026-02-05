<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Manages multiple concurrent video stream buffers.
 * Provides the API expected by VisionController.
 */
class StreamBufferManager
{
    /** @var Collection<string, StreamBufferService> */
    private Collection $streams;

    public function __construct()
    {
        $this->streams = new Collection();
    }

    /**
     * Start a new stream buffer
     */
    public function startBuffer(string $streamId, string $source, int $fps = 5): void
    {
        $config = [
            'target_fps' => $fps,
            'source' => $source,
        ];

        $this->streams->put($streamId, new StreamBufferService($streamId, $config));
        Log::info("StreamBufferManager: Started stream {$streamId} from {$source} at {$fps}fps");
    }

    /**
     * Ingest a frame into an active stream
     */
    public function ingestFrame(string $streamId, string $base64Frame, float $timestamp): void
    {
        $buffer = $this->streams->get($streamId);
        
        if (!$buffer) {
            Log::warning("StreamBufferManager: No active stream for {$streamId}");
            return;
        }

        $frame = [
            'frame_number' => (int)($timestamp * 30), // Approximate from timestamp at 30fps
            'base64_image' => $base64Frame,
            'timestamp' => $timestamp,
            'source' => 'api_ingest',
        ];

        $buffer->addFrame($frame, 0, 0); // conversation/agent IDs not used in standalone mode
    }

    /**
     * Check if stream should be analyzed (adaptive sampling)
     */
    public function shouldAnalyze(string $streamId): bool
    {
        $buffer = $this->streams->get($streamId);
        
        if (!$buffer) {
            return false;
        }

        $stats = $buffer->getStats();
        
        // Simple logic: analyze when buffer has enough samples
        // In production, use motion detection and adaptive FPS
        return $stats['buffer_size'] >= 2;
    }

    /**
     * Get temporal context for analysis
     */
    public function getTemporalContext(string $streamId): array
    {
        $buffer = $this->streams->get($streamId);
        
        if (!$buffer) {
            return [];
        }

        return $buffer->getTemporalContext();
    }

    /**
     * Stop stream and return summary
     */
    public function stopBuffer(string $streamId): ?array
    {
        $buffer = $this->streams->get($streamId);
        
        if (!$buffer) {
            return null;
        }

        $stats = $buffer->getStats();
        $buffer->clear();
        $this->streams->forget($streamId);

        Log::info("StreamBufferManager: Stopped stream {$streamId}");

        return [
            'stream_id' => $streamId,
            'frames_processed' => $stats['buffer_size'] ?? 0,
            'duration_seconds' => null, // Could track in stats
        ];
    }

    /**
     * Get active stream IDs
     * @return array<string>
     */
    public function getActiveStreams(): array
    {
        return $this->streams->keys()->toArray();
    }
}
