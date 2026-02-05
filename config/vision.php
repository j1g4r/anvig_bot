<?php

declare(strict_types=1);

return [
    'ollama' => [
        'base_url'      => env('OLLAMA_HOST', 'http://localhost:11434'),
        'vision_model'  => env('VISION_MODEL', 'llava:7b'),
        'embed_model'   => env('EMBED_MODEL', 'nomic-embed-text'),

        'timeout'       => (int) env('VISION_TIMEOUT', 120),
        'connect_timeout' => 30,

        'retry_attempts'  => 3,
        'retry_backoff'   => [100, 500, 2000],
    ],

    'models' => [
        'default' => 'llama3.2-vision',
        'available' => [
            'llava' => [
                '7b' => ['description' => 'Lightweight vision for general analysis', 'max_image_size' => 20971520],
                '13b' => ['description' => 'Enhanced vision with better reasoning', 'max_image_size' => 20971520],
            ],
            'llama3.2-vision' => [
                'description' => 'Latest Llama vision model', 'max_image_size' => 20971520,
            ],
        ],
    ],

    'stream' => [
        'default_fps'         => 1,
        'max_fps'             => 10,
        'buffer_duration'     => 3,
        'adaptive_sampling'   => true,
        'motion_threshold'    => 0.15,
        'keyframe_interval'   => 5,
        'max_batch_size'      => 1,
    ],

    'processing' => [
        'output_parser' => 'json',
        'store_raw'     => false,
        'confidence_threshold' => 0.6,
    ],

    'limits' => [
        'max_concurrent_streams' => (int) env('VISION_MAX_STREAMS', 5),
        'max_session_duration'   => 3600,
        'rate_limit_per_minute'  => 60,
        'max_image_size_mb'      => 20,
    ],
];
