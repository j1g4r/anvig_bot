<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseTool implements ToolInterface
{
    public function name(): string
    {
        return 'database_explorer';
    }

    public function description(): string
    {
        return 'Explore the internal database structure and query data. Useful for research and internal state auditing.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['schema', 'query'],
                    'description' => 'What to do. "schema" lists tables. "query" runs a SELECT.',
                ],
                'sql' => [
                    'type' => 'string',
                    'description' => 'The SQL query to run (only for "query" action). ONLY SELECT statements are allowed for safety.',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'schema';

        if ($action === 'schema') {
            return $this->getSchema();
        }

        if ($action === 'query') {
            $sql = $input['sql'] ?? '';
            if (empty($sql)) {
                return "Error: SQL query is required for 'query' action.";
            }
            return $this->runQuery($sql);
        }

        return "Error: Unknown action '$action'.";
    }

    protected function getSchema(): string
    {
        $tables = DB::getSchemaBuilder()->getTableListing();
        $output = "Database Tables:\n";
        
        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            $output .= "- $table: (" . implode(', ', $columns) . ")\n";
        }

        return $output;
    }

    protected function runQuery(string $sql): string
    {
        // Simple safety check
        $lSql = strtolower(trim($sql));
        if (!str_starts_with($lSql, 'select')) {
            return "Error: For safety reasons, only SELECT statements are permitted.";
        }

        try {
            $results = DB::select($sql);
            
            if (empty($results)) {
                return "Query executed successfully, but returned 0 rows.";
            }

            // Convert to a simple JSON string or markdown-ish table
            return json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            return "SQL Error: " . $e->getMessage();
        }
    }
}
