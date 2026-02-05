<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;

class PythonReplTool implements ToolInterface
{
    public function name(): string
    {
        return 'python_repl';
    }

    public function description(): string
    {
        return 'A Python shell. Use this to execute python commands. Input should be a valid python script. Useful for pure calculations, data parsing, or complex logic. Returns stdout.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => [
                    'type' => 'string',
                    'description' => 'The python code to execute.',
                ],
            ],
            'required' => ['code'],
        ];
    }

    public function execute(array $input): string
    {
        // Robustness: Unwrap 'params'
        $args = array_merge($input, $input['params'] ?? []);
        $code = $args['code'] ?? '';

        if (empty($code)) {
            return "Error: No code provided.";
        }

        // Basic Safety: Prevent obvious system calls?
        // For now, we rely on the container security.
        // But let's block 'os.system' as a basic hurdle.
        if (str_contains($code, 'os.system') || str_contains($code, 'subprocess')) {
             return "Security Error: Direct system calls restricted in REPL. Use shell_tool if you really need OS access.";
        }

        try {
            // Write to a temporary file to handle multi-line strings correctly
            $tmpFile = sys_get_temp_dir() . '/agent_script_' . uniqid() . '.py';
            file_put_contents($tmpFile, $code);

            // Execute with timeout
            $result = Process::timeout(10)->run("python3 " . escapeshellarg($tmpFile));
            
            // Clean up
            @unlink($tmpFile);

            if ($result->failed()) {
                return json_encode([
                    'status' => 'error',
                    'error' => $result->errorOutput(),
                    'exit_code' => $result->exitCode()
                ]);
            }

            return json_encode([
                'status' => 'success',
                'output' => trim($result->output())
            ]);

        } catch (\Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
