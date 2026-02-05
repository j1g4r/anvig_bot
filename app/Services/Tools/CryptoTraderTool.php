<?php

namespace App\Services\Tools;

class CryptoTraderTool implements ToolInterface
{
    public function name(): string
    {
        return 'crypto_trader';
    }

    public function description(): string
    {
        return "Crypto trading actions. Get prices or check balance. Actions: 'get_price', 'check_balance'.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => ['type' => 'string'],
                'symbol' => ['type' => 'string']
            ],
            'required' => ['action']
        ];
    }

    public function execute(array $input): string
    {
        $action = $input['action'] ?? 'check_balance';
        $symbol = $input['symbol'] ?? 'BTC';

        return json_encode([
            'status' => 'simulated_success',
            'data' => [
                'symbol' => $symbol,
                'price' => rand(40000, 60000), // Mock price
                'balance' => 0.5 // Mock balance
            ]
        ]);
    }
}
