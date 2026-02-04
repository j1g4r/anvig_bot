<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\File;

class FileTool implements ToolInterface
{
    public function name(): string
    {
        return 'file_system';
    }

    public function description(): string
    {
        return 'Manage files on the local system. Supported actions: list_dir, read_file, write_file. PATHS MUST BE RELATIVE TO PROJECT ROOT.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['list_dir', 'read_file', 'write_file'],
                    'description' => 'The action to perform',
                ],
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative path to file or directory (e.g. "app/Models/Agent.php")',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Content to write (required for write_file)',
                ],
            ],
            'required' => ['action', 'path'],
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? '';
        $path = $input['path'] ?? '';
        $content = $input['content'] ?? null;

        // Sandbox Security Check
        $rootParams = [base_path()];
        $realPath = base_path($path);
        
        // Basic containment check - prevent traversal
        if (str_contains($path, '..')) {
            return "Error: Path traversal (..) is not allowed.";
        }

        switch ($action) {
            case 'list_dir':
                if (!is_dir($realPath)) {
                    return "Error: Directory not found: $path";
                }
                $files = scandir($realPath);
                $files = array_diff($files, ['.', '..', '.git', 'node_modules', 'vendor']); // Filter noise
                return "Directory [$path]:\n" . implode("\n", array_slice($files, 0, 50)); // Limit output

            case 'read_file':
                if (!is_file($realPath)) {
                    return "Error: File not found: $path";
                }
                return File::get($realPath);

            case 'write_file':
                if ($content === null) {
                    return "Error: Content is required for write_file.";
                }
                // Ensure directory exists
                $dir = dirname($realPath);
                if (!is_dir($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                File::put($realPath, $content);
                return "Successfully wrote to $path (" . strlen($content) . " bytes).";

            default:
                return "Error: Unknown action $action";
        }
    }
}
