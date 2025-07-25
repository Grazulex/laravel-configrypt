<?php

use LaravelConfigrypt\Facades\Configrypt;
use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use Orchestra\Testbench\TestCase;

class ConfigryptFacadeTest extends TestCase
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

    public function test_can_encrypt_and_decrypt_through_facade(): void
    {
        $originalValue = 'facade-test-value';

        $encrypted = Configrypt::encrypt($originalValue);
        $this->assertStringStartsWith('ENC:', $encrypted);

        $decrypted = Configrypt::decrypt($encrypted);
        $this->assertSame($originalValue, $decrypted);
    }

    public function test_can_detect_encrypted_values_through_facade(): void
    {
        $this->assertTrue(Configrypt::isEncrypted('ENC:some-value'));
        $this->assertFalse(Configrypt::isEncrypted('plain-text'));
    }

    public function test_can_get_prefix_through_facade(): void
    {
        $this->assertSame('ENC:', Configrypt::getPrefix());
    }

    public function test_can_get_key_through_facade(): void
    {
        $this->assertSame('test-key-1234567890123456789012', Configrypt::getKey());
    }
}
