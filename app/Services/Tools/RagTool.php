<?php

namespace App\Services\Tools;

use App\Services\RagService;
use Illuminate\Support\Facades\Auth;

class RagTool implements ToolInterface
{
    protected RagService $ragService;

    public function __construct()
    {
        $this->ragService = new RagService();
    }

    public function name(): string
    {
        return 'rag_search';
    }

    public function description(): string
    {
        return 'Search through uploaded documents (PDF, Excel, CSV) to find relevant information. Use this when the user asks questions about their documents or needs to find specific information from files they uploaded.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query to find relevant document content.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of relevant chunks to return (default: 5).',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments): string
    {
        $query = $arguments['query'] ?? '';
        $limit = $arguments['limit'] ?? 5;
        $userId = Auth::id() ?? 1;

        if (empty($query)) {
            return 'Error: query is required for document search.';
        }

        $results = $this->ragService->search($query, $userId, $limit);

        if (empty($results)) {
            return 'No relevant documents found for your query. The user may not have uploaded any documents yet, or the query did not match any content.';
        }

        $output = "Found " . count($results) . " relevant document sections:\n\n";

        foreach ($results as $i => $result) {
            $docName = $result['document']->name ?? 'Unknown';
            $similarity = round($result['similarity'] * 100, 1);
            $output .= "--- [{$docName}] (Relevance: {$similarity}%) ---\n";
            $output .= substr($result['content'], 0, 500);
            if (strlen($result['content']) > 500) {
                $output .= "...";
            }
            $output .= "\n\n";
        }

        return $output;
    }
}
