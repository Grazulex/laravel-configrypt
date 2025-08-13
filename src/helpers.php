<?php

declare(strict_types=1);

use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Support\ConfigValue;

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

if (! function_exists('configrypt_value')) {
    /**
     * Create a lazy configuration value that defers decryption until runtime.
     *
     * This is specifically designed for use in configuration files to solve
     * the config:cache problem where encrypted values would be decrypted and
     * stored in plain text on disk.
     *
     * Usage in config files:
     * 'password' => configrypt_value('ENC:encrypted-value'),
     * 'api_key' => configrypt_value(env('API_KEY')),
     */
    function configrypt_value(string $value, mixed $default = null): ConfigValue
    {
        return new ConfigValue($value, $default);
    }
}
