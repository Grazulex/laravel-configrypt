<?php

declare(strict_types=1);

use LaravelConfigrypt\Services\ConfigryptService;

if (! function_exists('configrypt_env')) {
    /**
     * Get an encrypted environment variable and decrypt it automatically.
     *
     * This helper works around Laravel's env() cache limitations by
     * checking and decrypting encrypted values on-demand.
     */
    function configrypt_env(string $key, mixed $default = null): mixed
    {
        /** @phpstan-ignore-next-line larastan.noEnvCallsOutsideOfConfig */
        $value = env($key, $default);

        // If value is a string and looks encrypted, decrypt it
        if (is_string($value) && str_starts_with($value, (string) config('configrypt.prefix', 'ENC:'))) {
            try {
                return app(ConfigryptService::class)->decrypt($value);
            } catch (Exception $e) {
                // If decryption fails, return original value or default
                if (config('app.debug')) {
                    report($e);
                }

                return $default;
            }
        }

        return $value;
    }
}

if (! function_exists('encrypted_env')) {
    /**
     * Alias for configrypt_env() - shorter helper name.
     */
    function encrypted_env(string $key, mixed $default = null): mixed
    {
        return configrypt_env($key, $default);
    }
}
