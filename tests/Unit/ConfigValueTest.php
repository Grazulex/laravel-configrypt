<?php

declare(strict_types=1);

use LaravelConfigrypt\Support\ConfigValue;

test('it stores encrypted value without decrypting', function (): void {
    $encryptedValue = 'ENC:gk9AvRZgx6Jyds7K2uFctw==';
    $configValue = new ConfigValue($encryptedValue);

    // The ConfigValue should store the encrypted value
    expect($configValue)->toBeInstanceOf(ConfigValue::class);
});

test('it decrypts on string conversion', function (): void {
    $service = createConfigryptService();
    $plaintext = 'secret-password';
    $encrypted = $service->encrypt($plaintext);

    $configValue = new ConfigValue($encrypted);

    // When converted to string, it should decrypt
    expect((string) $configValue)->toBe($plaintext);
});

test('it decrypts on get value', function (): void {
    $service = createConfigryptService();
    $plaintext = 'another-secret';
    $encrypted = $service->encrypt($plaintext);

    $configValue = new ConfigValue($encrypted);

    // When getValue() is called, it should decrypt
    expect($configValue->getValue())->toBe($plaintext);
});

test('it handles non encrypted values', function (): void {
    $plainValue = 'not-encrypted';
    $configValue = new ConfigValue($plainValue);

    expect((string) $configValue)->toBe($plainValue);
    expect($configValue->getValue())->toBe($plainValue);
});

test('it uses default when decryption fails', function (): void {
    $invalidEncrypted = 'ENC:invalid-data';
    $default = 'fallback-value';
    $configValue = new ConfigValue($invalidEncrypted, $default);

    expect((string) $configValue)->toBe($default);
    expect($configValue->getValue())->toBe($default);
});

test('it serializes without decrypting', function (): void {
    $service = createConfigryptService();
    $plaintext = 'secret-data';
    $encrypted = $service->encrypt($plaintext);

    $configValue = new ConfigValue($encrypted, 'default');

    // Serialize the object (like config:cache would do)
    $serialized = serialize($configValue);
    $unserialized = unserialize($serialized);

    // It should still work after unserialization
    expect((string) $unserialized)->toBe($plaintext);

    // And the serialized data shouldn't contain the decrypted value
    expect($serialized)->not->toContain($plaintext);
    expect($serialized)->toContain($encrypted);
});

test('var export compatibility', function (): void {
    $service = createConfigryptService();
    $plaintext = 'export-test';
    $encrypted = $service->encrypt($plaintext);

    $configValue = new ConfigValue($encrypted, 'default');

    // Test var_export (used by config:cache)
    $exported = var_export($configValue, true);

    // The exported code shouldn't contain the decrypted value
    expect($exported)->not->toContain($plaintext);
    expect($exported)->toContain($encrypted);

    // But when evaluated, it should still work
    $recreated = eval("return $exported;");
    expect((string) $recreated)->toBe($plaintext);
});

test('configrypt value helper', function (): void {
    $service = createConfigryptService();
    $plaintext = 'helper-test';
    $encrypted = $service->encrypt($plaintext);

    $configValue = configrypt_value($encrypted, 'default');

    expect($configValue)->toBeInstanceOf(ConfigValue::class);
    expect((string) $configValue)->toBe($plaintext);
});
