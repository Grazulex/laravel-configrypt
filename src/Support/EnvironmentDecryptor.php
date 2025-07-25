<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Support;

use Exception;
use LaravelConfigrypt\Services\ConfigryptService;

class EnvironmentDecryptor
{
    public function __construct(
        protected ConfigryptService $configryptService,
        protected string $prefix = 'ENC:'
    ) {}

    /**
     * Get and decrypt an environment variable if needed.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key) ?: $default;

        if (is_string($value) && str_starts_with($value, $this->prefix)) {
            try {
                return $this->configryptService->decrypt($value);
            } catch (Exception $e) {
                if (config('app.debug')) {
                    report($e);
                }

                return $default;
            }
        }

        return $value;
    }

    /**
     * Check if a value appears to be encrypted.
     */
    public function isEncrypted(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, $this->prefix);
    }

    /**
     * Decrypt all encrypted environment variables in place.
     *
     * This modifies $_ENV and getenv() but cannot affect Laravel's env() cache.
     */
    public function decryptAll(): void
    {
        foreach ($_ENV as $key => $value) {
            if ($this->isEncrypted($value)) {
                try {
                    $decrypted = $this->configryptService->decrypt($value);
                    $_ENV[$key] = $decrypted;
                    putenv("{$key}={$decrypted}");
                } catch (Exception $e) {
                    if (config('app.debug')) {
                        report($e);
                    }
                }
            }
        }
    }

    /**
     * Get all environment variables with encrypted values decrypted.
     *
     * @return array<string, mixed>
     */
    public function getAllDecrypted(): array
    {
        $decrypted = [];

        foreach (array_keys($_ENV) as $key) {
            $decrypted[$key] = $this->get($key);
        }

        return $decrypted;
    }
}
