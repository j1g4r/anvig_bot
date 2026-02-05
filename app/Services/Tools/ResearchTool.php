<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;
use App\Models\Agent;

class ResearchTool implements ToolInterface
{
    protected BrowserTool $browser;
    protected MemoryTool $memory;

    public function __construct()
    {
        $this->browser = new BrowserTool();
        $this->memory = new MemoryTool();
    }

    public function name(): string
    {
        return 'research_web';
    }

    public function description(): string
    {
        return 'Visit a URL, read its content, and memorize it for future questions. Use this to "learn" a new page.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'The URL to research and learn.',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function execute(array $input): string
    {
        $args = array_merge($input, $input['params'] ?? []);
        $url = $args['url'] ?? '';
        if (empty($url)) {
            return "Error: URL is required.";
        }

        // 1. Browse and Extract Content
        $browserResult = $this->browser->execute(['action' => 'content', 'url' => $url]);
        
        // Parse the formatted output from BrowserTool
        // BrowserTool strings look like "Page Content:\n..." or "Browser Action..."
        // But BrowserTool returns plain string.
        // Wait, did I update BrowserTool to return JSON? No.
        // The *bridge* returns JSON, but BrowserTool->execute() returns string.
        
        // Let's look at BrowserTool output again.
        // "Page Content:\n" . $json['content'];
        
        if (strpos($browserResult, "Page Content:") === false) {
            return "Failed to retrieve content from page. Error: " . substr($browserResult, 0, 100);
        }

        $content = str_replace("Page Content:\n", "", $browserResult);
        
        // 2. Prepare Memory Content
        // We'll prefix it with metadata so the agent knows where it came from.
        $memoryContent = "Source: $url\n\n" . $content;

        // 3. Save to Memory
        $memoryResult = $this->memory->execute(['action' => 'save', 'content' => $memoryContent]);

        return "Research Complete for $url.\n$memoryResult";
    }
}
