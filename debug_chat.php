<?php

use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = env('OPENAI_API_KEY');
$baseUrl = env('OPENAI_BASE_URL', 'https://ollama.com');
$model = env('AGENT_MODEL', 'llama3.2');

echo "Testing Chat API...\n";
echo "Base URL: $baseUrl\n";
echo "Model: $model\n";

// OpenAI compatible path
$endpoint = rtrim($baseUrl, '/') . '/v1/chat/completions';
echo "Trying Endpoint: $endpoint\n";

try {
    $response = Http::withToken($apiKey)
        ->post($endpoint, [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ]);

    echo "Status: " . $response->status() . "\n";
    echo "Body: " . substr($response->body(), 0, 500) . "...\n";

    if ($response->status() === 404) {
        echo "\nEndpoint 404! Trying Ollama native path /api/chat...\n";
        $endpoint = rtrim($baseUrl, '/') . '/api/chat';
        echo "Trying Endpoint: $endpoint\n";
        
        $response = Http::withToken($apiKey)
            ->post($endpoint, [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => 'Hi']],
                'stream' => false,
            ]);
            
        echo "Status: " . $response->status() . "\n";
        echo "Body: " . substr($response->body(), 0, 500) . "...\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
