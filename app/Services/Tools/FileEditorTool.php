<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\File;

class FileEditorTool implements ToolInterface
{
    public function name(): string
    {
        return 'file_editor';
    }

    public function description(): string
    {
        return "Advanced file editing. Use for specific string replacements or appending content without overwriting the whole file. Actions: 'str_replace', 'append', 'prepend'.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['str_replace', 'append', 'prepend']],
                'path' => ['type' => 'string'],
                'content' => ['type' => 'string'],
                'target' => ['type' => 'string'],
                'replacement' => ['type' => 'string'],
            ],
            'required' => ['action', 'path']
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? null;
        $path = $input['path'] ?? null;
        $content = $input['content'] ?? '';
        $target = $input['target'] ?? '';
        $replacement = $input['replacement'] ?? '';

        if (!$path) return json_encode(['error' => 'Path is required']);

        $realPath = base_path($path);
        
        if (str_contains($path, '..')) return json_encode(['error' => 'Path traversal not allowed']);
        if (!File::exists($realPath)) return json_encode(['error' => "File not found: $path"]);

        $fileContent = File::get($realPath);
        $result = [];

        switch ($action) {
            case 'str_replace':
                if (empty($target)) {
                    $result = ['error' => 'Target string required for str_replace'];
                    break;
                }
                
                if (!str_contains($fileContent, $target)) {
                    $result = ['error' => 'Target string not found in file'];
                    break;
                }
                
                $newContent = str_replace($target, $replacement, $fileContent);
                File::put($realPath, $newContent);
                $result = ['status' => 'success', 'message' => "Replaced occurrence(s) of target in $path"];
                break;

            case 'append':
                File::append($realPath, "\n" . $content);
                $result = ['status' => 'success', 'message' => "Appended content to $path"];
                break;

            case 'prepend':
                File::put($realPath, $content . "\n" . $fileContent);
                $result = ['status' => 'success', 'message' => "Prepended content to $path"];
                break;

            default:
                $result = ['error' => "Unknown action: $action"];
        }

        return json_encode($result);
    }
}
