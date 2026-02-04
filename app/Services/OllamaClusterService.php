<?php

namespace App\Services;

use App\Models\OllamaNode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaClusterService
{
    /**
     * Get the best available node for a model.
     */
    public function getBestNode(?string $model = null, ?int $userId = null): ?OllamaNode
    {
        $query = OllamaNode::where('status', 'online');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $nodes = $query->get();

        if ($nodes->isEmpty()) {
            return null;
        }

        // Filter by model if specified
        if ($model) {
            $nodesWithModel = $nodes->filter(fn($n) => $n->hasModel($model));
            if ($nodesWithModel->isNotEmpty()) {
                $nodes = $nodesWithModel;
            }
        }

        // Sort by load (lowest first), then by response time
        return $nodes->sortBy(function ($node) {
            return [$node->getLoad(), $node->avg_response_time ?? 9999];
        })->first();
    }

    /**
     * Health check all nodes for a user.
     */
    public function healthCheckAll(?int $userId = null): array
    {
        $query = OllamaNode::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $results = [];
        foreach ($query->get() as $node) {
            $results[$node->id] = $this->healthCheck($node);
        }

        return $results;
    }

    /**
     * Health check a single node.
     */
    public function healthCheck(OllamaNode $node): array
    {
        try {
            $start = microtime(true);
            $response = Http::timeout(5)->get("{$node->url}/api/tags");
            $responseTime = (microtime(true) - $start) * 1000;

            if ($response->successful()) {
                $models = collect($response->json('models', []))
                    ->pluck('name')
                    ->toArray();

                $node->update([
                    'status' => 'online',
                    'models' => $models,
                    'last_seen_at' => now(),
                    'avg_response_time' => $responseTime,
                ]);

                return ['status' => 'online', 'models' => $models, 'response_time' => $responseTime];
            }

            $node->update(['status' => 'offline']);
            return ['status' => 'offline', 'error' => 'Bad response'];

        } catch (\Exception $e) {
            Log::warning("Ollama node health check failed: {$node->name}", ['error' => $e->getMessage()]);
            $node->update(['status' => 'offline']);
            return ['status' => 'offline', 'error' => $e->getMessage()];
        }
    }

    /**
     * Chat completion via best available node.
     */
    public function chat(string $model, array $messages, ?int $userId = null): ?array
    {
        $node = $this->getBestNode($model, $userId);

        if (!$node) {
            Log::error('No available Ollama nodes');
            return null;
        }

        try {
            $node->increment('active_requests');

            $start = microtime(true);
            $response = Http::timeout(120)->post("{$node->url}/api/chat", [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
            ]);

            $responseTime = (microtime(true) - $start) * 1000;

            // Update avg response time (moving average)
            $newAvg = $node->avg_response_time
                ? ($node->avg_response_time * 0.7 + $responseTime * 0.3)
                : $responseTime;
            $node->update(['avg_response_time' => $newAvg, 'last_seen_at' => now()]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Ollama chat failed on {$node->name}: {$e->getMessage()}");
            $node->update(['status' => 'offline']);
            return null;
        } finally {
            $node->decrement('active_requests');
        }
    }

    /**
     * Pull a model to a specific node.
     */
    public function pullModel(OllamaNode $node, string $model): bool
    {
        try {
            $response = Http::timeout(600)->post("{$node->url}/api/pull", [
                'name' => $model,
                'stream' => false,
            ]);

            if ($response->successful()) {
                // Refresh models list
                $this->healthCheck($node);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Model pull failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Register a new node.
     */
    public function registerNode(int $userId, string $name, string $host, int $port = 11434): OllamaNode
    {
        $node = OllamaNode::create([
            'user_id' => $userId,
            'name' => $name,
            'host' => $host,
            'port' => $port,
        ]);

        // Initial health check
        $this->healthCheck($node);

        return $node;
    }
}
