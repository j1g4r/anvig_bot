<?php

namespace App\Services\Tools;

use App\Services\TotpService;
use Illuminate\Support\Facades\Auth;

class MfaTool implements ToolInterface
{
    protected TotpService $totpService;

    public function __construct()
    {
        $this->totpService = new TotpService();
    }

    public function name(): string
    {
        return 'mfa';
    }

    public function description(): string
    {
        return 'Manage Multi-Factor Authentication (MFA) codes. Use this tool to generate TOTP codes for registered services, verify codes, list services, or register new ones.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['generate', 'verify', 'list', 'register', 'remove'],
                    'description' => 'The action to perform: generate a code, verify a code, list services, register a new service, or remove a service.',
                ],
                'service_name' => [
                    'type' => 'string',
                    'description' => 'The name of the service (e.g., GitHub, AWS, Google).',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'The TOTP code to verify (only for verify action).',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $arguments): string
    {
        $userId = Auth::id() ?? 1; // Default to user 1 in desktop mode
        $action = $arguments['action'] ?? 'list';
        $serviceName = $arguments['service_name'] ?? null;
        $code = $arguments['code'] ?? null;

        switch ($action) {
            case 'generate':
                if (!$serviceName) {
                    return 'Error: service_name is required for generating a code.';
                }
                $generatedCode = $this->totpService->generateCode($userId, $serviceName);
                if ($generatedCode === null) {
                    return "No MFA secret found for service: {$serviceName}. Please register it first.";
                }
                return "Your current code for {$serviceName} is: **{$generatedCode}** (valid for ~30 seconds)";

            case 'verify':
                if (!$serviceName || !$code) {
                    return 'Error: service_name and code are required for verification.';
                }
                $isValid = $this->totpService->verifyCode($userId, $serviceName, $code);
                return $isValid
                    ? "✅ Code verified successfully for {$serviceName}!"
                    : "❌ Invalid code for {$serviceName}. Please try again.";

            case 'list':
                $services = $this->totpService->listServices($userId);
                if (empty($services)) {
                    return 'No MFA services registered yet. Use action "register" to add one.';
                }
                return "Registered MFA services:\n- " . implode("\n- ", $services);

            case 'register':
                if (!$serviceName) {
                    return 'Error: service_name is required for registration.';
                }
                $result = $this->totpService->register($userId, $serviceName);
                return "Registered MFA for {$serviceName}!\n\n**Secret (save this):** `{$result['secret']}`\n\n**QR URI:** {$result['provisioning_uri']}";

            case 'remove':
                if (!$serviceName) {
                    return 'Error: service_name is required for removal.';
                }
                $removed = $this->totpService->removeService($userId, $serviceName);
                return $removed
                    ? "Removed MFA registration for {$serviceName}."
                    : "No MFA registration found for {$serviceName}.";

            default:
                return "Unknown action: {$action}. Supported: generate, verify, list, register, remove.";
        }
    }
}
