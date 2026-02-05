<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Storage;

class WriterTool implements ToolInterface
{
    public function name(): string
    {
        return 'writer_tool';
    }

    public function description(): string
    {
        return "Advanced content generation. Use this for writing articles, documentation, or long-form copy. Action: 'save_content'. Params: 'filename', 'content'.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'filename' => ['type' => 'string'],
                'content' => ['type' => 'string']
            ],
            'required' => ['content']
        ];
    }

    public function execute(array $input): string
    {
        // Robustness: Unwrap 'params' if the LLM hallucinated the Action/Params pattern
        $args = array_merge($input, $input['params'] ?? []);

        $filename = $args['filename'] ?? ('draft_' . time() . '.md');
        $content = $args['content'] ?? '';

        if (empty($content)) {
            return json_encode(['error' => 'Content cannot be empty']);
        }

        // Save to specific 'documents' folder
        $path = "documents/" . basename($filename);
        Storage::disk('local')->put($path, $content);

        return json_encode([
            'status' => 'success',
            'message' => "Content saved to {$path}",
            'word_count' => str_word_count($content)
        ]);
    }
}
