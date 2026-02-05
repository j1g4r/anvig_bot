<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OllamaNode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaClusterService
{
    public function getBestNode(?string $model = null, ?int $userId = null): ?OllamaNode
    {
        $query = OllamaNode::where('status', 'online');
        if ($userId) $query->where('user_id', $userId);
        $nodes = $query->get();
        if ($nodes->isEmpty()) return null;
        if ($model) {
            $withModel = $nodes->filter(fn($n) => $n->hasModel($model));
            if ($withModel->isNotEmpty()) $nodes = $withModel;
        }
        return $nodes->sortBy(fn($n) => [$n->getLoad(), $n->avg_response_time ?? 9999])->first();
    }

    public function chat(string $model, array $messages, ?int $userId = null, bool $stream = false): ?array
    {
        $node = $this->getBestNode($model, $userId);
        if (!$node) {
            Log::error('No available Ollama nodes');
            return null;
        }
        try {
            $node->increment('active_requests');
            $start = microtime(true);
            $response = Http::timeout($stream ? 60 : 120)->post("{$node->url}/api/chat", [
                'model' => $model, 'messages' => $messages, 'stream' => $stream,
                'options' => ['temperature' => 0.3]
            ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error("Ollama chat failed: {$e->getMessage()}");
            return null;
        } finally {
            $node->decrement('active_requests');
        }
    }

    public function chatWithImage(string $model, string $imageBase64, string $prompt, ?int $userId = null): ?array
    {
        // LLaVA uses different format - image path instead of base64 inline
        $node = $this->getBestNode($model, $userId);
        if (!$node) return null;
        try {
            $node->increment('active_requests');
            $url = "{$node->url}/api/generate";
            $response = Http::timeout(60)->post($url, [
                'model' => $model,
                'prompt' => $prompt,
                'images' => [$imageBase64],
                'stream' => false,
                'options' => ['temperature' => 0.3]
            ]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error("LLaVA analysis failed: {$e->getMessage()}");
            return null;
        } finally {
            $node->decrement('active_requests');
        }
    }
}
