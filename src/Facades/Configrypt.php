<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Facades;

use Illuminate\Support\Facades\Facade;
use LaravelConfigrypt\Services\ConfigryptService;

/**
 * @method static string encrypt(string $value)
 * @method static string decrypt(string $encryptedValue)
 * @method static bool isEncrypted(string $value)
 * @method static string getPrefix()
 * @method static string getKey()
 *
 * @see \LaravelConfigrypt\Services\ConfigryptService
 */
class Configrypt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConfigryptService::class;
    }
}
