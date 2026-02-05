<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;

class DeployerTool implements ToolInterface
{
    public function name(): string
    {
        return 'deployer_tool';
    }

    public function description(): string
    {
        return "Trigger deployments or build checks. Actions: 'check_build', 'deploy_staging'.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string', 'enum' => ['check_build', 'deploy_staging']]
            ],
            'required' => ['action']
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'check_build';

        if ($action === 'check_build') {
            $result = Process::run('npm run build');
             return json_encode([
                'status' => $result->successful() ? 'success' : 'failed',
                'output' => substr($result->output(), -500)
            ]);
        }

        return json_encode(['status' => 'simulated_success', 'message' => "Deployment action '$action' triggered (Mock)."]);
    }
}
