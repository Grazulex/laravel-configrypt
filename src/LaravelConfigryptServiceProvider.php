<?php

declare(strict_types=1);

namespace LaravelConfigrypt;

use Illuminate\Support\ServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;

class LaravelConfigryptServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/configrypt.php', 'configrypt'
        );

        $this->app->singleton(ConfigryptService::class, function ($app) {
            return new ConfigryptService(
                key: config('configrypt.key'),
                prefix: config('configrypt.prefix', 'ENC:'),
                cipher: config('configrypt.cipher', 'AES-256-CBC')
            );
        });

        $this->app->alias(ConfigryptService::class, 'configrypt');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/configrypt.php' => config_path('configrypt.php'),
        ], 'configrypt-config');

        if (config('configrypt.auto_decrypt', true)) {
            $this->autoDecryptEnvironmentVariables();
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\EncryptCommand::class,
                Commands\DecryptCommand::class,
            ]);
        }
    }

    /**
     * Automatically decrypt environment variables with the ENC: prefix.
     */
    protected function autoDecryptEnvironmentVariables(): void
    {
        $configryptService = $this->app->make(ConfigryptService::class);
        $prefix = config('configrypt.prefix', 'ENC:');

        foreach ($_ENV as $key => $value) {
            if (is_string($value) && str_starts_with($value, $prefix)) {
                try {
                    $decryptedValue = $configryptService->decrypt($value);
                    $_ENV[$key] = $decryptedValue;
                    putenv("{$key}={$decryptedValue}");
                } catch (\Exception $e) {
                    // Log error or handle silently - don't break the application
                    if (config('app.debug')) {
                        report($e);
                    }
                }
            }
        }
    }
}
