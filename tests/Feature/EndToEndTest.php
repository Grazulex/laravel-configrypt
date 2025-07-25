<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use Orchestra\Testbench\TestCase;

class EndToEndTest extends TestCase
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

    public function test_complete_encrypt_decrypt_workflow(): void
    {
        // Step 1: Encrypt a value using the command
        $originalValue = 'super-secret-api-key';

        $this->artisan('configrypt:encrypt', ['value' => $originalValue])
            ->assertExitCode(0);

        // Step 2: Get the encrypted value (we'll simulate this)
        $service = $this->app->make(ConfigryptService::class);
        $encrypted = $service->encrypt($originalValue);

        // Step 3: Decrypt using the command
        $this->artisan('configrypt:decrypt', ['value' => $encrypted])
            ->expectsOutput('Decrypted value:')
            ->expectsOutput($originalValue)
            ->assertExitCode(0);
    }

    public function test_real_world_scenario_database_password(): void
    {
        // Simulate a real-world scenario where we encrypt a database password
        $dbPassword = 'my-super-secure-db-password-123!@#';

        // Encrypt the password
        $service = $this->app->make(ConfigryptService::class);
        $encryptedPassword = $service->encrypt($dbPassword);

        // Simulate putting it in the .env file
        $_ENV['DB_PASSWORD'] = $encryptedPassword;
        putenv("DB_PASSWORD={$encryptedPassword}");

        // Re-instantiate service provider (no auto-decrypt anymore)
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Values remain encrypted in $_ENV and env() - this is expected
        $this->assertStringStartsWith('ENC:', $_ENV['DB_PASSWORD']);
        $this->assertStringStartsWith('ENC:', env('DB_PASSWORD'));

        // Test helper function works correctly
        $this->assertSame($dbPassword, configrypt_env('DB_PASSWORD'));

        // Clean up
        unset($_ENV['DB_PASSWORD']);
        putenv('DB_PASSWORD');
    }

    public function test_multiple_encrypted_environment_variables(): void
    {
        // Enable auto-decryption for this test
        $_ENV['CONFIGRYPT_AUTO_DECRYPT'] = 'true';

        $service = $this->app->make(ConfigryptService::class);

        // Encrypt multiple values
        $secrets = [
            'API_KEY' => 'secret-api-key-12345',
            'MAIL_PASSWORD' => 'mail-password-67890',
            'CACHE_PASSWORD' => 'cache-password-abcdef',
        ];

        $encryptedSecrets = [];
        foreach ($secrets as $key => $value) {
            $encryptedSecrets[$key] = $service->encrypt($value);
            $_ENV[$key] = $encryptedSecrets[$key];
            putenv("{$key}={$encryptedSecrets[$key]}");
        }

        // Re-instantiate service provider (no auto-decrypt anymore)
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Verify all values remain encrypted but helper functions work
        foreach ($secrets as $key => $expectedValue) {
            $this->assertStringStartsWith('ENC:', $_ENV[$key]);
            $this->assertStringStartsWith('ENC:', env($key));
            // Helper function works correctly
            $this->assertSame($expectedValue, configrypt_env($key));
        }

        // Clean up
        foreach (array_keys($secrets) as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
    }

    public function test_mixed_encrypted_and_plain_environment_variables(): void
    {
        $service = $this->app->make(ConfigryptService::class);

        // Set up mixed environment
        $_ENV['PLAIN_VALUE'] = 'this-is-plain-text';
        $_ENV['ENCRYPTED_VALUE'] = $service->encrypt('this-is-encrypted');
        $_ENV['ANOTHER_PLAIN'] = 'another-plain-value';

        putenv('PLAIN_VALUE=this-is-plain-text');
        putenv("ENCRYPTED_VALUE={$_ENV['ENCRYPTED_VALUE']}");
        putenv('ANOTHER_PLAIN=another-plain-value');

        // Re-instantiate service provider (no auto-decrypt anymore)
        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Verify results - plain values work normally, encrypted values remain encrypted
        $this->assertSame('this-is-plain-text', env('PLAIN_VALUE'));
        $this->assertSame('this-is-plain-text', $_ENV['PLAIN_VALUE']);

        $this->assertStringStartsWith('ENC:', $_ENV['ENCRYPTED_VALUE']); // Still encrypted in $_ENV
        $this->assertStringStartsWith('ENC:', env('ENCRYPTED_VALUE')); // Still encrypted in env()
        $this->assertSame('this-is-encrypted', configrypt_env('ENCRYPTED_VALUE')); // Helper works

        $this->assertSame('another-plain-value', env('ANOTHER_PLAIN'));
        $this->assertSame('another-plain-value', $_ENV['ANOTHER_PLAIN']);

        // Clean up
        unset($_ENV['PLAIN_VALUE'], $_ENV['ENCRYPTED_VALUE'], $_ENV['ANOTHER_PLAIN']);
        putenv('PLAIN_VALUE');
        putenv('ENCRYPTED_VALUE');
        putenv('ANOTHER_PLAIN');
    }

    public function test_encryption_with_different_key_lengths(): void
    {
        // Test with different key configurations
        $testCases = [
            'short-key' => 'short-key',
            'medium-length-key-123456' => 'medium-length-key-123456',
            'exactly-32-characters-long-key!!' => 'exactly-32-characters-long-key!!',
        ];

        foreach ($testCases as $description => $key) {
            $service = new ConfigryptService($key);
            $originalValue = "test-value-for-{$description}";

            $encrypted = $service->encrypt($originalValue);
            $decrypted = $service->decrypt($encrypted);

            $this->assertSame($originalValue, $decrypted, "Failed for key: {$description}");
        }
    }
}
