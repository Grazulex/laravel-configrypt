<?php

declare(strict_types=1);

use Illuminate\Contracts\Encryption\DecryptException;
use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Support\ConfigValue;
use LaravelConfigrypt\Support\EnvironmentDecryptor;

const PREVIOUS_KEYS_CURRENT = 'current-key-12345678901234567890';
const PREVIOUS_KEYS_OLD = 'old-key-1234567890123456789012345';
const PREVIOUS_KEYS_OLDER = 'older-key-123456789012345678901';

beforeEach(function (): void {
    config([
        'app.debug' => false,
        'configrypt.key' => PREVIOUS_KEYS_CURRENT,
        'configrypt.prefix' => 'ENC:',
        'configrypt.cipher' => 'AES-256-CBC',
        'configrypt.previous_keys' => null,
    ]);

    $this->app->register(LaravelConfigryptServiceProvider::class, true);
});

/**
 * @param  array<int, string>|string|null  $previousKeys
 */
function useConfiguredKeys(string $key, array|string|null $previousKeys): void
{
    config([
        'configrypt.key' => $key,
        'configrypt.previous_keys' => $previousKeys,
    ]);

    app()->forgetInstance(ConfigryptService::class);
    app()->forgetInstance(EnvironmentDecryptor::class);
}

describe('ConfigryptService previous keys', function (): void {
    it('decrypts values encrypted with a previous key', function (): void {
        $encrypted = (new ConfigryptService(PREVIOUS_KEYS_OLD))->encrypt('legacy-secret');

        $service = new ConfigryptService(PREVIOUS_KEYS_CURRENT, 'ENC:', 'AES-256-CBC', [PREVIOUS_KEYS_OLD]);

        expect($service->decrypt($encrypted))->toBe('legacy-secret');
    });

    it('still decrypts values encrypted with the current key', function (): void {
        $service = new ConfigryptService(PREVIOUS_KEYS_CURRENT, 'ENC:', 'AES-256-CBC', [PREVIOUS_KEYS_OLD]);

        expect($service->decrypt($service->encrypt('fresh-secret')))->toBe('fresh-secret');
    });

    it('encrypts with the current key only', function (): void {
        $service = new ConfigryptService(PREVIOUS_KEYS_CURRENT, 'ENC:', 'AES-256-CBC', [PREVIOUS_KEYS_OLD]);
        $encrypted = $service->encrypt('fresh-secret');

        expect((new ConfigryptService(PREVIOUS_KEYS_CURRENT))->decrypt($encrypted))->toBe('fresh-secret');
        expect(fn (): string => (new ConfigryptService(PREVIOUS_KEYS_OLD))->decrypt($encrypted))
            ->toThrow(DecryptException::class);
    });

    it('accepts a comma-separated string of previous keys', function (): void {
        $encryptedOld = (new ConfigryptService(PREVIOUS_KEYS_OLD))->encrypt('old-secret');
        $encryptedOlder = (new ConfigryptService(PREVIOUS_KEYS_OLDER))->encrypt('older-secret');

        $service = new ConfigryptService(
            PREVIOUS_KEYS_CURRENT,
            'ENC:',
            'AES-256-CBC',
            ' ' . PREVIOUS_KEYS_OLD . ' , ,' . PREVIOUS_KEYS_OLDER . ','
        );

        expect($service->getPreviousKeys())->toBe([PREVIOUS_KEYS_OLD, PREVIOUS_KEYS_OLDER]);
        expect($service->decrypt($encryptedOld))->toBe('old-secret');
        expect($service->decrypt($encryptedOlder))->toBe('older-secret');
    });

    it('ignores empty, duplicate and current keys', function (): void {
        $service = new ConfigryptService(
            PREVIOUS_KEYS_CURRENT,
            'ENC:',
            'AES-256-CBC',
            [PREVIOUS_KEYS_OLD, '', PREVIOUS_KEYS_CURRENT, PREVIOUS_KEYS_OLD, 42]
        );

        expect($service->getPreviousKeys())->toBe([PREVIOUS_KEYS_OLD]);
    });

    it('has no previous keys by default', function (): void {
        expect((new ConfigryptService(PREVIOUS_KEYS_CURRENT))->getPreviousKeys())->toBe([]);
        expect((new ConfigryptService(PREVIOUS_KEYS_CURRENT, 'ENC:', 'AES-256-CBC', ''))->getPreviousKeys())->toBe([]);
    });

    it('throws when no key can decrypt the value', function (): void {
        $encrypted = (new ConfigryptService('unknown-key-123456789012345678901'))->encrypt('secret');

        $service = new ConfigryptService(PREVIOUS_KEYS_CURRENT, 'ENC:', 'AES-256-CBC', [PREVIOUS_KEYS_OLD]);

        expect(fn (): string => $service->decrypt($encrypted))->toThrow(DecryptException::class);
    });

    it('re-encrypts values from a previous key with a new key', function (): void {
        $encrypted = (new ConfigryptService(PREVIOUS_KEYS_OLD))->encrypt('rotated-secret');

        $service = new ConfigryptService(PREVIOUS_KEYS_CURRENT, 'ENC:', 'AES-256-CBC', [PREVIOUS_KEYS_OLD]);
        $target = new ConfigryptService(PREVIOUS_KEYS_CURRENT);

        expect($target->decrypt($service->reEncrypt($encrypted, $target)))->toBe('rotated-secret');
    });
});

describe('Previous keys integration', function (): void {
    it('reads previous keys from the configuration', function (): void {
        useConfiguredKeys(PREVIOUS_KEYS_CURRENT, PREVIOUS_KEYS_OLD . ',' . PREVIOUS_KEYS_OLDER);

        expect(app(ConfigryptService::class)->getPreviousKeys())
            ->toBe([PREVIOUS_KEYS_OLD, PREVIOUS_KEYS_OLDER]);
    });

    it('accepts an array of previous keys in the configuration', function (): void {
        useConfiguredKeys(PREVIOUS_KEYS_CURRENT, [PREVIOUS_KEYS_OLD]);

        expect(app(ConfigryptService::class)->getPreviousKeys())->toBe([PREVIOUS_KEYS_OLD]);
    });

    it('decrypts environment variables encrypted with a previous key', function (): void {
        useConfiguredKeys(PREVIOUS_KEYS_CURRENT, PREVIOUS_KEYS_OLD);

        $encrypted = (new ConfigryptService(PREVIOUS_KEYS_OLD))->encrypt('env-secret');
        $_ENV['CONFIGRYPT_PREVIOUS_KEY_TEST'] = $encrypted;
        putenv("CONFIGRYPT_PREVIOUS_KEY_TEST={$encrypted}");

        try {
            expect(app(EnvironmentDecryptor::class)->get('CONFIGRYPT_PREVIOUS_KEY_TEST'))->toBe('env-secret');
            expect(configrypt_env('CONFIGRYPT_PREVIOUS_KEY_TEST'))->toBe('env-secret');
        } finally {
            unset($_ENV['CONFIGRYPT_PREVIOUS_KEY_TEST']);
            putenv('CONFIGRYPT_PREVIOUS_KEY_TEST');
        }
    });

    it('decrypts lazy config values encrypted with a previous key', function (): void {
        useConfiguredKeys(PREVIOUS_KEYS_CURRENT, [PREVIOUS_KEYS_OLD]);

        $encrypted = (new ConfigryptService(PREVIOUS_KEYS_OLD))->encrypt('config-secret');

        expect((new ConfigValue($encrypted, 'fallback'))->getValue())->toBe('config-secret');
    });

    it('decrypts lazy config values with previous keys when the service is not bound', function (): void {
        useConfiguredKeys(PREVIOUS_KEYS_CURRENT, PREVIOUS_KEYS_OLD);
        app()->offsetUnset(ConfigryptService::class);

        $encrypted = (new ConfigryptService(PREVIOUS_KEYS_OLD))->encrypt('unbound-secret');

        expect((new ConfigValue($encrypted, 'fallback'))->getValue())->toBe('unbound-secret');
    });

    it('falls back to the default when no key matches', function (): void {
        useConfiguredKeys(PREVIOUS_KEYS_CURRENT, [PREVIOUS_KEYS_OLD]);

        $encrypted = (new ConfigryptService('unknown-key-123456789012345678901'))->encrypt('secret');

        expect((new ConfigValue($encrypted, 'fallback'))->getValue())->toBe('fallback');
    });
});
