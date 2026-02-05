<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trading Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Global settings for the autonomous trading engine.
    |
    */

    // Enable/disable all trading operations
    'enabled' => env('TRADING_ENABLED', true),

    // Paper trading mode (no real trades executed)
    'paper_mode' => env('TRADING_PAPER_MODE', true),

    // Strategy execution settings
    'strategies' => [
        'enabled' => env('TRADING_STRATEGIES_ENABLED', true),
        'execution_interval' => env('TRADING_INTERVAL', 300), // seconds
        'max_positions' => 5,
        'max_daily_trades' => 10,
    ],

    // Risk management
    'risk' => [
        'confidence_threshold' => 0.70,
        'max_position_size_pct' => 0.10, // 10% of portfolio per position
        'max_total_exposure_pct' => 0.50, // 50% of portfolio exposed
        'stop_loss_pct' => 0.05,
        'daily_loss_limit_pct' => 0.10,
    ],

    // Kelly Criterion defaults
    'kelly' => [
        'fraction' => 0.25, // Half-Kelly for safety
        'min_edge' => 0.02, // Minimum expected edge to enter
        'max_win_prob' => 0.85,
    ],

    // Market data providers (priority order)
    'providers' => [
        'binance' => [
            'enabled' => env('BINANCE_ENABLED', false),
        ],
        'coinbase' => [
            'enabled' => env('COINBASE_ENABLED', false),
        ],
        'kraken' => [
            'enabled' => env('KRAKEN_ENABLED', false),
        ],
        'alphavantage' => [
            'enabled' => env('ALPHAVANTAGE_ENABLED', true),
            'api_key' => env('ALPHAVANTAGE_API_KEY'),
            'rate_limit' => 5, // calls per minute (free tier)
        ],
    ],

    // Exchange credentials (paper/live mode dependent)
    'exchanges' => [
        'binance' => [
            'api_key' => env('BINANCE_API_KEY'),
            'api_secret' => env('BINANCE_API_SECRET'),
            'testnet' => env('BINANCE_TESTNET', true),
        ],
        'coinbase' => [
            'api_key' => env('COINBASE_API_KEY'),
            'api_secret' => env('COINBASE_API_SECRET'),
        ],
        'kraken' => [
            'api_key' => env('KRAKEN_API_KEY'),
            'api_secret' => env('KRAKEN_API_SECRET'),
        ],
    ],

    // Supported trading pairs
    'pairs' => [
        'crypto' => ['BTC/USD', 'ETH/USD', 'SOL/USD', 'ADA/USD'],
        'traditional' => ['EUR/USD', 'GBP/USD', 'SPY', 'QQQ'],
    ],
];
