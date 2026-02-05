<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Vision 2.0: Real-time Stream Manager
 * Orchestrates multiple StreamBufferService instances for concurrent streams.
 */
class StreamManagerService
{
    /** @var Collection<string, StreamBufferService> */
    private Collection $streams;

    public function __construct()
    {
        $this->streams = new Collection();
    }

    /**
     * Start a new video stream buffer
     */
    public function startBuffer(string $streamId, string $source, int $fps = 5): StreamBufferService
    {
        $this->streams[$streamId] = new StreamBufferService($streamId, [
            'target_fps' => $fps,
            'max_buffer_size' => 60,
        ]);

        Log::info("StreamManager: Started stream {$streamId} from source {$source} at {$fps} FPS");
        return $this->streams[$streamId];
    }

    /**
     * Ingest a frame into the stream buffer
     */
    public function ingestFrame(string $streamId, string $base64Frame, float $timestamp): void
    {
        $buffer = $this->streams->get($streamId);
        if (!$buffer) {
            Log::warning("StreamManager: Attempted to ingest frame to unknown stream {$streamId}");
            return;
        }

        // Create frame data structure for StreamBufferService
        $frame = [
            'base64_image' => $base64Frame,
            'frame_number' => (int) ($timestamp * 30), // Assume 30fps source
            'timestamp' => $timestamp,
            'source' => 'stream',
            'metadata' => [
                'ingest_timestamp' => microtime(true),
            ],
        ];

        // Add frame with dummy conversation/agent IDs (placeholder)
        $buffer->addFrame($frame, 0, 0);
    }

    /**
     * Check if stream should analyze current state
     */
    public function shouldAnalyze(string $streamId): bool
    {
        $buffer = $this->streams->get($streamId);
        if (!$buffer) {
            return false;
        }

        // Trigger analysis every N frames based on target_fps
        $stats = $buffer->getStats();
        $frameCount = $stats['buffer_size'] ?? 0;
        $targetFps = $stats['target_fps'] ?? 5;

        // Analyze on first frame and every (30/target_fps) frames
        if ($frameCount === 1) {
            return true;
        }

        $interval = (int) round(30 / max(1, $targetFps));
        return $frameCount % max(2, $interval) === 0;
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
     * Stop a stream and get summary
     */
    public function stopBuffer(string $streamId): ?array
    {
        $buffer = $this->streams->get($streamId);
        if (!$buffer) {
            return null;
        }

        $stats = $buffer->getStats();
        $context = $buffer->getTemporalContext();

        $buffer->clear();
        $this->streams->forget($streamId);

        Log::info("StreamManager: Stopped stream {$streamId}");

        return [
            'stream_id' => $streamId,
            'total_frames' => $stats['buffer_size'],
            'target_fps' => $stats['target_fps'],
            'dominant_classes' => $context['dominant_classes'] ?? [],
            'avg_motion' => $context['avg_motion'] ?? 0,
        ];
    }

    /**
     * List active streams
     */
    public function listStreams(): array
    {
        return $this->streams->keys()->toArray();
    }

    /**
     * Get stream statistics
     */
    public function getStreamStats(string $streamId): ?array
    {
        $buffer = $this->streams->get($streamId);
        return $buffer?->getStats();
    }
}
