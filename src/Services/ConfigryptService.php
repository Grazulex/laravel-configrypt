<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;

class ConfigryptService
{
    protected Encrypter $encrypter;

    /**
     * Encrypters built from the previous keys, only used for decryption.
     *
     * @var array<int, Encrypter>
     */
    protected array $previousEncrypters = [];

    /**
     * The previous keys, in the order they are tried.
     *
     * @var array<int, string>
     */
    protected array $previousKeys = [];

    /**
     * @param  array<int|string, mixed>|string|null  $previousKeys  Keys that are only used to decrypt values
     *                                                              encrypted before a key rotation. Either an
     *                                                              array or a comma-separated string.
     */
    public function __construct(
        protected ?string $key,
        protected string $prefix = 'ENC:',
        string $cipher = 'AES-256-CBC',
        array|string|null $previousKeys = null
    ) {
        // For PHPStan analysis, use a dummy key if no key is provided
        if (in_array($key, [null, '', '0'], true)) {
            if (defined('PHPSTAN_ANALYSIS')) {
                $key = str_repeat('a', 32); // Dummy key for analysis
            } else {
                throw new InvalidArgumentException('Encryption key cannot be empty. Please set CONFIGRYPT_KEY or APP_KEY.');
            }
        }

        $this->encrypter = $this->makeEncrypter($key, $cipher);

        foreach (self::normalizeKeys($previousKeys) as $previousKey) {
            if ($previousKey === $key) {
                continue;
            }

            if (in_array($previousKey, $this->previousKeys, true)) {
                continue;
            }

            $this->previousKeys[] = $previousKey;
            $this->previousEncrypters[] = $this->makeEncrypter($previousKey, $cipher);
        }
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
     *
     * The current key is tried first, then each previous key in order.
     * When no key can decrypt the value, the error of the current key is thrown.
     */
    public function decrypt(string $encryptedValue): string
    {
        // Remove prefix if present
        if (str_starts_with($encryptedValue, $this->prefix)) {
            $encryptedValue = substr($encryptedValue, strlen($this->prefix));
        }

        try {
            return $this->encrypter->decrypt($encryptedValue);
        } catch (DecryptException $decryptException) {
            foreach ($this->previousEncrypters as $previousEncrypter) {
                try {
                    return $previousEncrypter->decrypt($encryptedValue);
                } catch (DecryptException) {
                    // Try the next previous key
                }
            }

            throw $decryptException;
        }
    }

    /**
     * Decrypt a value with this service and encrypt it again with another one.
     *
     * Used for key rotation: this service holds the current key and the
     * target service holds the new key.
     */
    public function reEncrypt(string $encryptedValue, self $target): string
    {
        return $target->encrypt($this->decrypt($encryptedValue));
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
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Get the previous keys accepted for decryption.
     *
     * @return array<int, string>
     */
    public function getPreviousKeys(): array
    {
        return $this->previousKeys;
    }

    /**
     * Normalize a list of keys given as an array or a comma-separated string.
     *
     * Empty entries and non-string values are ignored.
     *
     * @param  array<int|string, mixed>|string|null  $keys
     *
     * @return array<int, string>
     */
    public static function normalizeKeys(array|string|null $keys): array
    {
        if ($keys === null) {
            return [];
        }

        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }

        $normalized = [];

        foreach ($keys as $key) {
            if (! is_string($key)) {
                continue;
            }

            $key = trim($key);

            if ($key === '') {
                continue;
            }

            if ($key === '0') {
                continue;
            }

            $normalized[] = $key;
        }

        return $normalized;
    }

    protected function makeEncrypter(string $key, string $cipher): Encrypter
    {
        // Ensure the key is the right length for the cipher
        if ($cipher === 'AES-256-CBC' && strlen($key) !== 32) {
            // Generate a proper key from the provided key
            $key = hash('sha256', $key, true);
        }

        return new Encrypter($key, $cipher);
    }
}
