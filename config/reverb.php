<?php

declare(strict_types=1);

return [
    'default' => env('REVERB_SERVER', 'reverb'),

    'pulse_ingest_interval' => 15,
    'telescope_ingest_interval' => 15,

    'servers' => [
        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => 10 * 1024, // 10KB buffer limit
            'ping_interval' => 30,
            'pulse_ingest_interval' => 15,
            'telescope_ingest_interval' => 15,
            'max_message_size' => 10000,
            'scaling' => [
                'enabled' => false,
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                'server' => [
                    'url' => env('REDIS_URL'),
                    'host' => '127.0.0.1',
                    'port' => '6379',
                    'username' => null,
                    'password' => null,
                    'database' => '0',
                ],
            ],
        ],
    ],

    'apps' => [
        'provider' => 'config',
        'apps' => [
            [
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'app_id' => env('REVERB_APP_ID'),
                'options' => [
                    'host' => env('REVERB_HOST'),
                    'port' => (int) env('REVERB_PORT', 80),
                    'scheme' => env('REVERB_SCHEME', 'https'),
                    'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
                ],
                'ping_interval' => 60,
                'allowed_origins' => ['*'],
                'enable_client_messages' => false,
                'max_presence_members_per_channel' => 100,
                'ping_interval' => 60,
                'max_message_size' => 10000,
            ],
        ],
    ],
];
