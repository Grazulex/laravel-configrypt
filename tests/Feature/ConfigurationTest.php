<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use Orchestra\Testbench\TestCase;

class ConfigurationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelConfigryptServiceProvider::class];
    }

    public function test_default_configuration_values(): void
    {
        $this->assertSame('ENC:', config('configrypt.prefix'));
        $this->assertSame('AES-256-CBC', config('configrypt.cipher'));
        $this->assertTrue(config('configrypt.auto_decrypt'));
    }

    public function test_environment_configuration_override(): void
    {
        // Set environment variables
        $_ENV['CONFIGRYPT_KEY'] = 'custom-key-123456789012345678901';
        $_ENV['CONFIGRYPT_PREFIX'] = 'ENCRYPT:';
        $_ENV['CONFIGRYPT_CIPHER'] = 'AES-128-CBC';
        $_ENV['CONFIGRYPT_AUTO_DECRYPT'] = 'false';

        // Reload configuration
        $this->app['config']->set('configrypt.key', env('CONFIGRYPT_KEY'));
        $this->app['config']->set('configrypt.prefix', env('CONFIGRYPT_PREFIX', 'ENC:'));
        $this->app['config']->set('configrypt.cipher', env('CONFIGRYPT_CIPHER', 'AES-256-CBC'));
        $this->app['config']->set('configrypt.auto_decrypt', env('CONFIGRYPT_AUTO_DECRYPT', true));

        $this->assertSame('custom-key-123456789012345678901', config('configrypt.key'));
        $this->assertSame('ENCRYPT:', config('configrypt.prefix'));
        $this->assertSame('AES-128-CBC', config('configrypt.cipher'));
        $this->assertFalse(config('configrypt.auto_decrypt'));

        // Clean up
        unset($_ENV['CONFIGRYPT_KEY'], $_ENV['CONFIGRYPT_PREFIX'],
            $_ENV['CONFIGRYPT_CIPHER'], $_ENV['CONFIGRYPT_AUTO_DECRYPT']);
    }

    public function test_app_key_fallback(): void
    {
        $_ENV['APP_KEY'] = 'fallback-key-1234567890123456789';
        unset($_ENV['CONFIGRYPT_KEY']);

        $this->app['config']->set('configrypt.key', env('CONFIGRYPT_KEY', env('APP_KEY')));

        $this->assertSame('fallback-key-1234567890123456789', config('configrypt.key'));

        // Clean up
        unset($_ENV['APP_KEY']);
    }

    public function test_service_uses_configuration(): void
    {
        $this->app['config']->set('configrypt.key', 'test-config-key-12345678901234567');
        $this->app['config']->set('configrypt.prefix', 'CONFIG:');
        $this->app['config']->set('configrypt.cipher', 'AES-256-CBC');

        $service = $this->app->make(ConfigryptService::class);

        $this->assertSame('CONFIG:', $service->getPrefix());
        $this->assertSame('test-config-key-12345678901234567', $service->getKey());
    }

    public function test_configuration_merge(): void
    {
        // Test that the package configuration is properly merged
        $this->assertIsArray(config('configrypt'));
        $this->assertArrayHasKey('key', config('configrypt'));
        $this->assertArrayHasKey('prefix', config('configrypt'));
        $this->assertArrayHasKey('cipher', config('configrypt'));
        $this->assertArrayHasKey('auto_decrypt', config('configrypt'));
    }
}
