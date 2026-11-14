<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used to encrypt and decrypt values in your .env file.
    | You can use a dedicated CONFIGRYPT_KEY or fallback to APP_KEY.
    | Make sure this key is 32 characters long for AES-256-CBC.
    |
    */

    'key' => env('CONFIGRYPT_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Previous Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Keys that were used before a key rotation. They are only used to
    | decrypt values: when the current key cannot decrypt a value, each
    | previous key is tried in order. New values are always encrypted with
    | the current key. Use a comma-separated list or an array of keys.
    |
    */

    'previous_keys' => env('CONFIGRYPT_PREVIOUS_KEYS', ''),

    /*
    |--------------------------------------------------------------------------
    | Encryption Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix is used to identify encrypted values in your .env file.
    | Only values starting with this prefix will be decrypted.
    |
    */

    'prefix' => env('CONFIGRYPT_PREFIX', 'ENC:'),

    /*
    |--------------------------------------------------------------------------
    | Cipher Method
    |--------------------------------------------------------------------------
    |
    | The cipher method used for encryption and decryption.
    | Supported: "AES-256-CBC", "AES-128-CBC"
    |
    */

    'cipher' => env('CONFIGRYPT_CIPHER', 'AES-256-CBC'),

    /*
    |--------------------------------------------------------------------------
    | Auto Decrypt (DEPRECATED)
    |--------------------------------------------------------------------------
    |
    | Auto-decryption has been removed due to Laravel's environment caching
    | limitations and complexity. Use configrypt_env() or encrypted_env()
    | helper functions instead for reliable decryption.
    |
    | This option is kept for backward compatibility but has no effect.
    |
    */

    'auto_decrypt' => env('CONFIGRYPT_AUTO_DECRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Plain-Text Check
    |--------------------------------------------------------------------------
    |
    | Used by the configrypt:check command. Variables whose name matches one
    | of the "patterns" (case-insensitive, "*" as wildcard) must hold an
    | encrypted value. Variables listed in "ignore" are never reported.
    |
    */

    'check' => [
        'patterns' => [
            '*PASSWORD*',
            '*PASSWD*',
            '*_PASS',
            '*SECRET*',
            '*TOKEN*',
            '*_KEY',
            '*API_KEY*',
            '*PRIVATE*',
            '*CREDENTIAL*',
        ],
        'ignore' => [
            'APP_KEY',
            'CONFIGRYPT_KEY',
            'CONFIGRYPT_PREVIOUS_KEYS',
        ],
    ],

];
