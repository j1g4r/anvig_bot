<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;

class ShellTool implements ToolInterface
{
    public function name(): string
    {
        return 'run_command';
    }

    public function description(): string
    {
        return 'Execute a CLI command on the host machine. Returns stdout/stderr. NETWORK ACCESS IS BLOCKED. SENSITIVE ENV VARS ARE STRIPPED.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The command to execute',
                ],
            ],
            'required' => ['command'],
        ];
    }

    private bool $isAgent = false;

    public function setAgentContext(bool $isAgent): void
    {
        $this->isAgent = $isAgent;
    }

    public function execute(array $input): string
    {
        // Robustness: Unwrap 'params'
        $args = array_merge($input, $input['params'] ?? []);
        $command = $args['command'] ?? '';

        // Enforce Agent Context
        if (!$this->isAgent) {
            return 'SECURITY BLOCK: This tool can ONLY be executed by an autonomous Agent. Direct access is forbidden.';
        }

        if (empty($command)) {
            return 'Error: No command provided.';
        }

        if (!$this->isSafe($command)) {
            return "SECURITY ALERT: The command '$command' contains banned keywords (network tools, system modification, or sensitive file access) and has been blocked.";
        }

        try {
            // Sanitize Environment: Only pass PATH to the child process.
            // This prevents the command from accessing APP_KEY, DB_PASSWORD, AWS_KEYS, etc.
            $safeEnv = [
                'PATH' => getenv('PATH'),
                'TERM' => 'xterm-256color', // Sometimes needed for formatting
            ];

            $result = Process::env($safeEnv)->run($command);
            
            $output = $result->output();
            if ($result->failed()) {
                $output .= "\nError Output:\n" . $result->errorOutput();
            }
            return trim($output) ?: 'Command executed successfully with no output.';
        } catch (\Throwable $e) {
            return 'Execution failed: ' . $e->getMessage();
        }
    }

    private function isSafe(string $command): bool
    {
        // 1. Block Network / Exfiltration Tools
        $networkBlacklist = [
            'nc', 'netcat', 'ncat', 
            'ssh', 'scp', 'rsync', 'ftp', 'telnet',
            'socat',
            '/dev/tcp', '/dev/udp'
        ];

        // 2. Block System Modification / Destructive Commands
        $systemBlacklist = [
            'rm -rf', 'mkfs', 'dd if=', 'shutdown', 'reboot', 'halt', 
            'init 0', ':(){:|:&};:', 'chmod', 'chown', 'useradd', 'userdel'
        ];

        // 3. Block Access to Sensitive Files/Vars
        $privacyBlacklist = [
            '.env', 'printenv', 'export', // 'env' is tricky, removed to avoid blocking 'venv' etc. handled by regex below if needed, but 'printenv' covers most dump cases.
            'cat /etc/passwd', 'cat /etc/shadow', 'id_rsa'
        ];

        $allBanned = array_merge($networkBlacklist, $systemBlacklist, $privacyBlacklist);

        foreach ($allBanned as $term) {
            // Escape the term for regex, but allow for word boundaries
            // We want to match whole words. e.g. "ping" but not "mapping"
            // Special handling for terms with spaces or symbols
            
            $pattern = '/\b' . preg_quote($term, '/') . '\b/i';
            
            // If the term contains special bash chars (like . or /), word boundaries might behave differently.
            // For simple tools like 'curl', \b works.
            // For '.env', \b works before 'env' but '.' is a boundary.
            
            if (preg_match($pattern, $command)) {
                 return false;
            }
        }
        
        // Special strict check for 'env' command alone or at start
        if (preg_match('/(^|\s)env($|\s)/', $command)) {
            return false;
        }

        return true;
    }
}
