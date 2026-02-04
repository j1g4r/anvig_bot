<?php
/**
 * Jerry: Developer Tool for Desktop Control
 * 
 * Capability: Vision-based GUI interaction.
 */

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DesktopTool implements ToolInterface
{
    private $venvPath;
    private $scriptPath;

    public function __construct()
    {
        $this->venvPath = base_path('venv/bin/activate');
        $this->scriptPath = base_path('scripts/desktop_sentience.py');
    }

    public function name(): string
    {
        return 'desktop_control';
    }

    public function description(): string
    {
        return 'Interact with the host OS GUI. Actions: capture (vision), mouse (click/move), keyboard (type/press). Returns status or base64 image data.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['capture', 'mouse', 'keyboard'],
                    'description' => 'The desktop interaction to perform.',
                ],
                'subaction' => [
                    'type' => 'string',
                    'enum' => ['click', 'double_click', 'right_click', 'move', 'type', 'press'],
                    'description' => 'Specific action for mouse or keyboard.',
                ],
                'x' => ['type' => 'integer', 'description' => 'X coordinate for mouse actions.'],
                'y' => ['type' => 'integer', 'description' => 'Y coordinate for mouse actions.'],
                'text' => ['type' => 'string', 'description' => 'Text to type.'],
                'key' => ['type' => 'string', 'description' => 'Key name to press (e.g., "enter", "esc", "f1").'],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? '';
        $subaction = $input['subaction'] ?? '';
        $x = $input['x'] ?? null;
        $y = $input['y'] ?? null;
        $text = $input['text'] ?? '';
        $key = $input['key'] ?? '';

        $cmd = "source {$this->venvPath} && python3 {$this->scriptPath} --action {$action}";
        
        if ($subaction) $cmd .= " --subaction {$subaction}";
        if ($x !== null) $cmd .= " --x {$x}";
        if ($y !== null) $cmd .= " --y {$y}";
        if ($text) $cmd .= " --text " . escapeshellarg($text);
        if ($key) $cmd .= " --key " . escapeshellarg($key);

        try {
            $result = Process::run($cmd);
            $data = json_decode($result->output(), true);

            if (!$data) {
                return "Execution Failed: " . $result->errorOutput() ?: "Unknown python error.";
            }

            if (!$data['success']) {
                return "Action Failed: " . ($data['error'] ?? 'Unknown error');
            }

            if ($action === 'capture') {
                // Return base64 for the agent to potentially "see" via vision
                // We also save it to storage so the user can see it in the chat if needed
                $filename = 'desktop_' . time() . '.jpg';
                Storage::disk('public')->put('chat-images/' . $filename, base64_decode($data['image']));
                
                return "Desktop Captured. [IMAGE_ATTACHED: storage/chat-images/{$filename}] Resolution: {$data['width']}x{$data['height']}. Base64 Data returned internally.";
            }

            return "Desktop action '{$action}' performed successfully.";
        } catch (\Throwable $e) {
            return "Execution failed: " . $e->getMessage();
        }
    }
}
