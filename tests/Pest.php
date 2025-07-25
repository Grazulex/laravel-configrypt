<?php

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
    ]);
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
