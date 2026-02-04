<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BrowserTool implements ToolInterface
{
    public function name(): string
    {
        return 'browser_control';
    }

    public function description(): string
    {
        return 'Control a web browser to navigate pages, extract content, or take screenshots. Useful for research or verifying web UI.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['browse', 'content', 'screenshot'],
                    'description' => 'Action to perform. "browse" just visits. "content" returns text. "screenshot" saves image.',
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'The URL to visit.',
                ],
            ],
            'required' => ['action', 'url'],
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'browse';
        $url = $input['url'] ?? '';

        if (empty($url)) {
            return "Error: URL is required.";
        }

        // Call the Node.js Bridge
        $script = base_path('browser-bridge.cjs');
        $node = 'node'; // Assume node is in PATH

        $result = Process::run([$node, $script, $action, $url]);

        if ($result->failed()) {
            return "Browser Error: " . $result->errorOutput();
        }

        $output = $result->output();
        
        // Try to parse JSON output from bridge
        $json = json_decode($output, true);
        if ($json) {
            if (isset($json['error'])) {
                return "Browser Error: " . $json['error'];
            }
            if ($action === 'screenshot' && isset($json['screenshot_path'])) {
                return "Screenshot saved to: " . $json['screenshot_path'];
            }
            if ($action === 'content' && isset($json['content'])) {
                return "Page Content:\n" . $json['content'];
            }
            return "Browser Action '$action' completed on $url";
        }

        return "Raw Output: " . $output;
    }
}
