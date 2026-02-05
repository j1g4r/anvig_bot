<?php

namespace App\Services\Vision;

use Illuminate\Support\Collection;

/** Manages frame buffer with adaptive sampling for video streams */
class StreamBufferService
{
    private array $config = [
        'max_buffer_size' => 60,
        'target_fps' => 5,
        'temporal_window' => 5,
        'keyframe_interval' => 30,
        'motion_threshold' => 0.15,
        'adaptive_fps' => true,
        'min_fps' => 2,
        'max_fps' => 10,
    ];

    private Collection $buffer;
    private string $streamId;
    private int $lastKeyframe = 0;

    public function __construct(string $streamId, ?array $config = null)
    {
        $this->streamId = $streamId;
        $this->buffer = new Collection();

        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /** Add frame, return true if should be sampled for inference */
    public function addFrame(array $frame): bool
    {
        $frameNum = $frame['frame_number'] ?? $this->buffer->count() + 1;
        $motionScore = $frame['motion_score'] ?? 0;

        $isKeyframe = ($frameNum - $this->lastKeyframe) >= $this->config['keyframe_interval'];
        $highMotion = $motionScore > $this->config['motion_threshold'];

        // Adaptive sample rate
        $target = $this->config['adaptive_fps'] 
            ? $this->calculateAdaptiveFps($motionScore)
            : $this->config['target_fps'];

        $framesPerSample = max(1, (int) round(30 / $target));
        $shouldSample = ($frameNum % $framesPerSample === 0);

        if ($isKeyframe || $highMotion) {
            $this->lastKeyframe = $frameNum;
            $shouldSample = true;
        }

        // Add to buffer
        if ($this->buffer->count() >= $this->config['max_buffer_size']) {
            $this->buffer->shift();
        }

        $this->buffer->push([
            'frame_number' => $frameNum,
            'data' => $frame,
            'should_sample' => $shouldSample,
            'timestamp' => $frame['timestamp'] ?? microtime(true),
        ]);

        return $shouldSample;
    }

    private function calculateAdaptiveFps(float $motionScore): int
    {
        $base = $this->config['target_fps'];
        $adjusted = $base * (1 + $motionScore * 2);
        return (int) max($this->config['min_fps'], min($this->config['max_fps'], $adjusted));
    }

    /** Get temporal context from recent frames */
    public function getTemporalContext(): array
    {
        $window = $this->buffer->sortByDesc('timestamp')->take(5)->sortBy('timestamp');

        $context = [];
        foreach ($window as $item) {
            $ctx = [
                'frame_number' => $item['frame_number'],
                'timestamp' => $item['timestamp'],
                'motion_score' => $item['data']['motion_score'] ?? 0,
            ];

            if (!empty($item['data']['inference_result'])) {
                $result = $item['data']['inference_result'];
                $ctx['summary'] = $result['summary'] ?? '';
                $ctx['objects'] = $result['objects'] ?? [];
            }

            $context[] = $ctx;
        }

        return $context;
    }

    /** Mark frame with inference result */
    public function markProcessed(int $frameNumber, array $result): void
    {
        $idx = $this->buffer->search(fn($f) => $f['frame_number'] === $frameNumber);
        if ($idx !== false) {
            $item = $this->buffer->get($idx);
            $item['data']['inference_result'] = $result;
            $this->buffer->put($idx, $item);
        }
    }

    public function getStats(): array
    {
        return [
            'buffer_size' => $this->buffer->count(),
            'stream_id' => $this->streamId,
            'config' => $this->config,
        ];
    }

    public function clear(): void
    {
        $this->buffer = new Collection();
    }
}
