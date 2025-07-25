<?php

use LaravelConfigrypt\Services\ConfigryptService;

it('can encrypt and decrypt values', function (): void {
    $service = new ConfigryptService('test-key-1234567890123456789012', 'ENC:', 'AES-256-CBC');

    $originalValue = 'secret-password';
    $encrypted = $service->encrypt($originalValue);

    expect($encrypted)->toStartWith('ENC:');

    $decrypted = $service->decrypt($encrypted);
    expect($decrypted)->toBe($originalValue);
});

it('can detect encrypted values', function (): void {
    $service = new ConfigryptService('test-key-1234567890123456789012', 'ENC:', 'AES-256-CBC');

    expect($service->isEncrypted('ENC:some-encrypted-value'))->toBeTrue();
    expect($service->isEncrypted('plain-text'))->toBeFalse();
});

it('throws exception for empty key', function (): void {
    expect(fn (): ConfigryptService => new ConfigryptService(''))
        ->toThrow(InvalidArgumentException::class, 'Encryption key cannot be empty');
});
