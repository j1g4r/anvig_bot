<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Approximate cost per 1k tokens (USD). Update as pricing changes.
     */
    const RATES = [
        'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
        'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
        'text-embedding-3-small' => ['input' => 0.00002, 'output' => 0.0],
    ];

    /**
     * Wrapper for Chat Completion with Usage Tracking
     */
    public function chat(array $params, array $metadata = [])
    {
        try {
            $response = OpenAI::chat()->create($params);
            
            // Track Usage
            if (isset($response->usage)) {
                $model = $params['model'] ?? 'gpt-4o-mini';
                $this->logUsage(
                    $model,
                    $response->usage->promptTokens,
                    $response->usage->completionTokens,
                    $response->usage->totalTokens,
                    $metadata
                );
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("AIService Chat Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Wrapper for Embeddings with Usage Tracking
     */
    public function embedding(array $params, array $metadata = [])
    {
        try {
            $response = OpenAI::embeddings()->create($params);

            // Usage is usually at top level for embeddings response from laravel-openai
            if (isset($response->usage)) {
                $model = $params['model'] ?? 'text-embedding-3-small';
                $this->logUsage(
                    $model,
                    $response->usage->promptTokens,
                    0, // Embeddings have no output tokens
                    $response->usage->totalTokens,
                    $metadata
                );
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("AIService Embedding Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log usage to the database
     */
    protected function logUsage(string $model, int $input, int $output, int $total, array $metadata)
    {
        try {
            // Calculate Cost
            $rate = self::RATES[$model] ?? self::RATES['gpt-4o-mini']; // Default to mini
            $cost = ($input / 1000 * $rate['input']) + ($output / 1000 * $rate['output']);

            DB::table('api_logs')->insert([
                'endpoint' => 'openai',
                'model' => $model,
                'tokens_input' => $input,
                'tokens_output' => $output,
                'tokens_total' => $total,
                'cost' => $cost,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Do not fail the request if logging fails, just log error
            Log::error("Failed to log API usage: " . $e->getMessage());
        }
    }
}
