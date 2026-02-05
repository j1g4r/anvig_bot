<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Vision analysis using Ollama vision models */
class VisionService
{
    public const VISION_MODELS = [
        'llava:latest',
        'llava-llama3:latest',
        'bakllava:latest',
        'moondream:latest',
        'llava-phi3:latest',
    ];
    public const DEFAULT_MODEL = 'llava:latest';

    public function __construct(private OllamaClusterService $cluster) {}

    public function analyzeImage(string $imageData, string $prompt = 'describe this image', ?string $model = null, ?int $userId = null): ?array
    {
        $model = $model ?? self::DEFAULT_MODEL;
        
        $node = $this->cluster->getBestNode($model, $userId);
        if (!$node) {
            Log::error('Vision: no available node for model', ['model' => $model]);
            return null;
        }

        try {
            $base64 = $this->normalizeImage($imageData);
            if (!$base64) return null;

            $start = microtime(true);
            $node->increment('active_requests');

            $response = Http::timeout(60)->post("{$node->url}/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'images' => [$base64],
                'stream' => false,
            ]);

            $node->decrement('active_requests');
            $duration = round((microtime(true) - $start) * 1000, 2);

            if (!$response->successful()) {
                Log::error('Vision API failed', ['status' => $response->status()]);
                return null;
            }

            $result = $response->json();
            $content = $result['response'] ?? null;

            // Update node metrics
            $node->update(['avg_response_time' => $node->avg_response_time ? 
                ($node->avg_response_time * 0.8 + $duration * 0.2) : $duration]);

            return [
                'result' => $content,
                'model' => $model,
                'node' => $node->name,
                'duration_ms' => $duration,
                'done' => $result['done'] ?? false,
            ];

        } catch (\Exception $e) {
            Log::error('Vision analysis failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function normalizeImage(string $data): ?string
    {
        // Already base64
        if (preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $data) && strlen($data) > 100) {
            return $data;
        }

        // URL - fetch
        if (str_starts_with($data, 'http')) {
            try {
                return base64_encode(Http::timeout(10)->get($data)->body());
            } catch (\Exception $e) {
                return null;
            }
        }

        // File path
        if (file_exists($data)) {
            return base64_encode(file_get_contents($data));
        }

        return null;
    }

    public function isAvailable(?string $model = null, ?int $userId = null): bool
    {
        $models = $model ? [$model] : self::VISION_MODELS;
        foreach ($models as $m) {
            if ($this->cluster->getBestNode($m, $userId)) return true;
        }
        return false;
    }
}
