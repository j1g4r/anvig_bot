<?php

declare(strict_types=1);

namespace App\Services\Vision;

use App\Events\StreamFrameAnalysed;
use App\Models\VideoAnalysisSession;
use App\Models\VideoStreamFrame;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Stream Manager Service
 * Manages Redis-backed distributed state for video analysis pipelines.
 */
final readonly class StreamManagerService
{
    public function __construct(
        private OllamaClient $ollama_client,
        private RedisStreamAdapter $stream_adapter,
        private StreamBufferService $stream_buffer,
        private string $session_namespace = 'vision:stream'
    ) {}

    public function startSession(string $stream_hash, int $fps = 30, int $analysis_window = 3): VideoAnalysisSession
    {
        $session = VideoAnalysisSession::create([
            'stream_id'     => str_replace("-", "", substr(sha1(uniqid()), 0, 16)),
            'source_url'    => "internal://stream/{$stream_hash}",
            'fps'           => $fps,
            'status'        => 'processing',
            'started_at'    => now(),
        ]);

        $this->stream_adapter->pipeline($this->sessionKey($session->id))
            ->set('started_at', now()->toIso8601String())
            ->set('fps', (string) $fps)
            ->set('analysis_window', (string) $analysis_window)
            ->set('buffer_size', (string) ($fps * $analysis_window))
            ->execute();

        return $session;
    }

    public function processFrame(VideoAnalysisSession $session, string $frame_data, float $timestamp): ?VideoStreamFrame
    {
        $buffer_full = $this->stream_buffer->accumulate(
            $this->sessionKey($session->id),
            $frame_data,
            $timestamp,
            $session->fps
        );

        $frame = null;

        if ($buffer_full) {
            $bundle = $this->stream_buffer->flush($this->sessionKey($session->id));

            try {
                $result = $this->ollama_client->analyzeBundle(
                    $bundle,
                    $this->getPromptForStream($session->source_url)
                );

                $frame = $this->persistFrame(
                    $session,
                    $bundle['timestamp'],
                    $bundle['representative_frame'],
                    $result['description'] ?? null,
                    $result['confidence'] ?? null,
                    $result['raw_response'] ?? null
                );
            } catch (\Throwable $e) {
                Log::error('Frame analysis failed', [
                    'session' => $session->id,
                    'error'   => $e->getMessage(),
                ]);
                $frame = null;
            }
        }

        return $frame;
    }

    private function persistFrame(
        VideoAnalysisSession $session,
        float $timestamp,
        string $frame_data,
        ?string $description,
        ?float $confidence,
        ?string $raw_response
    ): VideoStreamFrame {
        $frame = VideoStreamFrame::create([
            'session_id'   => $session->id,
            'frame_index'  => (int) ($timestamp * $session->fps),
            'timestamp'    => $timestamp,
            'image_data'   => $frame_data,
            'analysis_result' => $description,
            'confidence'   => $confidence,
            'processed_at' => now(),
            'raw_llm_output' => $raw_response,
            'source_url'   => $session->source_url,
        ]);

        // FIXED: Removed all broadcast() calls to prevent payload errors
        // Events are handled through dedicated event bus

        return $frame;
    }

    public function completeSession(string $session_id): void
    {
        $session = VideoAnalysisSession::findOrFail($session_id);
        $session->status      = 'completed';
        $session->completed_at = now();
        $session->save();

        $this->stream_adapter->pipeline($this->sessionKey($session_id))
            ->set('completed_at', now()->toIso8601String())
            ->expire(3600)
            ->execute();

        $this->stream_buffer->clear($this->sessionKey($session_id));
    }

    public function getAdaptiveSamplingRate(?float $motion_score = null): int
    {
        if (request('high_motion', false)) {
            return (int) round($this->getTargetFps() * 0.8);
        }

        return (int) round($this->getTargetFps() * 0.5);
    }

    private function getTargetFps(): int
    {
        return (int) (Cache::get('vision.stream.fps') ?? 30);
    }

    private function sessionKey(string $id): string
    {
        return "{$this->session_namespace}:{$id}";
    }

    private function getPromptForStream(string $source_url): string
    {
        return config('vision.stream.default_prompt',
            "You are a security camera analysis model.\n"
            . "Describe any activity, objects, or persons visible in these frames.\n"
            . "Be concise.\n"
            . "Respond with JSON: {\"description\": string, \"confidence\": float, \"tags\": []}\n"
        );
    }
}
