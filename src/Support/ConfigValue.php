<?php

namespace LaravelConfigrypt\Support;

use LaravelConfigrypt\Services\ConfigryptService;
use Stringable;
use Throwable;

/**
 * A lazy configuration value that defers decryption until runtime.
 *
 * This class solves the config:cache problem by:
 * 1. Storing the encrypted value during config caching
 * 2. Only decrypting when the value is actually accessed
 * 3. Never writing decrypted values to disk
 */
class ConfigValue implements Stringable
{
    private ?string $decryptedValue = null;

    private bool $isDecrypted = false;

    public function __construct(
        private string $encryptedValue,
        private ?string $default = null
    ) {}

    /**
     * Get the decrypted value, decrypting on first access.
     */
    public function getValue(): ?string
    {
        if (! $this->isDecrypted) {
            $this->decrypt();
        }

        return $this->decryptedValue;
    }

    /**
     * Decrypt the value using the ConfigryptService.
     */
    private function decrypt(): void
    {
        try {
            // Try to resolve the service - might not be available during config loading
            if (! app()->bound(ConfigryptService::class)) {
                // During testing or early bootstrap, try to create service manually
                $configryptKey = config('configrypt.key') ?? config('app.key');
                $prefix = config('configrypt.prefix', 'ENC:');
                $cipher = config('configrypt.cipher', 'AES-256-CBC');

                // Default key for testing if no config available
                if (! $configryptKey) {
                    $configryptKey = 'test-key-1234567890123456789012';
                }

                $service = new ConfigryptService($configryptKey, $prefix, $cipher);
            } else {
                $service = app(ConfigryptService::class);
            }

            if ($service->isEncrypted($this->encryptedValue)) {
                $this->decryptedValue = $service->decrypt($this->encryptedValue);
            } else {
                $this->decryptedValue = $this->encryptedValue;
            }
        } catch (Throwable) {
            // Fallback to default if decryption fails
            $this->decryptedValue = $this->default;
        }

        $this->isDecrypted = true;
    }

    /**
     * String representation returns the decrypted value.
     */
    public function __toString(): string
    {
        return (string) $this->getValue();
    }

    /**
     * When var_export or config:cache serializes this object,
     * we want to preserve the encrypted value, not decrypt it.
     */
    public function __serialize(): array
    {
        return [
            'encryptedValue' => $this->encryptedValue,
            'default' => $this->default,
            // Don't serialize the decrypted value!
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->encryptedValue = $data['encryptedValue'];
        $this->default = $data['default'] ?? null;
        $this->decryptedValue = null;
        $this->isDecrypted = false;
    }

    /**
     * For older PHP versions and var_export compatibility.
     *
     * @param  array<string, mixed>  $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            $array['encryptedValue'],
            $array['default'] ?? null
        );
    }
}
