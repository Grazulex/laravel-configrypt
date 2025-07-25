<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use Orchestra\Testbench\TestCase;

class EnvironmentDecryptionTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelConfigryptServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('configrypt.key', 'test-key-1234567890123456789012');
        $app['config']->set('configrypt.prefix', 'ENC:');
        $app['config']->set('configrypt.cipher', 'AES-256-CBC');
        $app['config']->set('configrypt.auto_decrypt', false); // Disabled by default now
    }

    public function test_auto_decrypt_environment_variables(): void
    {
        // Note: Auto-decrypt has been removed, but test the helper functions work

        // Encrypt a test value
        $service = new ConfigryptService('test-key-1234567890123456789012');
        $encrypted = $service->encrypt('secret-database-password');

        // Simulate encrypted environment variable
        $_ENV['TEST_ENCRYPTED_VAR'] = $encrypted;
        putenv("TEST_ENCRYPTED_VAR={$encrypted}");

        // Re-instantiate service provider (no auto-decrypt anymore)
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Values should remain encrypted in $_ENV and env()
        $this->assertSame($encrypted, $_ENV['TEST_ENCRYPTED_VAR']);
        $this->assertSame($encrypted, env('TEST_ENCRYPTED_VAR'));

        // But helper functions should work
        $this->assertSame('secret-database-password', configrypt_env('TEST_ENCRYPTED_VAR'));

        // Clean up
        unset($_ENV['TEST_ENCRYPTED_VAR']);
        putenv('TEST_ENCRYPTED_VAR');
    }

    public function test_auto_decrypt_ignores_non_encrypted_values(): void
    {
        $plainValue = 'plain-text-value';

        $_ENV['TEST_PLAIN_VAR'] = $plainValue;
        putenv("TEST_PLAIN_VAR={$plainValue}");

        // Re-instantiate service provider
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Plain value should remain unchanged
        $this->assertSame($plainValue, env('TEST_PLAIN_VAR'));
        $this->assertSame($plainValue, $_ENV['TEST_PLAIN_VAR']);

        // Clean up
        unset($_ENV['TEST_PLAIN_VAR']);
        putenv('TEST_PLAIN_VAR');
    }

    public function test_auto_decrypt_handles_invalid_encrypted_values(): void
    {
        // Set an invalid encrypted value
        $invalidEncrypted = 'ENC:invalid-encrypted-data';

        $_ENV['TEST_INVALID_VAR'] = $invalidEncrypted;
        putenv("TEST_INVALID_VAR={$invalidEncrypted}");

        // Re-instantiate service provider
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Invalid encrypted value should remain unchanged (graceful failure)
        $this->assertSame($invalidEncrypted, env('TEST_INVALID_VAR'));

        // Clean up
        unset($_ENV['TEST_INVALID_VAR']);
        putenv('TEST_INVALID_VAR');
    }

    public function test_auto_decrypt_with_custom_prefix(): void
    {
        // Note: Auto-decrypt has been removed, but test the helper functions work with custom prefix

        // Change the prefix in config
        config(['configrypt.prefix' => 'CUSTOM:']);

        $service = new ConfigryptService('test-key-1234567890123456789012', 'CUSTOM:');
        $encrypted = $service->encrypt('custom-secret');

        $_ENV['TEST_CUSTOM_VAR'] = $encrypted;
        putenv("TEST_CUSTOM_VAR={$encrypted}");

        // Re-instantiate service provider (no auto-decrypt anymore)
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Values should remain encrypted in $_ENV and env()
        $this->assertSame($encrypted, $_ENV['TEST_CUSTOM_VAR']);
        $this->assertSame($encrypted, env('TEST_CUSTOM_VAR'));

        // But helper functions should work with custom prefix
        $this->assertSame('custom-secret', configrypt_env('TEST_CUSTOM_VAR'));

        // Clean up
        unset($_ENV['TEST_CUSTOM_VAR']);
        putenv('TEST_CUSTOM_VAR');
    }

    public function test_auto_decrypt_disabled(): void
    {
        // Explicitly disable auto-decrypt
        $_ENV['CONFIGRYPT_AUTO_DECRYPT'] = 'false';
        config(['configrypt.auto_decrypt' => false]);

        $service = new ConfigryptService('test-key-1234567890123456789012');
        $encrypted = $service->encrypt('should-not-be-decrypted');

        $_ENV['TEST_NO_DECRYPT_VAR'] = $encrypted;
        putenv("TEST_NO_DECRYPT_VAR={$encrypted}");

        // Re-instantiate service provider
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Should remain encrypted in both $_ENV and env()
        $this->assertSame($encrypted, $_ENV['TEST_NO_DECRYPT_VAR']);
        $this->assertSame($encrypted, env('TEST_NO_DECRYPT_VAR'));
        $this->assertStringStartsWith('ENC:', env('TEST_NO_DECRYPT_VAR'));

        // Clean up
        unset($_ENV['TEST_NO_DECRYPT_VAR']);
        unset($_ENV['CONFIGRYPT_AUTO_DECRYPT']);
        putenv('TEST_NO_DECRYPT_VAR');
    }
}
