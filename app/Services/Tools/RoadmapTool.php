<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\File;

class RoadmapTool implements ToolInterface
{
    private string $roadmapPath;

    public function __construct()
    {
        // Hardcoded path to the brain/task.md for now, or use a config
        // In this environment, we know the path is in the brain dir.
        // We'll use a glob map or find the active task.md
        // For robustness, let's look for the one in the current brain context or pass it via constructor?
        // Simpler: Just rely on the existence of 'task.md' in the specific directory we've been using.
        // Or better: Let the AgentService inject context.
        // But for this tool, let's assume it operates on the "Global Project Roadmap".
        // We will make it flexible to accept a path, but default to finding one.
        
        // Let's assume the path is passed or we find it. For now, empty, we'll implement logic to find it.
        $this->roadmapPath = ''; 
    }

    public function name(): string
    {
        return 'roadmap_manager';
    }

    public function description(): string
    {
        return 'Read or update the project roadmap (task.md). Use this to mark cycles as complete or add new cycles.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['read', 'mark_complete', 'add_cycle'],
                    'description' => 'The action to perform.',
                ],
                'cycle_id' => [
                    'type' => 'string',
                    'description' => 'The Cycle ID (e.g., C42) to mark as complete.',
                ],
                'cycle_description' => [
                    'type' => 'string',
                    'description' => 'Description for a new cycle (only for add_cycle action).',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $args): string
    {
        // FIND PATH: We need a reliable way to find the task.md.
        // In this specific environment, it's deep in .gemini/...
        // A hacky but effective way for this MVP is to search for it using the same patterns we use.
        // OR: We configure the path in .env? No.
        // We will search for 'task.md' in the .gemini directory recursively if not set.
        
        if (empty($this->roadmapPath)) {
            // Attempt to find the task.md in the user's home directory structure
            // Assuming the structure is /Users/jp_mac/.gemini/... and app is in /Users/jp_mac/workplace/...
            // We can try absolute path based on known user home info or traverse up.
            
            // Try explicit path first (since we know the env)
            $home = getenv('HOME') ?: '/Users/jp_mac';
            $files = glob("$home/.gemini/antigravity/brain/*/task.md");

            if (empty($files)) {
                  // Fallback: try relative from base_path (Laravel root)
                  // base_path = /Users/jp_mac/workplace/JP/ANVIG_BOT
                  // We need to go up 4 levels to get to Users/jp_mac ?? No.
                  // /Users/jp_mac/workplace/JP/ANVIG_BOT (4 segments)
                  // .gemini is at /Users/jp_mac/.gemini
                  
                  $files = glob(base_path('../../../.gemini/antigravity/brain/*/task.md'));
            }

            if (empty($files)) {
                return "Error: Could not locate task.md roadmap file. Searched in .gemini paths.";
            }

            // Sort by modification time to get the active one
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $this->roadmapPath = $files[0];
        }

        $action = $args['action'];
        $content = File::get($this->roadmapPath);

        if ($action === 'read') {
            return $content;
        }

        if ($action === 'mark_complete') {
            $cycle = $args['cycle_id'] ?? '';
            if (!$cycle) return "Error: cycle_id required for mark_complete";

            // Regex/String replace to find "[ ] **$cycle**" or "[/] **$cycle**" and change to "[x]"
            // Also update "Progress" section?
            
            $pattern = "/\[[ \/]?\] \*\*$cycle\*\*/";
            $replacement = "[x] **$cycle**";
            
            if (!preg_match($pattern, $content)) {
                return "Error: Cycle $cycle not found or already completed.";
            }
            
            $newContent = preg_replace($pattern, $replacement, $content);
            File::put($this->roadmapPath, $newContent);
            
            return "Marked $cycle as complete.";
        }

        if ($action === 'add_cycle') {
            // Logic to append cycle... simple concatenation
            return "Feature add_cycle not fully implemented yet.";
        }

        return "Unknown action.";
    }
}
