<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->baseUrl = env('OPENAI_BASE_URL', 'https://ollama.com');
        $this->model = env('EMBEDDING_MODEL', 'nomic-embed-text'); 
        // Note: text-embedding-3-small is OpenAI. If using Ollama Cloud with no OpenAI proxy, usage of nomic-embed-text is standard.
    }

    public function getEmbedding(string $text): array
    {
        try {
            // New Ollama API: /api/embed
            // Docs: https://github.com/ollama/ollama/blob/main/docs/api.md#generate-embeddings
            $endpoint = rtrim($this->baseUrl, '/') . '/api/embed';
            
            // Note: If user has /v1 in env, strip it
            if (str_contains($endpoint, '/v1/api/embed')) {
                 $endpoint = str_replace('/v1/api/embed', '/api/embed', $endpoint);
            }

            $response = Http::withToken(env('OPENAI_API_KEY'))
                ->post($endpoint, [
                    'model' => $this->model,
                    'input' => $text,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['embeddings'][0] ?? [];
            } else {
                 Log::error('Embedding Failed [' . $response->status() . ']: ' . $response->body());
                 return [];
            }
            
        } catch (\Exception $e) {
            Log::error('Embedding Exception: ' . $e->getMessage());
            return [];
        }
    }

    public function packVector(array $vector): string
    {
        return pack('f*', ...$vector);
    }

    public function unpackVector(string $binary): array
    {
        return array_values(unpack('f*', $binary));
    }

    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        $count = count($vecA);
        for ($i = 0; $i < $count; $i++) {
            $a = $vecA[$i];
            $b = $vecB[$i] ?? 0;
            $dotProduct += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
