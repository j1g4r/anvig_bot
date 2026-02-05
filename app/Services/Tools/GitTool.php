<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;

class GitTool implements ToolInterface, ContextAwareToolInterface
{
    protected ?\App\Models\Conversation $conversation = null;
    protected bool $isAgentContext = false;

    public function name(): string
    {
        return 'git_version_control';
    }

    public function description(): string
    {
        return "Manage project version control. Use this to save work, check changes, or switch branches. Actions: 'status', 'diff', 'log', 'commit', 'checkout_branch', 'create_branch'.";
    }

    public function setConversation(\App\Models\Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function setAgentContext(bool $isAgent): void
    {
        $this->isAgentContext = $isAgent;
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string'],
                'params' => ['type' => 'object'],
            ],
            'required' => ['action']
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'status';
        $params = $input['params'] ?? []; 
        $message = $input['message'] ?? ($params['message'] ?? 'Auto-commit agent work');
        $branch = $input['branch'] ?? ($params['branch'] ?? 'main');

        $result = match ($action) {
            'status' => $this->gitStatus(),
            'diff' => $this->gitDiff(),
            'log' => $this->gitLog(),
            'commit' => $this->gitCommit($message),
            'checkout_branch' => $this->gitCheckout($branch, false),
            'create_branch' => $this->gitCheckout($branch, true),
            default => ['error' => "Unknown git action: $action"]
        };

        return json_encode($result);
    }

    protected function gitStatus(): array
    {
        $result = Process::run('git status --short');
        return [
            'output' => $result->output(),
            'clean' => empty(trim($result->output()))
        ];
    }

    protected function gitDiff(): array
    {
        // Limit diff size to prevent token overflow
        $result = Process::run('git diff HEAD --stat');
        $fullDiff = Process::run('git diff HEAD');
        
        $output = $fullDiff->output();
        if (strlen($output) > 5000) {
            $output = substr($output, 0, 5000) . "\n... (Diff truncated)";
        }

        return [
            'stat' => $result->output(),
            'diff' => $output
        ];
    }

    protected function gitLog(): array
    {
        $result = Process::run('git log -n 5 --oneline');
        return ['history' => $result->output()];
    }

    protected function gitCommit(string $message): array
    {
        if (empty(trim($message))) {
            return ['error' => 'Commit message cannot be empty'];
        }

        // Add all changes
        Process::run('git add .');
        
        $result = Process::run(['git', 'commit', '-m', $message]);
        
        if ($result->successful()) {
            return ['status' => 'success', 'output' => $result->output()];
        }
        
        return ['status' => 'error', 'output' => $result->errorOutput() ?: $result->output()];
    }

    protected function gitCheckout(string $branch, bool $create = false): array
    {
        if (empty($branch)) return ['error' => 'Branch name required'];

        $cmd = $create ? ['git', 'checkout', '-b', $branch] : ['git', 'checkout', $branch];
        $result = Process::run($cmd);

        if ($result->successful()) {
            return ['status' => 'success', 'current_branch' => $branch];
        }

        return ['status' => 'error', 'output' => $result->errorOutput()];
    }
}
