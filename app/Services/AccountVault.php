<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;

class AccountVault
{
    protected function getCurrentEncrypter(): Encrypter
    {
        $key = $this->decodeKey(config('account_vault.key'));

        return new Encrypter($key, config('account_vault.cipher'));
    }

    protected function decodeKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7), true);
        }

        return $key;
    }

    public function encrypt(string $value): string
    {
        return $this->getCurrentEncrypter()->encryptString($value);
    }

    public function decrypt(string $payload): string
    {
        $encrypters = [
            $this->getCurrentEncrypter(),
        ];
        foreach ($encrypters as $encrypter) {
            try {
                return $encrypter->decryptString($payload);
            } catch (DecryptException $e) {
                // try next key
            }
        }

        throw new DecryptException('Unable to decrypt account vault value.');
    }
}