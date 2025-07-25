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
    | decrypted during the application bootstrap process. This happens very
    | early during service provider registration and bypasses Laravel's
    | environment caching to ensure env() returns decrypted values.
    |
    | Set to false to disable auto-decryption and use manual helpers only.
    |
    */

    'auto_decrypt' => env('CONFIGRYPT_AUTO_DECRYPT', false),

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
**Default**: `false`  
**Type**: Boolean

When enabled, encrypted environment variables will be automatically decrypted during the Laravel application bootstrap process. This innovative feature works by:

1. **Early Decryption**: Decryption happens during service provider registration (very early in Laravel's boot process)
2. **Environment Update**: Updates `$_ENV`, `$_SERVER`, and `putenv()` with decrypted values
3. **Cache Clearing**: Clears Laravel's internal environment cache using reflection to force re-reading
4. **Transparent Operation**: After enabling, `env()` calls return decrypted values seamlessly

```env
# Enable auto-decryption (recommended for most use cases)
CONFIGRYPT_AUTO_DECRYPT=true

# Disable auto-decryption (use manual helpers only)
CONFIGRYPT_AUTO_DECRYPT=false
```

**With Auto-Decryption Enabled:**
```php
// Works normally - env() returns decrypted values
$dbPassword = env('DB_PASSWORD');
$apiKey = env('STRIPE_SECRET');

// Config files work normally too
$dbPassword = config('database.connections.mysql.password');
```

**With Auto-Decryption Disabled:**
```php
// env() returns encrypted values, use helpers instead
$dbPassword = configrypt_env('DB_PASSWORD');  // Helper function
$apiKey = encrypted_env('STRIPE_SECRET');     // Helper alias
$jwtSecret = Str::decryptEnv('JWT_SECRET');   // Str macro
```

**Security Note**: Auto-decryption is secure because:
- Decryption only happens in memory during application bootstrap
- Decrypted values never touch disk storage
- Failed decryptions are handled gracefully without breaking the application
- Only values with the correct encryption prefix are processed

## Environment-Specific Configuration

You can use different configurations for different environments:

### Development (.env.local)
```env
CONFIGRYPT_KEY=development-key-32-characters
CONFIGRYPT_PREFIX=DEV_ENC:
CONFIGRYPT_AUTO_DECRYPT=true
```

### Staging (.env.staging)
```env
CONFIGRYPT_KEY=staging-key-32-characters-long--
CONFIGRYPT_PREFIX=ENC:
CONFIGRYPT_CIPHER=AES-256-CBC
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

## Usage Patterns by Configuration

### Auto-Decryption Enabled (Recommended)

Most seamless approach - existing code continues to work:

```env
CONFIGRYPT_AUTO_DECRYPT=true
```

```php
// Your existing code works without changes
$dbPassword = env('DB_PASSWORD');        // Returns decrypted value
$apiKey = config('services.stripe.secret'); // Returns decrypted value
```

### Auto-Decryption Disabled (Manual Control)

More explicit control over decryption:

```env
CONFIGRYPT_AUTO_DECRYPT=false
```

```php
// Use helper functions for decryption
$dbPassword = configrypt_env('DB_PASSWORD');
$apiKey = encrypted_env('STRIPE_SECRET');
$jwtSecret = Str::decryptEnv('JWT_SECRET');

// Or use facades
$dbPassword = ConfigryptEnv::get('DB_PASSWORD');
```

### Hybrid Approach (Migration)

Enable auto-decryption but use helpers for new code:

```env
CONFIGRYPT_AUTO_DECRYPT=true
```

```php
// Legacy code works normally
$dbPassword = env('DB_PASSWORD');

// New code uses explicit helpers
$apiKey = configrypt_env('STRIPE_SECRET');
```

## Security Best Practices

1. **Use Dedicated Keys**: Use a separate `CONFIGRYPT_KEY` instead of relying on `APP_KEY`
2. **Key Rotation**: Regularly rotate your encryption keys and re-encrypt values
3. **Environment Isolation**: Use different keys for different environments
4. **Key Management**: Store encryption keys securely (e.g., in a key vault or secrets manager)
5. **Strong Keys**: Use cryptographically secure random keys (32 characters for AES-256-CBC)
6. **Auto-Decryption Security**: When using auto-decryption, ensure your deployment environment is secure
7. **Monitoring**: Monitor decryption errors in production to catch configuration issues
8. **Backup Strategy**: Have a plan for key recovery and emergency access to encrypted values

## Troubleshooting Configuration

### Common Issues

**Auto-Decryption Not Working:**
- Ensure `CONFIGRYPT_AUTO_DECRYPT=true` is set
- Verify the encryption key is correct
- Check that encrypted values have the correct prefix

**Decryption Errors:**
- Verify the encryption key matches the key used for encryption
- Check that the cipher method matches
- Ensure the encrypted value hasn't been corrupted

**Performance Concerns:**
- Auto-decryption happens once during bootstrap, not on every request
- Consider disabling auto-decryption if you only need to decrypt a few values
- Use helper functions for fine-grained control

### Debug Mode

Enable Laravel's debug mode to see detailed error messages:

```env
APP_DEBUG=true
```

This will show detailed error messages for failed decryption attempts.

## Next Steps

- [Basic Usage](basic-usage.md) - Learn how to encrypt and use encrypted values
- [Security Considerations](security.md) - Learn about security best practices