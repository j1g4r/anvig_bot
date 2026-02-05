<?php

namespace App\Services\Tools;

class VoiceNodeTool implements ToolInterface
{
    public function name(): string
    {
        return 'voice_node';
    }

    public function description(): string
    {
        return "Text-to-Speech and Transcription. Actions: 'speak', 'transcribe'.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string'],
                'text' => ['type' => 'string']
            ],
            'required' => ['action']
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'speak';
        $text = $input['text'] ?? '';

        return json_encode([
            'status' => 'simulated_success',
            'message' => "Voice action '$action' processed for text: " . substr($text, 0, 50) . "..."
        ]);
    }
}
