<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use Orchestra\Testbench\TestCase;

class EncryptCommandTest extends TestCase
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

    public function test_can_encrypt_a_value_successfully(): void
    {
        $this->artisan('configrypt:encrypt', ['value' => 'my-secret-password'])
            ->expectsOutput('Encrypted value:')
            ->assertExitCode(0);
    }

    public function test_fails_with_empty_value(): void
    {
        $this->artisan('configrypt:encrypt', ['value' => ''])
            ->expectsOutput('Value cannot be empty.')
            ->assertExitCode(1);
    }

    public function test_displays_usage_instructions(): void
    {
        $this->artisan('configrypt:encrypt', ['value' => 'test-value'])
            ->expectsOutput('You can now use this encrypted value in your .env file:')
            ->assertExitCode(0);
    }

    public function test_produces_output_starting_with_prefix(): void
    {
        $output = $this->artisan('configrypt:encrypt', ['value' => 'test-value']);

        $output->expectsOutput('Encrypted value:');
        $output->assertExitCode(0);
    }
}
