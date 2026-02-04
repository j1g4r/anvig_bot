<?php

namespace App\Services;

use App\Models\SmartDevice;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SmartHomeService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 10]);
    }

    /**
     * Send a command to a device.
     */
    public function sendCommand(SmartDevice $device, string $action, array $params = []): array
    {
        try {
            $result = match ($device->protocol) {
                'mqtt' => $this->sendMqttCommand($device, $action, $params),
                'http' => $this->sendHttpCommand($device, $action, $params),
                'home_assistant' => $this->sendHomeAssistantCommand($device, $action, $params),
                default => ['success' => false, 'error' => 'Unknown protocol'],
            };

            if ($result['success']) {
                $this->updateState($device, $action, $params);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("SmartHome command failed: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * MQTT command via HTTP bridge (e.g., Zigbee2MQTT, Tasmota).
     */
    protected function sendMqttCommand(SmartDevice $device, string $action, array $params): array
    {
        $config = $device->config;
        $topic = $config['topic'] ?? '';
        $bridgeUrl = $config['bridge_url'] ?? env('MQTT_BRIDGE_URL', 'http://localhost:8080');

        $payload = match ($action) {
            'turn_on' => ['state' => 'ON'],
            'turn_off' => ['state' => 'OFF'],
            'toggle' => ['state' => 'TOGGLE'],
            'set_brightness' => ['brightness' => $params['brightness'] ?? 100],
            'set_temperature' => ['temperature' => $params['temperature'] ?? 22],
            default => $params,
        };

        $response = $this->http->post("{$bridgeUrl}/api/mqtt/publish", [
            'json' => [
                'topic' => "{$topic}/set",
                'payload' => $payload,
            ],
        ]);

        return ['success' => $response->getStatusCode() === 200];
    }

    /**
     * Direct HTTP command (REST API devices).
     */
    protected function sendHttpCommand(SmartDevice $device, string $action, array $params): array
    {
        $config = $device->config;
        $baseUrl = $config['base_url'] ?? '';
        $headers = $config['headers'] ?? [];

        $endpoint = $config['endpoints'][$action] ?? null;
        if (!$endpoint) {
            return ['success' => false, 'error' => "No endpoint for action: {$action}"];
        }

        $response = $this->http->request(
            $endpoint['method'] ?? 'POST',
            $baseUrl . ($endpoint['path'] ?? ''),
            [
                'headers' => $headers,
                'json' => array_merge($endpoint['body'] ?? [], $params),
            ]
        );

        return ['success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300];
    }

    /**
     * Home Assistant REST API.
     */
    protected function sendHomeAssistantCommand(SmartDevice $device, string $action, array $params): array
    {
        $config = $device->config;
        $haUrl = $config['ha_url'] ?? env('HOME_ASSISTANT_URL', 'http://homeassistant.local:8123');
        $token = $config['token'] ?? env('HOME_ASSISTANT_TOKEN', '');
        $entityId = $config['entity_id'] ?? '';

        $service = match ($action) {
            'turn_on' => 'turn_on',
            'turn_off' => 'turn_off',
            'toggle' => 'toggle',
            default => $action,
        };

        $domain = explode('.', $entityId)[0] ?? 'light';

        $response = $this->http->post("{$haUrl}/api/services/{$domain}/{$service}", [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ],
            'json' => array_merge(['entity_id' => $entityId], $params),
        ]);

        return ['success' => $response->getStatusCode() === 200];
    }

    /**
     * Update device state after command.
     */
    protected function updateState(SmartDevice $device, string $action, array $params): void
    {
        $state = $device->state ?? [];

        if (in_array($action, ['turn_on', 'toggle'])) {
            $state['on'] = true;
        } elseif ($action === 'turn_off') {
            $state['on'] = false;
        }

        if (isset($params['brightness'])) {
            $state['brightness'] = $params['brightness'];
        }
        if (isset($params['temperature'])) {
            $state['temperature'] = $params['temperature'];
        }

        $device->update([
            'state' => $state,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Get all devices for a user.
     */
    public function getDevices(int $userId): array
    {
        return SmartDevice::where('user_id', $userId)
            ->orderBy('room')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Find device by name (for natural language).
     */
    public function findDeviceByName(int $userId, string $name): ?SmartDevice
    {
        return SmartDevice::where('user_id', $userId)
            ->where(function ($q) use ($name) {
                $q->where('name', 'LIKE', "%{$name}%")
                  ->orWhere('room', 'LIKE', "%{$name}%");
            })
            ->first();
    }
}
