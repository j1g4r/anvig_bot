<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Http;

class GitHubTool implements ToolInterface
{
    public function name(): string
    {
        return 'github_manager';
    }

    public function description(): string
    {
        return 'Interact with GitHub. Actions: list_issues, get_issue, create_issue, list_prs. Requires GITHUB_TOKEN.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['list_issues', 'get_issue', 'create_issue', 'list_prs'],
                ],
                'repo' => [
                    'type' => 'string',
                    'description' => 'Repository content (owner/name)',
                ],
                'issue_number' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'body' => ['type' => 'string'],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $input): string
    {
        $args = array_merge($input, $input['params'] ?? []);
        $action = $args['action'] ?? '';
        $repo = $args['repo'] ?? env('GITHUB_DEFAULT_REPO', 'my-org/my-repo');

        $token = env('GITHUB_TOKEN');
        if (empty($token)) {
             return json_encode([
                'status' => 'error',
                'message' => 'GITHUB_TOKEN is missing in .env. Returning Mock Data for demonstration.',
                'mock_data' => $this->getMockData($action)
            ]);
        }

        $baseUrl = "https://api.github.com/repos/$repo";
        $headers = [
            'Authorization' => "Bearer $token",
            'Accept' => 'application/vnd.github.v3+json',
        ];

        try {
            switch ($action) {
                case 'list_issues':
                    $response = Http::withHeaders($headers)->get("$baseUrl/issues");
                    return $response->body();

                case 'get_issue':
                    $num = $args['issue_number'] ?? 0;
                    $response = Http::withHeaders($headers)->get("$baseUrl/issues/$num");
                    return $response->body();

                case 'create_issue':
                    $response = Http::withHeaders($headers)->post("$baseUrl/issues", [
                        'title' => $args['title'] ?? 'New Issue',
                        'body' => $args['body'] ?? '',
                    ]);
                    return $response->body();

                default:
                    return json_encode(['error' => "Unknown action: $action"]);
            }
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function getMockData($action) {
        if ($action === 'list_issues') {
            return [
                ['number' => 101, 'title' => 'Fix Memory Leak', 'state' => 'open'],
                ['number' => 102, 'title' => 'Add Dark Mode', 'state' => 'open'],
            ];
        }
        return ['message' => 'Simulated success for ' . $action];
    }
}
