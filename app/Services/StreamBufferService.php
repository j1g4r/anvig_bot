<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VideoAnalysisSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class StreamBufferService
{
    private const STREAM_PREFIX = 'stream_buffer:';
    private const DEFAULT_FPS = 5;
    private const DEFAULT_WINDOW = 30;
    private const MAX_BUFFER_SIZE = 150;
    private const MIN_ANALYSIS_INTERVAL = 2;

    public function startBuffer(string $streamId, int $fps = self::DEFAULT_FPS, int $windowSeconds = self::DEFAULT_WINDOW): array
    {
        $sessionId = $this->generateSessionId();
        $config = [
            'session_id' => $sessionId,
            'stream_id' => $streamId,
            'fps' => $fps,
            'window_seconds' => $windowSeconds,
            'max_frames' => min($fps * $windowSeconds, self::MAX_BUFFER_SIZE),
            'created_at' => now()->toIso8601String(),
            'status' => 'active',
            'frame_count' => 0,
            'last_analysis_at' => null,
        ];
        Cache::put(self::STREAM_PREFIX.$streamId, $config, now()->addHours(2));
        return ['session_id' => $sessionId, 'config' => $config];
    }

    public function ingestFrame(string $streamId, string $base64Frame, float $timestamp): array
    {
        $bufferKey = self::STREAM_PREFIX.$streamId;
        $config = Cache::get($bufferKey);
        if (!$config || $config['status'] !== 'active') {
            $this->startBuffer($streamId);
            $config = Cache::get($bufferKey);
        }
        $frameKey = $bufferKey.':frames';
        $frames = Cache::get($frameKey, []);
        if (count($frames) >= $config['max_frames']) {
            array_shift($frames);
        }
        $compactFrame = [
            'timestamp' => $timestamp,
            'hash' => hash('xxh3', $base64Frame),
            'size' => strlen($base64Frame),
        ];
        $frames[] = $compactFrame;
        $this->persistFrame($config['session_id'], $timestamp, $base64Frame);
        $config['frame_count'] = count($frames);
        $config['last_frame_hash'] = $compactFrame['hash'];
        $config['last_timestamp'] = $timestamp;
        Cache::put($frameKey, $frames, now()->addHours(2));
        Cache::put($bufferKey, $config, now()->addHours(2));
        return [
            'frame_hash' => $compactFrame['hash'],
            'buffer_size' => count($frames),
            'should_analyze' => $this->shouldAnalyze($config, $timestamp),
        ];
    }

    private function shouldAnalyze(array $config, float $currentTime): bool
    {
        $lastAnalysis = $config['last_analysis_at'] ?? 0;
        return ($currentTime - $lastAnalysis) >= self::MIN_ANALYSIS_INTERVAL;
    }

    public function markAnalyzed(string $streamId, float $timestamp, array $result): void
    {
        $bufferKey = self::STREAM_PREFIX.$streamId;
        $config = Cache::get($bufferKey, []);
        $config['last_analysis_at'] = $timestamp;
        $config['last_result'] = $result;
        Cache::put($bufferKey, $config, now()->addHours(2));
    }

    private function persistFrame(string $sessionId, float $timestamp, string $base64Frame): void
    {
        $path = "vision-streams/{$sessionId}/".floor($timestamp * 1000).'.txt';
        Storage::disk('local')->put($path, $base64Frame);
    }

    private function generateSessionId(): string
    {
        return 'sess_'.bin2hex(random_bytes(8));
    }

    public function getBufferState(string $streamId): ?array
    {
        return Cache::get(self::STREAM_PREFIX.$streamId);
    }

    public function stopBuffer(string $streamId): array
    {
        $bufferKey = self::STREAM_PREFIX.$streamId;
        $config = Cache::get($bufferKey);
        if ($config) {
            $config['status'] = 'ended';
            $config['ended_at'] = now()->toIso8601String();
            VideoAnalysisSession::create([
                'id' => $config['session_id'],
                'source' => $config['stream_id'],
                'fps' => $config['fps'],
                'status' => 'completed',
                'started_at' => $config['created_at'],
                'ended_at' => now(),
            ]);
        }
        Cache::forget($bufferKey);
        Cache::forget($bufferKey.':frames');
        return ['status' => 'stopped', 'session_id' => $config['session_id'] ?? null];
    }

    public function getFrameByHash(string $sessionId, string $hash): ?string
    {
        $dir = "vision-streams/{$sessionId}";
        if (!Storage::disk('local')->exists($dir)) {
            return null;
        }
        $files = Storage::disk('local')->files($dir);
        foreach ($files as $file) {
            $content = Storage::disk('local')->get($file);
            if (hash('xxh3', $content) === $hash) {
                return $content;
            }
        }
        return null;
    }
}
