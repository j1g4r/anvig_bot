<?php

namespace App\Services\Tools;

use App\Services\SmartHomeService;
use Illuminate\Support\Facades\Auth;

class SmartHomeTool implements ToolInterface
{
    protected SmartHomeService $smartHomeService;

    public function __construct()
    {
        $this->smartHomeService = new SmartHomeService();
    }

    public function name(): string
    {
        return 'smart_home';
    }

    public function description(): string
    {
        return 'Control smart home devices like lights, switches, thermostats, and locks. Use this when the user wants to turn on/off lights, adjust temperature, check device status, or control any IoT device.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['turn_on', 'turn_off', 'toggle', 'set_brightness', 'set_temperature', 'status', 'list'],
                    'description' => 'The action to perform.',
                ],
                'device_name' => [
                    'type' => 'string',
                    'description' => 'Name or room of the device (e.g., "living room light", "bedroom", "thermostat").',
                ],
                'value' => [
                    'type' => 'number',
                    'description' => 'Value for brightness (0-100) or temperature.',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $arguments): string
    {
        $action = $arguments['action'] ?? 'list';
        $deviceName = $arguments['device_name'] ?? '';
        $value = $arguments['value'] ?? null;
        $userId = Auth::id() ?? 1;

        if ($action === 'list') {
            return $this->listDevices($userId);
        }

        if ($action === 'status' && empty($deviceName)) {
            return $this->listDevices($userId);
        }

        if (empty($deviceName)) {
            return 'Error: Please specify which device you want to control.';
        }

        $device = $this->smartHomeService->findDeviceByName($userId, $deviceName);
        if (!$device) {
            return "Error: Could not find a device matching '{$deviceName}'. Use 'list' to see available devices.";
        }

        if ($action === 'status') {
            return $this->getDeviceStatus($device);
        }

        $params = [];
        if ($action === 'set_brightness' && $value !== null) {
            $params['brightness'] = (int) $value;
        }
        if ($action === 'set_temperature' && $value !== null) {
            $params['temperature'] = (float) $value;
        }

        $result = $this->smartHomeService->sendCommand($device, $action, $params);

        if ($result['success']) {
            $actionText = match ($action) {
                'turn_on' => 'turned on',
                'turn_off' => 'turned off',
                'toggle' => 'toggled',
                'set_brightness' => "brightness set to {$value}%",
                'set_temperature' => "temperature set to {$value}°",
                default => $action,
            };
            return "✅ {$device->name} has been {$actionText}.";
        }

        return "❌ Failed to control {$device->name}: " . ($result['error'] ?? 'Unknown error');
    }

    protected function listDevices(int $userId): string
    {
        $devices = $this->smartHomeService->getDevices($userId);

        if (empty($devices)) {
            return "No smart home devices registered. Add devices at /smart-home.";
        }

        $output = "Smart Home Devices:\n\n";
        $currentRoom = '';

        foreach ($devices as $device) {
            if ($device['room'] !== $currentRoom) {
                $currentRoom = $device['room'] ?: 'Unassigned';
                $output .= "📍 {$currentRoom}\n";
            }

            $status = ($device['state']['on'] ?? false) ? '🟢 ON' : '⚪ OFF';
            $output .= "  • {$device['name']} ({$device['type']}) - {$status}\n";
        }

        return $output;
    }

    protected function getDeviceStatus($device): string
    {
        $status = $device->isOn() ? '🟢 ON' : '⚪ OFF';
        $output = "{$device->name} is {$status}";

        if (isset($device->state['brightness'])) {
            $output .= " at {$device->state['brightness']}% brightness";
        }
        if (isset($device->state['temperature'])) {
            $output .= " set to {$device->state['temperature']}°";
        }
        if ($device->last_seen_at) {
            $output .= " (last seen: {$device->last_seen_at->diffForHumans()})";
        }

        return $output;
    }
}
