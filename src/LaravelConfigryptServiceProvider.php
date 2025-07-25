<?php

declare(strict_types=1);

namespace LaravelConfigrypt;

use Override;
use LaravelConfigrypt\Commands\EncryptCommand;
use LaravelConfigrypt\Commands\DecryptCommand;
use Exception;
use Illuminate\Support\ServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;

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

        $this->app->singleton(ConfigryptService::class, function ($app): ConfigryptService {
            $key = config('configrypt.key');

            return new ConfigryptService(
                key: $key,
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
            __DIR__ . '/Config/configrypt.php' => config_path('configrypt.php'),
        ], 'configrypt-config');

        if (config('configrypt.auto_decrypt', true)) {
            $this->autoDecryptEnvironmentVariables();
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                EncryptCommand::class,
                DecryptCommand::class,
            ]);
        }
    }

    /**
     * Automatically decrypt environment variables with the ENC: prefix.
     */
    protected function autoDecryptEnvironmentVariables(): void
    {
        try {
            $configryptService = $this->app->make(ConfigryptService::class);
            $prefix = config('configrypt.prefix', 'ENC:');

            foreach ($_ENV as $key => $value) {
                if (is_string($value) && str_starts_with($value, (string) $prefix)) {
                    try {
                        $decryptedValue = $configryptService->decrypt($value);
                        $_ENV[$key] = $decryptedValue;
                        putenv("{$key}={$decryptedValue}");
                    } catch (Exception $e) {
                        // Log error or handle silently - don't break the application
                        if (config('app.debug')) {
                            report($e);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Service couldn't be instantiated (likely missing encryption key)
            // This is expected during PHPStan analysis or when keys are not configured
            if (config('app.debug') && ! defined('PHPSTAN_ANALYSIS')) {
                report($e);
            }
        }
    }
}
