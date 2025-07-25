<?php

use LaravelConfigrypt\Services\ConfigryptService;

describe('ConfigryptService', function (): void {
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

    it('throws exception for null key', function (): void {
        expect(fn (): ConfigryptService => new ConfigryptService(null))
            ->toThrow(InvalidArgumentException::class, 'Encryption key cannot be empty');
    });

    it('can use custom prefix', function (): void {
        $service = new ConfigryptService('test-key-1234567890123456789012', 'CUSTOM:', 'AES-256-CBC');

        $originalValue = 'test-value';
        $encrypted = $service->encrypt($originalValue);

        expect($encrypted)->toStartWith('CUSTOM:');
        expect($service->getPrefix())->toBe('CUSTOM:');

        $decrypted = $service->decrypt($encrypted);
        expect($decrypted)->toBe($originalValue);
    });

    it('can decrypt without prefix', function (): void {
        $service = new ConfigryptService('test-key-1234567890123456789012', 'ENC:', 'AES-256-CBC');

        $originalValue = 'test-value';
        $encrypted = $service->encrypt($originalValue);

        // Remove prefix manually
        $encryptedWithoutPrefix = substr($encrypted, 4); // Remove 'ENC:'

        $decrypted = $service->decrypt($encryptedWithoutPrefix);
        expect($decrypted)->toBe($originalValue);
    });

    it('handles different key lengths correctly', function (): void {
        // Short key - should be hashed to proper length
        $service1 = new ConfigryptService('short-key', 'ENC:', 'AES-256-CBC');

        $originalValue = 'test-value';
        $encrypted = $service1->encrypt($originalValue);
        $decrypted = $service1->decrypt($encrypted);

        expect($decrypted)->toBe($originalValue);
    });

    it('returns correct prefix', function (): void {
        $service = new ConfigryptService('test-key-1234567890123456789012', 'TEST:', 'AES-256-CBC');

        expect($service->getPrefix())->toBe('TEST:');
    });

    it('returns correct key', function (): void {
        $key = 'test-key-1234567890123456789012';
        $service = new ConfigryptService($key, 'ENC:', 'AES-256-CBC');

        expect($service->getKey())->toBe($key);
    });

    it('works with empty string values', function (): void {
        $service = new ConfigryptService('test-key-1234567890123456789012', 'ENC:', 'AES-256-CBC');

        $originalValue = '';
        $encrypted = $service->encrypt($originalValue);
        $decrypted = $service->decrypt($encrypted);

        expect($decrypted)->toBe($originalValue);
    });

    it('works with special characters', function (): void {
        $service = new ConfigryptService('test-key-1234567890123456789012', 'ENC:', 'AES-256-CBC');

        $originalValue = 'Special chars: àéîôù @#$%^&*()_+{}|:"<>?[];\',./';
        $encrypted = $service->encrypt($originalValue);
        $decrypted = $service->decrypt($encrypted);

        expect($decrypted)->toBe($originalValue);
    });

    it('produces different encrypted values for same input', function (): void {
        $service = new ConfigryptService('test-key-1234567890123456789012', 'ENC:', 'AES-256-CBC');

        $originalValue = 'same-input';
        $encrypted1 = $service->encrypt($originalValue);
        $encrypted2 = $service->encrypt($originalValue);

        // Should be different due to random IV
        expect($encrypted1)->not->toBe($encrypted2);

        // But both should decrypt to the same value
        expect($service->decrypt($encrypted1))->toBe($originalValue);
        expect($service->decrypt($encrypted2))->toBe($originalValue);
    });
});
