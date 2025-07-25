# Configuration

Laravel Configrypt provides several configuration options to customize its behavior according to your needs.

## Publishing Configuration

To publish the configuration file:

```bash
php artisan vendor:publish --tag=configrypt-config
```

This creates `config/configrypt.php` with the following default settings:

```php
<?php

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
    | Auto Decrypt
    |--------------------------------------------------------------------------
    |
    | When enabled, encrypted environment variables will be automatically
    | decrypted during the application bootstrap process.
    |
    */

    'auto_decrypt' => env('CONFIGRYPT_AUTO_DECRYPT', true),

];
```

## Configuration Options

### Encryption Key

**Environment Variable**: `CONFIGRYPT_KEY`  
**Default**: Falls back to `APP_KEY`  
**Type**: String

The encryption key used to encrypt and decrypt values. For AES-256-CBC, this should be 32 characters long. If the key is shorter or longer, Laravel Configrypt will automatically hash it to the correct length.

```env
# Dedicated key (recommended)
CONFIGRYPT_KEY=your-32-character-encryption-key

# Or use your Laravel APP_KEY (not recommended for production)
APP_KEY=base64:your-laravel-app-key
```

### Encryption Prefix

**Environment Variable**: `CONFIGRYPT_PREFIX`  
**Default**: `ENC:`  
**Type**: String

The prefix used to identify encrypted values in your environment variables. Only values starting with this prefix will be processed for decryption.

```env
# Default prefix
CONFIGRYPT_PREFIX=ENC:

# Custom prefix
CONFIGRYPT_PREFIX=ENCRYPTED:
```

Examples with different prefixes:
```env
# With default ENC: prefix
DB_PASSWORD=ENC:AbCdEfGhIjKlMnOpQrStUvWxYz==

# With custom ENCRYPTED: prefix  
DB_PASSWORD=ENCRYPTED:AbCdEfGhIjKlMnOpQrStUvWxYz==
```

### Cipher Method

**Environment Variable**: `CONFIGRYPT_CIPHER`  
**Default**: `AES-256-CBC`  
**Type**: String  
**Supported Values**: `AES-256-CBC`, `AES-128-CBC`

The encryption cipher method to use. AES-256-CBC is recommended for maximum security.

```env
# AES-256-CBC (recommended)
CONFIGRYPT_CIPHER=AES-256-CBC

# AES-128-CBC (faster, less secure)
CONFIGRYPT_CIPHER=AES-128-CBC
```

### Auto Decrypt

**Environment Variable**: `CONFIGRYPT_AUTO_DECRYPT`  
**Default**: `true`  
**Type**: Boolean

When enabled, encrypted environment variables will be automatically decrypted during the Laravel application bootstrap process. This allows transparent usage with `env()` and `config()` helpers.

```env
# Enable auto-decryption (default)
CONFIGRYPT_AUTO_DECRYPT=true

# Disable auto-decryption (manual decryption only)
CONFIGRYPT_AUTO_DECRYPT=false
```

When disabled, you'll need to manually decrypt values using the `Configrypt` facade:

```php
use LaravelConfigrypt\Facades\Configrypt;

$decrypted = Configrypt::decrypt(env('ENCRYPTED_VALUE'));
```

## Environment-Specific Configuration

You can use different configurations for different environments:

### Development (.env.local)
```env
CONFIGRYPT_KEY=development-key-32-characters
CONFIGRYPT_PREFIX=DEV_ENC:
CONFIGRYPT_AUTO_DECRYPT=true
```

### Production (.env.production)
```env
CONFIGRYPT_KEY=production-key-32-characters-long
CONFIGRYPT_PREFIX=ENC:
CONFIGRYPT_CIPHER=AES-256-CBC
CONFIGRYPT_AUTO_DECRYPT=true
```

### Testing (.env.testing)
```env
CONFIGRYPT_KEY=testing-key-32-characters-long---
CONFIGRYPT_PREFIX=TEST_ENC:
CONFIGRYPT_AUTO_DECRYPT=true
```

## Security Best Practices

1. **Use Dedicated Keys**: Use a separate `CONFIGRYPT_KEY` instead of relying on `APP_KEY`
2. **Key Rotation**: Regularly rotate your encryption keys
3. **Environment Isolation**: Use different keys for different environments
4. **Key Management**: Store encryption keys securely (e.g., in a key vault)
5. **Strong Keys**: Use cryptographically secure random keys

## Next Steps

- [Basic Usage](basic-usage.md) - Learn how to encrypt and use encrypted values
- [Security Considerations](security.md) - Learn about security best practices