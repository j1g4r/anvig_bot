<?php

namespace App\Services\Tools;

class SlackTool implements ToolInterface
{
    public function name(): string
    {
        return 'slack_tool';
    }

    public function description(): string
    {
        return "Interact with Team Chat (Slack/Discord). Actions: 'send_message', 'read_channel'. *CURRENTLY A STUB*.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string'],
                'message' => ['type' => 'string']
            ],
            'required' => ['message']
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'send_message';
        $message = $input['message'] ?? '';

        return json_encode([
            'status' => 'simulated_success',
            'message' => "Mock message sent to channel: $message"
        ]);
    }
}
