<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Process;

class TestRunnerTool implements ToolInterface
{
    public function name(): string
    {
        return 'test_runner';
    }

    public function description(): string
    {
        return "Run automated PHP tests. Actions: 'run_all', 'run_filter'. Params: 'filter' (string) for specific test files/methods.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string'],
                'filter' => ['type' => 'string']
            ],
            'required' => []
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'run_all';
        $filter = $input['filter'] ?? null;

        $cmd = ['php', 'artisan', 'test'];
        
        if ($filter) {
            $cmd[] = '--filter';
            $cmd[] = $filter;
        }

        $result = Process::run($cmd);
        $output = $result->output();

        $passed = str_contains($output, 'PASS');
        $failed = str_contains($output, 'FAIL');

        $failures = [];
        if ($failed) {
            preg_match_all('/FAIL\s+(.*)/', $output, $matches);
            $failures = $matches[1] ?? [];
        }

        return json_encode([
            'status' => $passed ? 'passed' : 'failed',
            'summary' => $passed ? 'All tests passed.' : count($failures) . ' tests failed.',
            'failures' => array_slice($failures, 0, 10),
            'raw_output_snippet' => substr($output, -2000)
        ]);
    }
}
