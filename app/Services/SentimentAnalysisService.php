<?php

namespace App\Services;

class SentimentAnalysisService
{
    /**
     * Analyze text sentiment.
     * Returns ['sentiment' => 'positive|negative|neutral', 'score' => -1.0 to 1.0]
     */
    public function analyze(string $text): array
    {
        // Simple keyword-based analysis for MVP
        // In production, we'd use a Transformer model or external API
        
        $text = strtolower($text);
        
        $positives = ['happy', 'great', 'good', 'love', 'excellent', 'amazing', 'thanks', 'thank', 'cool', 'awesome'];
        $negatives = ['sad', 'bad', 'angry', 'hate', 'terrible', 'awful', 'slow', 'error', 'fail', 'broken', 'stupid', 'frustrated'];
        
        $score = 0;
        
        foreach ($positives as $word) {
            if (str_contains($text, $word)) $score += 0.2;
        }
        
        foreach ($negatives as $word) {
            if (str_contains($text, $word)) $score -= 0.3; // Negatives carry more weight
        }
        
        // Clamp score
        $score = max(-1.0, min(1.0, $score));
        
        // Determine label
        $label = 'neutral';
        if ($score >= 0.1) $label = 'positive';
        if ($score <= -0.1) $label = 'negative';
        
        return [
            'sentiment' => $label,
            'score' => $score
        ];
    }
}
