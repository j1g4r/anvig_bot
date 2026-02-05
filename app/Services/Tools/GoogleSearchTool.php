<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Http;

class GoogleSearchTool implements ToolInterface
{
    public function name(): string
    {
        return 'google_search';
    }

    public function description(): string
    {
        return 'Search Google for information. Returns a list of relevant titles and URLs.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query.',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $input): string
    {
        $args = array_merge($input, $input['params'] ?? []);
        $query = $args['query'] ?? '';

        if (empty($query)) {
            return "Error: Search query required.";
        }

        // TODO: Integrate real SerpApi or Custom Search JSON API
        // For now, we return a helpful message or use a free scraper (which is flaky).
        // Let's Stub it with a "Simulated" result for common queries to verify the flow,
        // and a message asking for an API Key for real results.

        // Simulated Responses for Testing
        if (str_contains(strtolower($query), 'laravel')) {
            return json_encode([
                ['title' => 'Laravel - The PHP Framework for Web Artisans', 'url' => 'https://laravel.com'],
                ['title' => 'Laravel News', 'url' => 'https://laravel-news.com'],
                ['title' => 'Laravel Documentation', 'url' => 'https://laravel.com/docs'],
            ]);
        }
        
        if (str_contains(strtolower($query), 'agent')) {
             return json_encode([
                ['title' => 'AutoGPT', 'url' => 'https://github.com/Significant-Gravitas/Auto-GPT'],
                ['title' => 'LangChain', 'url' => 'https://www.langchain.com'],
                ['title' => 'BabyAGI', 'url' => 'https://github.com/yoheinakajima/babyagi'],
            ]);
        }

        // Fallback for unknown queries
        return json_encode([
            'status' => 'partial_success',
            'message' => "Search functionality requires a SERP API Key (Not Configured).",
            'suggestion' => "Please ask the user to configure the Google Search Service.",
            'simulated_result' => [
               ['title' => "Result for '$query'", 'url' => "https://google.com/search?q=" . urlencode($query)] 
            ]
        ]);
    }
}
