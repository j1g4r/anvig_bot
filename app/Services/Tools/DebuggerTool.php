<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class DebuggerTool implements ToolInterface
{
    public function name(): string
    {
        return 'system_debugger';
    }

    public function description(): string
    {
        return 'Analyze system health, logs, and environment configurations to diagnose failures. Use this to find why a command failed or why a file is missing.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['check_logs', 'check_permissions', 'check_env', 'inspect_process'],
                    'description' => 'The debugging action to perform.',
                ],
                'path' => [
                    'type' => 'string',
                    'description' => 'Path to file or directory for permission checks.',
                ],
                'log_file' => [
                    'type' => 'string',
                    'description' => 'Specific log file to check (default: laravel.log).',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $input): string
    {
        try {
            switch ($input['action']) {
                case 'check_logs':
                    $logFile = $input['log_file'] ?? 'laravel.log';
                    $logPath = storage_path('logs/' . $logFile);
                    if (!File::exists($logPath)) {
                        return "Error: Log file not found at $logPath";
                    }
                    $lines = array_slice(explode("\n", File::get($logPath)), -20);
                    return "Last 20 lines of $logFile:\n" . implode("\n", $lines);

                case 'check_permissions':
                    $path = base_path($input['path'] ?? '.');
                    if (!File::exists($path)) {
                        return "Error: Path not found: $path";
                    }
                    $perms = substr(sprintf('%o', fileperms($path)), -4);
                    $owner = function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($path))['name'] ?? 'unknown') : 'unknown';
                    return "Permissions for $path: $perms (Owner: $owner)";

                case 'check_env':
                    $envPath = base_path('.env');
                    if (!File::exists($envPath)) {
                        return "Error: .env file missing.";
                    }
                    // Basic check without leaking secrets
                    $content = File::get($envPath);
                    $hasAppKey = str_contains($content, 'APP_KEY=');
                    $hasDb = str_contains($content, 'DB_DATABASE=');
                    return ".env Status: APP_KEY=" . ($hasAppKey ? 'SET' : 'MISSING') . ", DB=" . ($hasDb ? 'SET' : 'MISSING');

                case 'inspect_process':
                    $result = Process::run('ps aux | grep php');
                    return "PHP Processes:\n" . $result->output();

                default:
                    return "Invalid debugging action.";
            }
        } catch (\Exception $e) {
            return "Debugger Error: " . $e->getMessage();
        }
    }
}
