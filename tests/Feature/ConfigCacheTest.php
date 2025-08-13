<?php

declare(strict_types=1);

use LaravelConfigrypt\Support\ConfigValue;

test('config value works with config cache', function (): void {
    $plaintext = 'database-password-secret';
    $service = createConfigryptService();
    $encrypted = $service->encrypt($plaintext);

    // Create a ConfigValue
    $configValue = new ConfigValue($encrypted, 'fallback');

    // Simulate what happens during config:cache
    $serialized = serialize($configValue);

    // The serialized data should contain the encrypted value, not the decrypted one
    expect($serialized)->toContain($encrypted);
    expect($serialized)->not->toContain($plaintext);

    // Unserialize (simulating loading from cache)
    $unserialized = unserialize($serialized);

    // Should still decrypt correctly
    expect((string) $unserialized)->toBe($plaintext);
    expect($unserialized->getValue())->toBe($plaintext);
});

test('config value handles var_export for config cache', function (): void {
    $plaintext = 'api-key-secret';
    $service = createConfigryptService();
    $encrypted = $service->encrypt($plaintext);

    // Create a ConfigValue
    $configValue = new ConfigValue($encrypted, 'fallback');

    // Test var_export compatibility (used by config:cache)
    $exported = var_export($configValue, true);

    // The exported code should contain the encrypted value, not the decrypted one
    expect($exported)->toContain($encrypted);
    expect($exported)->not->toContain($plaintext);

    // The exported code should be valid PHP that recreates the object
    $recreated = eval("return $exported;");
    expect($recreated)->toBeInstanceOf(ConfigValue::class);
    expect((string) $recreated)->toBe($plaintext);
});

test('config value with helper function in config file', function (): void {
    $plaintext = 'mail-password';
    $service = createConfigryptService();
    $encrypted = $service->encrypt($plaintext);

    // This simulates how it would be used in a config file
    $configArray = [
        'mail' => [
            'driver' => 'smtp',
            'host' => 'smtp.example.com',
            'password' => configrypt_value($encrypted, 'default'),
        ],
    ];

    // Test that the config value is created correctly
    expect($configArray['mail']['password'])->toBeInstanceOf(ConfigValue::class);

    // Test that it decrypts when accessed
    expect((string) $configArray['mail']['password'])->toBe($plaintext);

    // Test that config:cache scenario works
    $serialized = serialize($configArray);
    expect($serialized)->toContain($encrypted);
    expect($serialized)->not->toContain($plaintext);

    $unserialized = unserialize($serialized);
    expect((string) $unserialized['mail']['password'])->toBe($plaintext);
});
