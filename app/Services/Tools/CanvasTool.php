<?php

namespace App\Services\Tools;

use App\Models\Canvas;
use App\Models\Conversation;
use App\Events\CanvasUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CanvasTool implements ToolInterface
{
    public function name(): string
    {
        return 'update_canvas';
    }

    public function description(): string
    {
        return 'Create or update a shared document/canvas in the current workspace. Use this for drafting code, plans, or articles that the user should see in the side panel.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'A descriptive title for the canvas.',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'The full content for the canvas (Markdown or Code).',
                ],
                'canvas_type' => [
                    'type' => 'string',
                    'enum' => ['markdown', 'code', 'text'],
                    'description' => 'The formatting type for the content.',
                ],
            ],
            'required' => ['title', 'content'],
        ];
    }

    public function execute(array $input): string
    {
        try {
            // In a real tool execution, we have access to the current conversation ID via context or singleton?
            // For this implementation, we'll fetch the LATEST active conversation for the system,
            // or we'll need to pass it in. Let's assume the AgentService sets a context.
            
            $conversationId = app('active_conversation_id'); // We will set this in AgentService before run
            
            if (!$conversationId) {
                return "ERROR: No active conversation context found.";
            }

            $canvas = Canvas::where('conversation_id', $conversationId)->first();
            
            if ($canvas) {
                $canvas->update([
                    'title' => $input['title'],
                    'content' => $input['content'],
                    'type' => $input['canvas_type'] ?? 'markdown',
                    'version' => $canvas->version + 1,
                ]);
            } else {
                $canvas = Canvas::create([
                    'conversation_id' => $conversationId,
                    'title' => $input['title'],
                    'content' => $input['content'],
                    'type' => $input['canvas_type'] ?? 'markdown',
                    'version' => 1,
                ]);
            }

            // Fetch refreshed to get version
            $canvas->refresh();

            broadcast(new CanvasUpdated($canvas));

            return "SUCCESS: Canvas '{$canvas->title}' updated to Version {$canvas->version}. The user can now see it in their workspace.";

        } catch (\Exception $e) {
            Log::error("CanvasTool Error: " . $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }
}
