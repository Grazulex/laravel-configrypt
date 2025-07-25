<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Facades;

use Illuminate\Support\Facades\Facade;
use LaravelConfigrypt\Support\EnvironmentDecryptor;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool isEncrypted(mixed $value)
 * @method static void decryptAll()
 * @method static array<string, mixed> getAllDecrypted()
 *
 * @see \LaravelConfigrypt\Support\EnvironmentDecryptor
 */
class ConfigryptEnv extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EnvironmentDecryptor::class;
    }
}
