<?php

namespace App\Services;

use App\Models\SemanticCache;

class InferenceCacheService
{
    /**
     * Check for a cached response.
     */
    public function lookup(string $query): ?SemanticCache
    {
        $hash = $this->getHash($query);
        
        $hit = SemanticCache::where('query_hash', $hash)->first();

        if ($hit) {
            $hit->hits++;
            $hit->last_hit_at = now();
            $hit->save();
        }

        return $hit;
    }

    /**
     * Store a response in the cache.
     */
    public function store(string $query, string $response): void
    {
        $hash = $this->getHash($query);

        SemanticCache::firstOrCreate(
            ['query_hash' => $hash],
            [
                'query_text' => $query,
                'response' => $response,
                'hits' => 1,
                'last_hit_at' => now(),
            ]
        );
    }

    private function getHash(string $text): string
    {
        // Normalize: lowercase, trim
        $normalized = trim(strtolower($text));
        
        // Remove simple punctuation for better fuzzy match simulation
        $normalized = str_replace(['?', '.', '!', ','], '', $normalized);
        
        return md5($normalized);
    }
}
