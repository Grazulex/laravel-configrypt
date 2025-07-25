<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
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

    public function test_service_provider_registers_service(): void
    {
        $service = $this->app->make(ConfigryptService::class);

        $this->assertInstanceOf(ConfigryptService::class, $service);
        $this->assertSame('ENC:', $service->getPrefix());
        $this->assertSame('test-key-1234567890123456789012', $service->getKey());
    }

    public function test_service_provider_registers_alias(): void
    {
        $service1 = $this->app->make(ConfigryptService::class);
        $service2 = $this->app->make('configrypt');

        $this->assertSame($service1, $service2);
    }

    public function test_config_is_published(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'configrypt-config',
            '--force' => true,
        ]);

        $this->assertFileExists(config_path('configrypt.php'));
    }

    public function test_commands_are_registered(): void
    {
        // Test that the commands can be executed
        $this->artisan('configrypt:encrypt --help')->assertExitCode(0);
        $this->artisan('configrypt:decrypt --help')->assertExitCode(0);
    }

    public function test_auto_decrypt_handles_missing_key_gracefully(): void
    {
        config(['configrypt.key' => null]);

        $provider = new LaravelConfigryptServiceProvider($this->app);
        $provider->register();

        $this->expectNotToPerformAssertions();
        $provider->boot();
    }
}
