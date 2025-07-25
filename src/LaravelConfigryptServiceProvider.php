<?php

declare(strict_types=1);

namespace LaravelConfigrypt;

use Exception;
use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LaravelConfigrypt\Commands\DecryptCommand;
use LaravelConfigrypt\Commands\EncryptCommand;
use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Support\EnvironmentDecryptor;
use Override;
use ReflectionClass;

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

        // Decrypt environment variables as early as possible
        // Use env() directly since config might not be fully loaded yet
        $autoDecrypt = $_ENV['CONFIGRYPT_AUTO_DECRYPT'] ?? 'true';
        if ($autoDecrypt === 'true' || $autoDecrypt === true || $autoDecrypt === '1') {
            $this->earlyAutoDecryptEnvironmentVariables();
        }

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

    /**
     * Early environment decryption during the register phase.
     */
    protected function earlyAutoDecryptEnvironmentVariables(): void
    {
        // Use basic configuration values since config might not be fully loaded yet
        $configryptKey = $_ENV['CONFIGRYPT_KEY'] ?? $_ENV['APP_KEY'] ?? null;
        $prefix = $_ENV['CONFIGRYPT_PREFIX'] ?? 'ENC:';

        if (! $configryptKey) {
            return; // Can't decrypt without a key
        }

        // Remove base64: prefix if present
        if (str_starts_with((string) $configryptKey, 'base64:')) {
            $configryptKey = base64_decode(substr((string) $configryptKey, 7));
        }

        try {
            // Create a minimal service instance for decryption
            $tempService = new ConfigryptService(
                key: $configryptKey,
                prefix: $prefix,
                cipher: 'AES-256-CBC'
            );

            foreach ($_ENV as $key => $value) {
                if (is_string($value) && str_starts_with($value, (string) $prefix)) {
                    try {
                        $decryptedValue = $tempService->decrypt($value);

                        // Update all possible sources
                        $_ENV[$key] = $decryptedValue;
                        $_SERVER[$key] = $decryptedValue;
                        putenv("{$key}={$decryptedValue}");

                        // Force Laravel to clear its environment cache
                        $this->clearLaravelEnvironmentCache($key);
                    } catch (Exception) {
                        // Silently ignore decryption failures in early stage
                    }
                }
            }
        } catch (Exception) {
            // Silently ignore service creation failures in early stage
        }
    }

    /**
     * Force Laravel to clear its internal environment cache for a specific key.
     */
    protected function clearLaravelEnvironmentCache(string $key): void
    {
        try {
            // Try to access Laravel's Repository instance directly
            $repo = $this->app->has('env') ? $this->app->make('env') : null;
            if ($repo && is_object($repo) && method_exists($repo, 'forget')) {
                $repo->forget($key);
            }

            // Also try to clear from config cache if it exists
            if ($this->app->has('config')) {
                $config = $this->app->make('config');
                if (is_object($config) && method_exists($config, 'forget')) {
                    // Clear any config that might be using this env var
                    $config->forget("env.{$key}");
                }
            }

            // Use reflection to access private properties if needed
            $this->forceResetEnvironmentCache();
        } catch (Exception) {
            // Ignore errors in cache clearing
        }
    }

    /**
     * Use reflection to force reset Laravel's environment cache.
     */
    protected function forceResetEnvironmentCache(): void
    {
        try {
            // Method 1: Clear static repository in Env class
            if (class_exists(Env::class)) {
                $envClass = new ReflectionClass(Env::class);
                if ($envClass->hasProperty('repository')) {
                    $repoProperty = $envClass->getProperty('repository');
                    $repoProperty->setAccessible(true);
                    $repoProperty->setValue(null, null); // Reset to null to force re-reading
                }
            }

            // Method 2: Directly override the env() function behavior by creating a new Repository
            if (function_exists('env') && class_exists(Env::class)) {
                // Force Env to reinitialize by setting a new repository
                $this->reinitializeEnvRepository();
            }
        } catch (Exception) {
            // Ignore reflection errors
        }
    }

    /**
     * Force reinitialize the Env repository to read from updated $_ENV.
     */
    protected function reinitializeEnvRepository(): void
    {
        try {
            if (class_exists(Env::class)) {
                $envClass = new ReflectionClass(Env::class);

                // Get the getRepository method
                if ($envClass->hasMethod('getRepository')) {
                    $getRepoMethod = $envClass->getMethod('getRepository');
                    $getRepoMethod->setAccessible(true);

                    // Force it to create a new repository
                    if ($envClass->hasProperty('repository')) {
                        $repoProperty = $envClass->getProperty('repository');
                        $repoProperty->setAccessible(true);
                        $repoProperty->setValue(null, null);

                        // Call getRepository to recreate it
                        $getRepoMethod->invoke(null);
                    }
                }
            }
        } catch (Exception) {
            // Ignore reflection errors
        }
    }
}
