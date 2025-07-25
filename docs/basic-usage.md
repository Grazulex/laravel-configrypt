# Basic Usage

This guide covers the fundamental usage patterns of Laravel Configrypt for encrypting and decrypting environment variables.

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

### Automatic Decryption

With `auto_decrypt` enabled (default), encrypted values are automatically decrypted when accessed:

```php
// These will automatically return the decrypted values
$dbPassword = env('DB_PASSWORD');           // Returns decrypted value
$stripeSecret = env('STRIPE_SECRET');       // Returns decrypted value

// Also works with config files
$dbPassword = config('database.connections.mysql.password');
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
    'password' => env('DB_PASSWORD', ''), // Automatically decrypted
],

// config/services.php
'stripe' => [
    'model' => App\Models\User::class,
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'), // Automatically decrypted
],
```

### Manual Decryption

If you have `auto_decrypt` disabled, or need to decrypt values manually:

```php
use LaravelConfigrypt\Facades\Configrypt;

// Get the encrypted value from environment
$encryptedValue = env('SOME_ENCRYPTED_VALUE');

// Manually decrypt it
$decryptedValue = Configrypt::decrypt($encryptedValue);
```

## Common Use Cases

### Database Credentials

```bash
# Encrypt database password
php artisan configrypt:encrypt "super-secret-db-password"
```

```env
DB_PASSWORD=ENC:generated-encrypted-value-here
```

```php
// Use in database configuration
'password' => env('DB_PASSWORD'), // Automatically decrypted
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

### Auto-Decryption Error Handling

When `auto_decrypt` is enabled, decryption errors are handled silently to prevent application crashes:

- In debug mode: Errors are reported to Laravel's error handler
- In production: Errors are silently ignored, and the original encrypted value is returned

## Best Practices

1. **Always Use HTTPS**: Ensure encrypted values are transmitted securely
2. **Rotate Keys Regularly**: Change encryption keys periodically
3. **Environment Separation**: Use different keys for different environments
4. **Backup Strategy**: Have a plan for key recovery
5. **Audit Trail**: Log when encryption/decryption operations occur
6. **Test Thoroughly**: Verify encrypted values work in all environments

## Next Steps

- [Advanced Usage](advanced-usage.md) - Learn about advanced features and patterns
- [Artisan Commands](artisan-commands.md) - Detailed command reference
- [Examples](../examples/README.md) - See practical examples