# Basic Usage

This guide covers the fundamental usage patterns of Laravel Configrypt for encrypting and decrypting environment variables.

## Quick Start Options

Laravel Configrypt provides **multiple ways** to access decrypted values, each suited for different migration strategies and use cases.

### Option 1: Auto-Decryption (Recommended)

Enable automatic decryption to make encrypted environment variables work seamlessly with Laravel's `env()` function:

```bash
# Add to .env file
CONFIGRYPT_AUTO_DECRYPT=true
```

With auto-decryption enabled, your existing code continues to work:

```php
// Your existing env() calls work normally
$dbPassword = env('DB_PASSWORD');        // Returns decrypted value
$apiKey = env('STRIPE_SECRET');         // Returns decrypted value
$jwtSecret = env('JWT_SECRET');         // Returns decrypted value
```

### Option 2: Helper Functions

Use the dedicated helper functions for explicit decryption:

```php
// Primary helper function
$dbPassword = configrypt_env('DB_PASSWORD');
$apiKey = configrypt_env('STRIPE_SECRET', 'default-fallback');

// Alias for consistency
$jwtSecret = encrypted_env('JWT_SECRET');
```

### Option 3: Str Macro (Easy Migration)

Use the `Str` macro for easy migration from existing `env()` calls:

```php
use Illuminate\Support\Str;

$dbPassword = Str::decryptEnv('DB_PASSWORD');
$apiKey = Str::decryptEnv('STRIPE_SECRET');
```

### Option 4: Facades

Use the facades for more control and additional functionality:

```php
use LaravelConfigrypt\Facades\ConfigryptEnv;
use LaravelConfigrypt\Facades\Configrypt;

// Environment-specific facade
$dbPassword = ConfigryptEnv::get('DB_PASSWORD');

// Main encryption facade  
$isEncrypted = Configrypt::isEncrypted($someValue);
$decrypted = Configrypt::decrypt($encryptedValue);
```

## Encrypting Values

### Using Artisan Command

The easiest way to encrypt a value is using the provided Artisan command:

```bash
php artisan configrypt:encrypt "my-secret-value"
```

Output:
```
Encrypted value:
ENC:gk9AvRZgx6Jyds7K2uFctw==

You can now use this encrypted value in your .env file:
SOME_SECRET=ENC:gk9AvRZgx6Jyds7K2uFctw==
```

### Using the Facade

You can also encrypt values programmatically using the `Configrypt` facade:

```php
use LaravelConfigrypt\Facades\Configrypt;

$encrypted = Configrypt::encrypt('my-secret-value');
// Returns: ENC:gk9AvRZgx6Jyds7K2uFctw==
```

### Using the Service

Inject the service directly:

```php
use LaravelConfigrypt\Services\ConfigryptService;

class MyController extends Controller
{
    public function __construct(private ConfigryptService $configrypt)
    {
    }

    public function encryptValue()
    {
        $encrypted = $this->configrypt->encrypt('my-secret-value');
        return $encrypted;
    }
}
```

## Adding Encrypted Values to .env

Once you have an encrypted value, add it to your `.env` file:

```env
# Database credentials
DB_PASSWORD=ENC:gk9AvRZgx6Jyds7K2uFctw==

# API keys
STRIPE_SECRET=ENC:AbCdEfGhIjKlMnOpQrStUvWxYz==
MAILGUN_SECRET=ENC:XyZ123AbC456DeF789GhI012JkL==

# Other sensitive data
JWT_SECRET=ENC:MnOpQrStUvWxYzAbCdEfGhIjKl==
```

## Using Encrypted Values

### Method 1: Auto-Decryption (Recommended)

With `CONFIGRYPT_AUTO_DECRYPT=true` in your `.env` file, encrypted values are automatically decrypted during early application bootstrap:

```php
// Your existing env() calls work normally - no code changes needed!
$dbPassword = env('DB_PASSWORD');           // Returns decrypted value
$stripeSecret = env('STRIPE_SECRET');       // Returns decrypted value

// Also works with config files
$dbPassword = config('database.connections.mysql.password');
```

**How Auto-Decryption Works:**
- Decryption happens during service provider registration (very early in Laravel's boot process)
- All `ENC:` prefixed environment variables are decrypted
- Values are updated in `$_ENV`, `$_SERVER`, and via `putenv()`
- Laravel's environment cache is cleared to ensure `env()` returns decrypted values
- Errors are handled gracefully (silent in production, logged in debug mode)

### Method 2: Helper Functions

Use the helper functions for explicit control over decryption:

```php
// Primary helper (works with or without auto-decrypt)
$dbPassword = configrypt_env('DB_PASSWORD');
$stripeSecret = configrypt_env('STRIPE_SECRET', 'default-value');

// Alias for consistency
$jwtSecret = encrypted_env('JWT_SECRET');
```

### Method 3: Str Macro Migration

Perfect for migrating existing codebases:

```php
use Illuminate\Support\Str;

// Easy search and replace from env() to Str::decryptEnv()
$dbPassword = Str::decryptEnv('DB_PASSWORD');
$apiKey = Str::decryptEnv('API_KEY');
```

### Method 4: Facades for Advanced Usage

```php
use LaravelConfigrypt\Facades\ConfigryptEnv;
use LaravelConfigrypt\Facades\Configrypt;

// Environment-specific operations
$dbPassword = ConfigryptEnv::get('DB_PASSWORD');
$allDecrypted = ConfigryptEnv::getAllDecrypted();

// Check if auto-decryption missed anything
ConfigryptEnv::decryptAll();

// Manual encryption/decryption operations
$encrypted = Configrypt::encrypt('new-secret-value');
$isEncrypted = Configrypt::isEncrypted($someValue);
```

### In Configuration Files

Encrypted environment variables work seamlessly with Laravel's configuration system:

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''), // Auto-decrypted or use configrypt_env()
],

// config/services.php
'stripe' => [
    'model' => App\Models\User::class,
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'), // Auto-decrypted or use configrypt_env()
],
```

**Multiple Approaches for Config Files:**

```php
// Option A: Auto-decrypt enabled (set CONFIGRYPT_AUTO_DECRYPT=true)
'password' => env('DB_PASSWORD'), // Works normally

// Option B: Use helper function (always works)
'password' => configrypt_env('DB_PASSWORD'),

// Option C: Use Str macro  
'password' => Str::decryptEnv('DB_PASSWORD'),

// Option D: Use facade
'password' => ConfigryptEnv::get('DB_PASSWORD'),
```

### Manual Decryption

If you have `auto_decrypt` disabled, or need to decrypt values manually:

```php
use LaravelConfigrypt\Facades\Configrypt;

// Get the encrypted value from environment  
$encryptedValue = env('SOME_ENCRYPTED_VALUE'); // Still encrypted

// Manually decrypt it
if (Configrypt::isEncrypted($encryptedValue)) {
    $decryptedValue = Configrypt::decrypt($encryptedValue);
} else {
    $decryptedValue = $encryptedValue; // Not encrypted
}

// Or use the helper function which handles this automatically
$decryptedValue = configrypt_env('SOME_ENCRYPTED_VALUE');
```

## Common Use Cases

### Database Credentials

```bash
# Encrypt database password
php artisan configrypt:encrypt "super-secret-db-password"
```

```env
DB_PASSWORD=ENC:generated-encrypted-value-here
CONFIGRYPT_AUTO_DECRYPT=true
```

```php
// Use in database configuration (multiple options)

// Option A: Auto-decrypt enabled - works normally
'password' => env('DB_PASSWORD'),

// Option B: Use helper function
'password' => configrypt_env('DB_PASSWORD'),

// Option C: Use Str macro for migration
'password' => Str::decryptEnv('DB_PASSWORD'),
```

### API Keys and Secrets

```bash
# Encrypt API keys
php artisan configrypt:encrypt "sk_live_abcdef123456"
php artisan configrypt:encrypt "key-1234567890abcdef"
```

```env
STRIPE_SECRET=ENC:stripe-encrypted-value
MAILGUN_SECRET=ENC:mailgun-encrypted-value
```

### Third-Party Service Credentials

```bash
# Encrypt OAuth secrets
php artisan configrypt:encrypt "oauth-client-secret"
php artisan configrypt:encrypt "jwt-signing-key"
```

```env
OAUTH_CLIENT_SECRET=ENC:oauth-encrypted-value
JWT_SECRET=ENC:jwt-encrypted-value
```

## Checking if a Value is Encrypted

You can check if a value is encrypted using the `isEncrypted` method:

```php
use LaravelConfigrypt\Facades\Configrypt;

$value1 = 'ENC:gk9AvRZgx6Jyds7K2uFctw==';
$value2 = 'plain-text-value';

Configrypt::isEncrypted($value1); // true
Configrypt::isEncrypted($value2); // false
```

## Decrypting Values

### Using Artisan Command

```bash
php artisan configrypt:decrypt "ENC:gk9AvRZgx6Jyds7K2uFctw=="
```

Output:
```
Decrypted value:
my-secret-value
```

### Using the Facade

```php
use LaravelConfigrypt\Facades\Configrypt;

$decrypted = Configrypt::decrypt('ENC:gk9AvRZgx6Jyds7K2uFctw==');
// Returns: my-secret-value
```

## Error Handling

Laravel Configrypt handles errors gracefully:

```php
use LaravelConfigrypt\Facades\Configrypt;

try {
    $decrypted = Configrypt::decrypt('invalid-encrypted-value');
} catch (Exception $e) {
    // Handle decryption error
    Log::error('Failed to decrypt value: ' . $e->getMessage());
}
```

### Error Handling

Laravel Configrypt handles errors gracefully:

```php
use LaravelConfigrypt\Facades\Configrypt;

try {
    $decrypted = Configrypt::decrypt('invalid-encrypted-value');
} catch (Exception $e) {
    // Handle decryption error
    Log::error('Failed to decrypt value: ' . $e->getMessage());
}

// Helper functions handle errors automatically
$value = configrypt_env('SOME_KEY', 'fallback-value'); // Returns fallback on error
```

### Auto-Decryption Error Handling

When `CONFIGRYPT_AUTO_DECRYPT=true`, decryption errors are handled silently to prevent application crashes:

- **Debug mode** (`APP_DEBUG=true`): Errors are reported to Laravel's error handler for debugging
- **Production mode**: Errors are silently ignored, and the original encrypted value is preserved
- **Graceful fallback**: Invalid encrypted values remain unchanged rather than breaking the application
- **Individual handling**: Helper functions like `configrypt_env()` provide fallback values for failed decryptions

### Migration Considerations

When migrating from plain to encrypted environment variables:

```php
// Safe migration pattern using helper functions
$dbPassword = configrypt_env('DB_PASSWORD', env('DB_PASSWORD'));

// This will:
// 1. Try to decrypt if the value has ENC: prefix
// 2. Fall back to original env() value if not encrypted
// 3. Allow gradual migration of environment variables
```

## Best Practices

1. **Enable Auto-Decryption**: Set `CONFIGRYPT_AUTO_DECRYPT=true` for seamless integration with existing code
2. **Use Helper Functions**: For new code, prefer `configrypt_env()` over `env()` for explicit decryption control
3. **Environment Separation**: Use different keys for different environments (dev/staging/production)
4. **Always Use HTTPS**: Ensure encrypted values are transmitted securely
5. **Rotate Keys Regularly**: Change encryption keys periodically and re-encrypt values
6. **Backup Strategy**: Have a plan for key recovery and emergency access
7. **Audit Trail**: Log when encryption/decryption operations occur in production
8. **Test Thoroughly**: Verify encrypted values work in all environments and deployment scenarios
9. **Gradual Migration**: Use helper functions to migrate gradually from plain to encrypted values
10. **Error Monitoring**: Monitor decryption errors in production to catch configuration issues early

## Next Steps

- [Advanced Usage](advanced-usage.md) - Learn about advanced features and patterns
- [Artisan Commands](artisan-commands.md) - Detailed command reference
- [Examples](../examples/README.md) - See practical examples