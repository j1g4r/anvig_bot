<?php

namespace App\Services\Tools;

use App\Services\EmbeddingService;
use Illuminate\Support\Facades\DB;
use App\Models\Agent;

use App\Services\Tools\ContextAwareToolInterface;
use App\Models\Conversation;

class MemoryTool implements ToolInterface, ContextAwareToolInterface
{
    protected EmbeddingService $embedder;
    protected ?Conversation $conversation = null;

    public function __construct()
    {
        $this->embedder = new EmbeddingService();
    }

    public function setConversation(Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function name(): string
    {
        return 'memory_bank';
    }

    public function description(): string
    {
        return 'Long-term memory storage. Use "save" to remember facts/docs. Use "search" to retrieve info.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['save', 'search'],
                    'description' => 'save or search',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Text to save or the query to search for.',
                ],
            ],
            'required' => ['action', 'content'],
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? '';
        $content = $input['content'] ?? '';

        if (empty($content)) {
            return "Error: content required.";
        }

        // Use context agent ID if available, fallback to 1 (System)
        $agentId = $this->conversation ? $this->conversation->agent_id : 1; 

        if ($action === 'save') {
            // Simple Chunking Strategy (approx 500 words / 3000 chars per chunk)
            $chunks = str_split($content, 3000); 
            $count = 0;

            foreach ($chunks as $chunk) {
                // Try embedding
                $vector = $this->embedder->getEmbedding($chunk); 
                
                DB::table('memories')->insert([
                    'agent_id' => $agentId,
                    'content' => $chunk,
                    'embedding' => !empty($vector) ? json_encode($vector) : null,
                    'embedding_binary' => !empty($vector) ? $this->embedder->packVector($vector) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }

            return "Memory saved successfully across $count chunks (Binary Vector Optimization Active).";
        }

        if ($action === 'search') {
            $queryVector = $this->embedder->getEmbedding($content);
            $results = [];

            // 1. Vector Search (Use Binary for Speed)
            if (!empty($queryVector)) {
                $memories = DB::table('memories')
                    ->where('agent_id', $agentId)
                    ->whereNotNull('embedding_binary')
                    ->get();
                
                foreach ($memories as $mem) {
                    $memVector = $this->embedder->unpackVector($mem->embedding_binary);
                    $score = $this->embedder->cosineSimilarity($queryVector, $memVector);
                    if ($score > 0.4) {
                        $results[] = ['score' => $score, 'content' => $mem->content, 'date' => $mem->created_at];
                    }
                }
            }

            // 2. Keyword Search Fallback (if no vector results or vector failed)
            if (empty($results)) {
                $keywordMemories = DB::table('memories')
                    ->where('agent_id', $agentId)
                    ->where('content', 'LIKE', '%' . $content . '%')
                    ->limit(5)
                    ->get();
                
                foreach ($keywordMemories as $kMem) {
                    $results[] = ['score' => 1.0, 'content' => $kMem->content, 'date' => $kMem->created_at, 'type' => 'keyword'];
                }
            }

            // Sort by score desc
            usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
            
            $top = array_slice($results, 0, 3);
            
            if (empty($top)) {
                return "No relevant memories found.";
            }

            $out = "Found related memories:\n";
            foreach ($top as $r) {
                $type = isset($r['type']) ? "(Keyword Match)" : "[Score: " . number_format($r['score'], 2) . "]";
                $out .= "- $type " . $r['content'] . "\n";
            }
            return $out;
        }

        return "Unknown action.";
    }
}
