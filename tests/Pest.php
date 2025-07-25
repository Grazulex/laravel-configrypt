<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use Orchestra\Testbench\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// Global setup for all tests
beforeEach(function (): void {
    // Set up default test environment
    config([
        'app.key' => 'base64:' . base64_encode('test-app-key-1234567890123456'),
        'app.debug' => true,
        'app.env' => 'testing',
        'configrypt.key' => 'test-key-1234567890123456789012',
        'configrypt.cipher' => 'AES-256-CBC',
        'configrypt.prefix' => 'ENC:',
    ]);

    // Load the service provider
    $this->app->register(LaravelConfigryptServiceProvider::class);

    // Include the helper functions
    if (! function_exists('configrypt_env')) {
        require_once __DIR__ . '/../src/helpers.php';
    }
});

// Helper function to create a service with test configuration
function createConfigryptService(?string $key = null, ?string $prefix = null, ?string $cipher = null): ConfigryptService
{
    return new ConfigryptService(
        $key ?? 'test-key-1234567890123456789012',
        $prefix ?? 'ENC:',
        $cipher ?? 'AES-256-CBC'
    );
}

// Helper function to encrypt a value for testing
function encryptForTest(string $value, ?string $key = null): string
{
    $service = createConfigryptService($key);

    return $service->encrypt($value);
}
