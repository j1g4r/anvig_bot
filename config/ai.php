<?php

declare(strict_types=1);

return [
    'default' => env('AI_PROVIDER', 'ollama'),
    
    'providers' => [
        'ollama' => [
            'url' => env('OLLAMA_URL', 'http://localhost:11434'),
            'vision_model' => env('OLLAMA_VISION_MODEL', 'llama3.2-vision'),
            'chat_model' => env('OLLAMA_CHAT_MODEL', 'llama3.2'),
            'embeddings_model' => env('OLLAMA_EMBEDDINGS_MODEL', 'nomic-embed-text'),
            'timeout' => 60,
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
            'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
            'embeddings_model' => env('OPENAI_EMBEDDINGS_MODEL', 'text-embedding-3-small'),
        ],
    ],
    
    'streaming' => [
        'default_fps' => 5,
        'analysis_interval' => 2,
        'buffer_window' => 30,
    ],
    
    'features' => [
        'autonomous_healing' => true,
        'self_diagnosis' => true,
        'model_fallback' => true,
    ],
];
