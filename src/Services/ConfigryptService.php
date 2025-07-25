<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Services;

use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;

class ConfigryptService
{
    protected Encrypter $encrypter;
    protected string $prefix;

    public function __construct(
        protected string $key,
        string $prefix = 'ENC:',
        string $cipher = 'AES-256-CBC'
    ) {
        $this->prefix = $prefix;
        
        if (empty($key)) {
            throw new InvalidArgumentException('Encryption key cannot be empty. Please set CONFIGRYPT_KEY or APP_KEY.');
        }

        // Ensure the key is the right length for the cipher
        if ($cipher === 'AES-256-CBC' && strlen($key) !== 32) {
            // Generate a proper key from the provided key
            $key = hash('sha256', $key, true);
        }

        $this->encrypter = new Encrypter($key, $cipher);
    }

    /**
     * Encrypt a value with the configured prefix.
     */
    public function encrypt(string $value): string
    {
        $encrypted = $this->encrypter->encrypt($value);
        return $this->prefix . $encrypted;
    }

    /**
     * Decrypt a value, removing the prefix if present.
     */
    public function decrypt(string $encryptedValue): string
    {
        // Remove prefix if present
        if (str_starts_with($encryptedValue, $this->prefix)) {
            $encryptedValue = substr($encryptedValue, strlen($this->prefix));
        }

        return $this->encrypter->decrypt($encryptedValue);
    }

    /**
     * Check if a value is encrypted (has the prefix).
     */
    public function isEncrypted(string $value): bool
    {
        return str_starts_with($value, $this->prefix);
    }

    /**
     * Get the encryption prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the encryption key.
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
