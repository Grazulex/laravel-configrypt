# API Reference

Complete API documentation for Laravel Configrypt classes and methods.

## ConfigryptService

The main service class that handles encryption and decryption operations.

### Class: `LaravelConfigrypt\Services\ConfigryptService`

#### Constructor

```php
public function __construct(
    protected ?string $key,
    protected string $prefix = 'ENC:',
    string $cipher = 'AES-256-CBC'
)
```

**Parameters:**
- `$key` (string|null) - The encryption key. Must be 32 characters for AES-256-CBC
- `$prefix` (string) - The prefix to identify encrypted values (default: 'ENC:')
- `$cipher` (string) - The cipher method (default: 'AES-256-CBC')

**Throws:**
- `InvalidArgumentException` - When the encryption key is empty or invalid

**Example:**
```php
$service = new ConfigryptService(
    key: 'your-32-character-encryption-key',
    prefix: 'ENC:',
    cipher: 'AES-256-CBC'
);
```

#### encrypt()

Encrypt a value with the configured prefix.

```php
public function encrypt(string $value): string
```

**Parameters:**
- `$value` (string) - The plain text value to encrypt

**Returns:**
- `string` - The encrypted value with prefix (e.g., "ENC:encrypted-data")

**Throws:**
- `Exception` - When encryption fails

**Example:**
```php
$encrypted = $service->encrypt('my-secret-value');
// Returns: "ENC:gk9AvRZgx6Jyds7K2uFctw=="
```

#### decrypt()

Decrypt a value, removing the prefix if present.

```php
public function decrypt(string $encryptedValue): string
```

**Parameters:**
- `$encryptedValue` (string) - The encrypted value (with or without prefix)

**Returns:**
- `string` - The decrypted plain text value

**Throws:**
- `Exception` - When decryption fails (invalid data, wrong key, etc.)

**Example:**
```php
$decrypted = $service->decrypt('ENC:gk9AvRZgx6Jyds7K2uFctw==');
// Returns: "my-secret-value"

// Also works without prefix
$decrypted = $service->decrypt('gk9AvRZgx6Jyds7K2uFctw==');
```

#### isEncrypted()

Check if a value is encrypted (has the prefix).

```php
public function isEncrypted(string $value): bool
```

**Parameters:**
- `$value` (string) - The value to check

**Returns:**
- `bool` - True if the value starts with the configured prefix

**Example:**
```php
$service->isEncrypted('ENC:some-encrypted-value'); // true
$service->isEncrypted('plain-text-value');         // false
```

#### getPrefix()

Get the encryption prefix.

```php
public function getPrefix(): string
```

**Returns:**
- `string` - The configured prefix

**Example:**
```php
$prefix = $service->getPrefix(); // "ENC:"
```

#### getKey()

Get the encryption key.

```php
public function getKey(): ?string
```

**Returns:**
- `string|null` - The encryption key (use with caution)

**Example:**
```php
$key = $service->getKey(); // "your-32-character-encryption-key"
```

## Configrypt Facade

Laravel facade providing static access to ConfigryptService methods.

### Class: `LaravelConfigrypt\Facades\Configrypt`

#### encrypt()

```php
public static function encrypt(string $value): string
```

**Example:**
```php
use LaravelConfigrypt\Facades\Configrypt;

$encrypted = Configrypt::encrypt('my-secret');
```

#### decrypt()

```php
public static function decrypt(string $encryptedValue): string
```

**Example:**
```php
$decrypted = Configrypt::decrypt('ENC:encrypted-value');
```

#### isEncrypted()

```php
public static function isEncrypted(string $value): bool
```

**Example:**
```php
$isEncrypted = Configrypt::isEncrypted('ENC:some-value');
```

#### getPrefix()

```php
public static function getPrefix(): string
```

**Example:**
```php
$prefix = Configrypt::getPrefix();
```

#### getKey()

```php
public static function getKey(): ?string
```

**Example:**
```php
$key = Configrypt::getKey();
```

## Artisan Commands

### EncryptCommand

Command to encrypt values from the command line.

#### Class: `LaravelConfigrypt\Commands\EncryptCommand`

```php
protected $signature = 'configrypt:encrypt {value : The value to encrypt}';
protected $description = 'Encrypt a value for use in .env files';
```

**Usage:**
```bash
php artisan configrypt:encrypt "value-to-encrypt"
```

#### handle()

```php
public function handle(ConfigryptService $configrypt): int
```

**Returns:**
- `int` - Command exit code (0 for success, 1 for failure)

### DecryptCommand

Command to decrypt values from the command line.

#### Class: `LaravelConfigrypt\Commands\DecryptCommand`

```php
protected $signature = 'configrypt:decrypt {value : The encrypted value to decrypt}';
protected $description = 'Decrypt an encrypted value';
```

**Usage:**
```bash
php artisan configrypt:decrypt "ENC:encrypted-value"
```

#### handle()

```php
public function handle(ConfigryptService $configrypt): int
```

**Returns:**
- `int` - Command exit code (0 for success, 1 for failure)

## Service Provider

### LaravelConfigryptServiceProvider

Registers the ConfigryptService and handles auto-decryption.

#### Class: `LaravelConfigrypt\LaravelConfigryptServiceProvider`

#### register()

Registers the ConfigryptService in the service container.

```php
public function register(): void
```

- Merges configuration from `config/configrypt.php`
- Registers `ConfigryptService` as singleton
- Creates alias 'configrypt' for the service

#### boot()

Bootstrap services and handle auto-decryption.

```php
public function boot(): void
```

- Publishes configuration file
- Enables auto-decryption if configured
- Registers Artisan commands

#### autoDecryptEnvironmentVariables()

Automatically decrypt environment variables with the configured prefix.

```php
protected function autoDecryptEnvironmentVariables(): void
```

This method:
- Iterates through all environment variables
- Identifies encrypted values by prefix
- Decrypts them and updates `$_ENV` and `putenv()`
- Handles errors gracefully to prevent application crashes

## Configuration

### Configuration Array Structure

```php
return [
    'key' => env('CONFIGRYPT_KEY', env('APP_KEY')),
    'prefix' => env('CONFIGRYPT_PREFIX', 'ENC:'),
    'cipher' => env('CONFIGRYPT_CIPHER', 'AES-256-CBC'),
    'auto_decrypt' => env('CONFIGRYPT_AUTO_DECRYPT', true),
];
```

#### Configuration Keys

- **key** (string) - Encryption key for encrypting/decrypting values
- **prefix** (string) - Prefix to identify encrypted values in environment variables
- **cipher** (string) - Encryption cipher method ('AES-256-CBC' or 'AES-128-CBC')
- **auto_decrypt** (bool) - Whether to automatically decrypt environment variables during bootstrap

## Environment Variables

### CONFIGRYPT_KEY

The encryption key used for encrypting and decrypting values.

**Type:** String  
**Required:** Yes (or APP_KEY must be set)  
**Length:** 32 characters for AES-256-CBC  

```env
CONFIGRYPT_KEY=your-32-character-encryption-key
```

### CONFIGRYPT_PREFIX

The prefix used to identify encrypted values.

**Type:** String  
**Default:** `ENC:`  
**Required:** No  

```env
CONFIGRYPT_PREFIX=ENCRYPTED:
```

### CONFIGRYPT_CIPHER

The encryption cipher method.

**Type:** String  
**Default:** `AES-256-CBC`  
**Allowed Values:** `AES-256-CBC`, `AES-128-CBC`  

```env
CONFIGRYPT_CIPHER=AES-256-CBC
```

### CONFIGRYPT_AUTO_DECRYPT

Whether to automatically decrypt environment variables during application bootstrap.

**Type:** Boolean  
**Default:** `true`  
**Required:** No  

```env
CONFIGRYPT_AUTO_DECRYPT=true
```

## Exceptions

### InvalidArgumentException

Thrown when invalid parameters are provided to the ConfigryptService constructor.

**Common Causes:**
- Empty or null encryption key
- Invalid cipher method

**Example:**
```php
try {
    $service = new ConfigryptService('');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // "Encryption key cannot be empty. Please set CONFIGRYPT_KEY or APP_KEY."
}
```

### DecryptException

Thrown when decryption fails.

**Common Causes:**
- Invalid encrypted data
- Wrong encryption key
- Corrupted data

**Example:**
```php
try {
    $decrypted = Configrypt::decrypt('invalid-data');
} catch (DecryptException $e) {
    echo $e->getMessage(); // "The payload is invalid."
}
```

## Type Hints and Return Types

All methods use strict typing:

```php
// Service methods
public function encrypt(string $value): string
public function decrypt(string $encryptedValue): string
public function isEncrypted(string $value): bool
public function getPrefix(): string
public function getKey(): ?string

// Constructor
public function __construct(
    protected ?string $key,
    protected string $prefix = 'ENC:',
    string $cipher = 'AES-256-CBC'
)
```

## Laravel Integration

### Service Container

The ConfigryptService is automatically registered in Laravel's service container:

```php
// Resolve from container
$configrypt = app(ConfigryptService::class);

// Or use dependency injection
class MyController extends Controller
{
    public function __construct(private ConfigryptService $configrypt)
    {
    }
}
```

### Configuration Publishing

Publish the configuration file:

```bash
php artisan vendor:publish --tag=configrypt-config
```

This publishes `config/configrypt.php` from the package.

### Auto-Discovery

The package uses Laravel's auto-discovery feature, so no manual registration is required in `config/app.php`.

## Testing

### PHPUnit Integration

```php
use LaravelConfigrypt\Services\ConfigryptService;

class ConfigryptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Override service for testing
        $this->app->singleton(ConfigryptService::class, function () {
            return new ConfigryptService(
                key: 'test-key-32-characters-long----',
                prefix: 'TEST_ENC:',
                cipher: 'AES-256-CBC'
            );
        });
    }
}
```

## Next Steps

- [Examples](../examples/README.md) - See practical usage examples
- [Security Considerations](security.md) - Learn about security best practices
- [Troubleshooting](troubleshooting.md) - Common issues and solutions