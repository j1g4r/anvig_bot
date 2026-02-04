<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Support\Facades\Auth;

class RagService
{
    /**
     * Search documents for relevant chunks.
     */
    public function search(string $query, ?int $userId = null, int $limit = 5): array
    {
        $userId = $userId ?? Auth::id() ?? 1;

        // Generate query embedding
        $queryEmbedding = $this->generateEmbedding($query);
        if (!$queryEmbedding) {
            return [];
        }

        // Get all chunks for user's documents
        $chunks = DocumentChunk::whereHas('document', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('status', 'indexed');
        })->get();

        // Calculate similarities
        $results = [];
        foreach ($chunks as $chunk) {
            $chunkEmbedding = $chunk->getEmbeddingVector();
            if (!$chunkEmbedding) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryEmbedding, $chunkEmbedding);
            $results[] = [
                'chunk' => $chunk,
                'document' => $chunk->document,
                'similarity' => $similarity,
                'content' => $chunk->content,
            ];
        }

        // Sort by similarity and take top results
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        $count = min(count($a), count($b));
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Generate embedding using OpenAI.
     */
    protected function generateEmbedding(string $text): ?array
    {
        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => substr($text, 0, 8000),
            ]);

            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format search results as context for LLM.
     */
    public function formatAsContext(array $results): string
    {
        if (empty($results)) {
            return 'No relevant documents found.';
        }

        $context = "Relevant document excerpts:\n\n";
        foreach ($results as $i => $result) {
            $docName = $result['document']->name ?? 'Unknown';
            $context .= "--- [{$docName}] ---\n";
            $context .= $result['content'] . "\n\n";
        }

        return $context;
    }
}
