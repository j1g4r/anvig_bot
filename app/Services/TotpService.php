<?php

namespace App\Services;

use App\Models\MfaSecret;
use Illuminate\Support\Facades\Crypt;
use OTPHP\TOTP;

class TotpService
{
    /**
     * Register a new MFA service for a user.
     * Returns the secret and provisioning URI for QR code.
     */
    public function register(int $userId, string $serviceName, ?string $issuer = 'Anvig'): array
    {
        // Generate a new TOTP secret
        $totp = TOTP::generate();
        $totp->setLabel($serviceName);
        $totp->setIssuer($issuer);

        $secret = $totp->getSecret();

        // Store encrypted secret
        MfaSecret::updateOrCreate(
            ['user_id' => $userId, 'service_name' => $serviceName],
            [
                'encrypted_secret' => Crypt::encryptString($secret),
                'issuer' => $issuer,
            ]
        );

        return [
            'secret' => $secret,
            'provisioning_uri' => $totp->getProvisioningUri(),
        ];
    }

    /**
     * Generate the current TOTP code for a service.
     */
    public function generateCode(int $userId, string $serviceName): ?string
    {
        $mfaSecret = MfaSecret::where('user_id', $userId)
            ->where('service_name', $serviceName)
            ->first();

        if (!$mfaSecret) {
            return null;
        }

        $totp = TOTP::createFromSecret($mfaSecret->getSecret());
        return $totp->now();
    }

    /**
     * Verify a TOTP code for a service.
     */
    public function verifyCode(int $userId, string $serviceName, string $code): bool
    {
        $mfaSecret = MfaSecret::where('user_id', $userId)
            ->where('service_name', $serviceName)
            ->first();

        if (!$mfaSecret) {
            return false;
        }

        $totp = TOTP::createFromSecret($mfaSecret->getSecret());
        return $totp->verify($code);
    }

    /**
     * List all registered services for a user.
     */
    public function listServices(int $userId): array
    {
        return MfaSecret::where('user_id', $userId)
            ->pluck('service_name')
            ->toArray();
    }

    /**
     * Remove a service registration.
     */
    public function removeService(int $userId, string $serviceName): bool
    {
        return MfaSecret::where('user_id', $userId)
            ->where('service_name', $serviceName)
            ->delete() > 0;
    }
}
