<?php

declare(strict_types=1);

namespace LaravelConfigrypt;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LaravelConfigrypt\Commands\DecryptCommand;
use LaravelConfigrypt\Commands\EncryptCommand;
use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Support\EnvironmentDecryptor;
use Override;

class LaravelConfigryptServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/configrypt.php', 'configrypt'
        );

        // Note: Auto-decryption removed due to Laravel's env() caching limitations
        // Use configrypt_env() or encrypted_env() helper functions instead

        $this->app->singleton(ConfigryptService::class, function ($app): ConfigryptService {
            $key = config('configrypt.key');

            return new ConfigryptService(
                key: $key,
                prefix: config('configrypt.prefix', 'ENC:'),
                cipher: config('configrypt.cipher', 'AES-256-CBC')
            );
        });

        $this->app->singleton(EnvironmentDecryptor::class, fn ($app): EnvironmentDecryptor => new EnvironmentDecryptor(
            $app->make(ConfigryptService::class),
            config('configrypt.prefix', 'ENC:')
        ));

        $this->app->alias(ConfigryptService::class, 'configrypt');
        $this->app->alias(EnvironmentDecryptor::class, 'configrypt.env');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/Config/configrypt.php' => config_path('configrypt.php'),
        ], 'configrypt-config');

        // Add a macro to make migration easier for developers
        $this->addConfigryptMacros();

        if ($this->app->runningInConsole()) {
            $this->commands([
                EncryptCommand::class,
                DecryptCommand::class,
            ]);
        }
    }

    /**
     * Add helpful macros to make the transition easier for developers.
     */
    protected function addConfigryptMacros(): void
    {
        // Add a macro to the Str class for easy decryption
        if (class_exists(Str::class)) {
            Str::macro('decryptEnv', function (string $key, $default = null) {
                /** @phpstan-ignore-next-line larastan.noEnvCallsOutsideOfConfig */
                $rawValue = env($key, $default);

                return app(ConfigryptService::class)->isEncrypted($rawValue)
                    ? app(ConfigryptService::class)->decrypt($rawValue)
                    : $rawValue;
            });
        }
    }
}
