<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use Orchestra\Testbench\TestCase;

class DecryptCommandTest extends TestCase
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
    }

    public function test_can_decrypt_a_value_successfully(): void
    {
        // First encrypt a value to get a valid encrypted string
        $service = new ConfigryptService('test-key-1234567890123456789012');
        $encrypted = $service->encrypt('my-secret-password');

        $this->artisan('configrypt:decrypt', ['value' => $encrypted])
            ->expectsOutput('Decrypted value:')
            ->expectsOutput('my-secret-password')
            ->assertExitCode(0);
    }

    public function test_fails_with_empty_value(): void
    {
        $this->artisan('configrypt:decrypt', ['value' => ''])
            ->expectsOutput('Encrypted value cannot be empty.')
            ->assertExitCode(1);
    }

    public function test_fails_with_invalid_encrypted_value(): void
    {
        $this->artisan('configrypt:decrypt', ['value' => 'invalid-encrypted-value'])
            ->expectsOutputToContain('Decryption failed:')
            ->expectsOutput('Make sure the value is properly encrypted and you have the correct encryption key.')
            ->assertExitCode(1);
    }

    public function test_fails_with_wrong_encryption_key(): void
    {
        // Encrypt with one key
        $service1 = new ConfigryptService('different-key-123456789012');
        $encrypted = $service1->encrypt('test-value');

        // Try to decrypt with different key (configured in defineEnvironment)
        $this->artisan('configrypt:decrypt', ['value' => $encrypted])
            ->expectsOutputToContain('Decryption failed:')
            ->assertExitCode(1);
    }
}
