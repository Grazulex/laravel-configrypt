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
        $app['config']->set('configrypt.auto_decrypt', true);
    }

    public function test_auto_decrypt_environment_variables(): void
    {
        // Encrypt a test value
        $service = new ConfigryptService('test-key-1234567890123456789012');
        $encrypted = $service->encrypt('secret-database-password');

        // Simulate encrypted environment variable
        $_ENV['TEST_ENCRYPTED_VAR'] = $encrypted;
        putenv("TEST_ENCRYPTED_VAR={$encrypted}");

        // Re-instantiate service provider to trigger auto-decrypt
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Check that the environment variable was decrypted
        $this->assertSame('secret-database-password', env('TEST_ENCRYPTED_VAR'));
        $this->assertSame('secret-database-password', $_ENV['TEST_ENCRYPTED_VAR']);

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
        // Change the prefix
        config(['configrypt.prefix' => 'CUSTOM:']);

        $service = new ConfigryptService('test-key-1234567890123456789012', 'CUSTOM:');
        $encrypted = $service->encrypt('custom-secret');

        $_ENV['TEST_CUSTOM_VAR'] = $encrypted;
        putenv("TEST_CUSTOM_VAR={$encrypted}");

        // Re-instantiate service provider
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Should be decrypted
        $this->assertSame('custom-secret', env('TEST_CUSTOM_VAR'));

        // Clean up
        unset($_ENV['TEST_CUSTOM_VAR']);
        putenv('TEST_CUSTOM_VAR');
    }

    public function test_auto_decrypt_disabled(): void
    {
        // Disable auto decrypt
        config(['configrypt.auto_decrypt' => false]);

        $service = new ConfigryptService('test-key-1234567890123456789012');
        $encrypted = $service->encrypt('should-not-be-decrypted');

        $_ENV['TEST_NO_DECRYPT_VAR'] = $encrypted;
        putenv("TEST_NO_DECRYPT_VAR={$encrypted}");

        // Re-instantiate service provider
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Should remain encrypted
        $this->assertSame($encrypted, env('TEST_NO_DECRYPT_VAR'));
        $this->assertStringStartsWith('ENC:', env('TEST_NO_DECRYPT_VAR'));

        // Clean up
        unset($_ENV['TEST_NO_DECRYPT_VAR']);
        putenv('TEST_NO_DECRYPT_VAR');
    }
}
