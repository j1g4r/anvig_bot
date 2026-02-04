<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class MfaSecret extends Model
{
    protected $fillable = [
        'user_id',
        'service_name',
        'encrypted_secret',
        'issuer',
    ];

    protected $hidden = [
        'encrypted_secret',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Decrypt and return the TOTP secret.
     */
    public function getSecret(): string
    {
        return Crypt::decryptString($this->encrypted_secret);
    }

    /**
     * Encrypt and store the TOTP secret.
     */
    public function setSecretAttribute(string $value): void
    {
        $this->attributes['encrypted_secret'] = Crypt::encryptString($value);
    }
}
