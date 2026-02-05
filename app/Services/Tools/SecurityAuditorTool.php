<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;

class SecurityAuditorTool implements ToolInterface
{
    public function name(): string
    {
        return 'security_auditor';
    }

    public function description(): string
    {
        return "Audit project security. Actions: 'npm_audit', 'composer_audit'.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['npm_audit', 'composer_audit']]
            ],
            'required' => ['action']
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'npm_audit';
        $cmd = match($action) {
            'composer_audit' => 'composer audit --format=json',
            default => 'npm audit --json'
        };

        $result = Process::run($cmd);
        
        return json_encode([
            'status' => $result->successful() ? 'secure' : 'vulnerable',
            'output' => substr($result->output(), 0, 2000) . '...' // Truncate
        ]);
    }
}
