<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;

class CodeAnalyzerTool implements ToolInterface
{
    public function name(): string
    {
        return 'code_analyzer';
    }

    public function description(): string
    {
        return 'Analyze PHP code for syntax errors, static analysis issues, and structural insights. Use this BEFORE writing large changes or AFTER edits to verify correctness.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['check_syntax', 'static_analysis', 'get_structure'],
                    'description' => 'Analysis type.',
                ],
                'file_path' => [
                    'type' => 'string',
                    'description' => 'Relative path to the file.',
                ],
            ],
            'required' => ['action', 'file_path'],
        ];
    }

    public function execute(array $input): string
    {
        $args = array_merge($input, $input['params'] ?? []);
        $action = $args['action'] ?? 'complexity';
        $path = $args['path'] ?? '';
        $fullPath = base_path($path);

        if (!file_exists($fullPath)) {
            return "Error: File '$path' not found.";
        }

        switch ($action) {
            case 'check_syntax':
                return $this->checkSyntax($fullPath);
            case 'static_analysis':
                return $this->runStaticAnalysis($path);
            case 'get_structure':
                return $this->getStructure($fullPath);
            default:
                return "Error: Invalid action.";
        }
    }

    protected function checkSyntax(string $path): string
    {
        $result = Process::run("php -l " . escapeshellarg($path));
        return $result->output();
    }

    protected function runStaticAnalysis(string $relativePath): string
    {
        // Try PHPStan if Configured
        if (file_exists(base_path('vendor/bin/phpstan'))) {
            $cmd = base_path('vendor/bin/phpstan') . " analyse " . escapeshellarg($relativePath) . " --no-progress";
            $result = Process::run($cmd);
            return $result->successful() 
                ? "Static Analysis Passed.\n" . $result->output() 
                : "Static Analysis Found Issues:\n" . $result->output();
        }
        return "Error: PHPStan not found in project. Install it to use static_analysis.";
    }

    protected function getStructure(string $path): string
    {
        // Simple Regex parse for specific constructs (faster/safer than reflection on broken code)
        $content = file_get_contents($path);
        
        $lines = explode("\n", $content);
        $structure = [];
        
        foreach ($lines as $i => $line) {
            $lineNum = $i + 1;
            if (preg_match('/^\s*(class|interface|trait|enum)\s+(\w+)/', $line, $m)) {
                $structure[] = "Line $lineNum: [{$m[1]}] {$m[2]}";
            }
            if (preg_match('/^\s*(public|protected|private)?\s*function\s+(\w+)\(/', $line, $m)) {
                $vis = $m[1] ?: 'public';
                $structure[] = "  Line $lineNum: method $vis {$m[2]}()";
            }
        }
        
        if (empty($structure)) {
            return "No classes or functions found (or format too complex for regex parser).";
        }
        
        return "File Structure for $path:\n" . implode("\n", $structure);
    }
}
