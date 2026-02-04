<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookTool implements ToolInterface
{
    public function name(): string
    {
        return 'web_request';
    }

    public function description(): string
    {
        return 'Send an HTTP request to an external URL (Webhooks/APIs).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'The destination URL.',
                ],
                'method' => [
                    'type' => 'string',
                    'enum' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'description' => 'HTTP method to use (default: POST).',
                ],
                'data' => [
                    'type' => 'object',
                    'description' => 'JSON body or query parameters to send.',
                ],
                'headers' => [
                    'type' => 'object',
                    'description' => 'Custom HTTP headers (e.g. Authorization keys).',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function execute(array $input): string
    {
        $url = $input['url'];
        $method = strtoupper($input['method'] ?? 'POST');
        $data = $input['data'] ?? [];
        $headers = $input['headers'] ?? [];

        try {
            Log::info("Agent executing webhook: $method $url");

            $response = Http::withHeaders($headers)
                ->send($method, $url, [
                    'json' => $data
                ]);

            $status = $response->status();
            $body = $response->body();

            if ($response->successful()) {
                return "SUCCESS (Status $status): " . substr($body, 0, 1000) . (strlen($body) > 1000 ? '...' : '');
            } else {
                return "FAILED (Status $status): " . $body;
            }
        } catch (\Exception $e) {
            Log::error("WebhookTool Error: " . $e->getMessage());
            return "ERROR: " . $e->getMessage();
        }
    }
}
