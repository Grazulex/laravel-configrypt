# API Reference

Complete API documentation for Laravel Configrypt classes, helper functions, and methods.

## Helper Functions

Laravel Configrypt provides several global helper functions for easy access to decryption functionality.

### configrypt_env()

Primary helper function to get decrypted environment variables.

```php
function configrypt_env(string $key, mixed $default = null): mixed
```

**Parameters:**
- `$key` (string) - The environment variable key
- `$default` (mixed) - Default value if key doesn't exist or decryption fails

**Returns:**
- `mixed` - Decrypted value if encrypted, original value if not encrypted, or default on error

**Behavior:**
- Checks if the value has the encryption prefix
- Automatically decrypts encrypted values
- Returns original value if not encrypted
- Returns default value on decryption errors
- Errors are reported in debug mode, silently ignored in production

**Example:**
```php
// Basic usage
$dbPassword = configrypt_env('DB_PASSWORD');

// With default value
$apiKey = configrypt_env('API_KEY', 'default-key');

// Works with both encrypted and plain values
$encrypted = 'ENC:abc123';
$plain = 'plain-text';
configrypt_env('ENCRYPTED_VAR'); // Returns decrypted value
configrypt_env('PLAIN_VAR');     // Returns plain value
```

### encrypted_env()

Alias for `configrypt_env()` - provides a shorter name for consistency.

```php
function encrypted_env(string $key, mixed $default = null): mixed
```

**Parameters:**
- Same as `configrypt_env()`

**Returns:**
- Same as `configrypt_env()`

**Example:**
```php
// Identical to configrypt_env()
$password = encrypted_env('DB_PASSWORD');
$secret = encrypted_env('JWT_SECRET', 'fallback');
```

## Str Macro

Laravel Configrypt adds a macro to the `Str` class for easy migration from `env()` calls.

### Str::decryptEnv()

Macro added to `Illuminate\Support\Str` for decrypting environment variables.

```php
Str::macro('decryptEnv', function (string $key, $default = null))
```

**Parameters:**
- `$key` (string) - The environment variable key
- `$default` (mixed) - Default value if key doesn't exist

**Returns:**
- `mixed` - Decrypted value if encrypted, original value if not encrypted

**Example:**
```php
use Illuminate\Support\Str;

// Easy migration from env() calls
$password = Str::decryptEnv('DB_PASSWORD');
$apiKey = Str::decryptEnv('STRIPE_SECRET', 'default');

// Perfect for search & replace in codebase:
// Before: env('DB_PASSWORD')
// After:  Str::decryptEnv('DB_PASSWORD')
```

## EnvironmentDecryptor

Service class for handling environment variable decryption operations.

### Class: `LaravelConfigrypt\Support\EnvironmentDecryptor`

#### Constructor

```php
public function __construct(
    protected ConfigryptService $configryptService,
    protected string $prefix = 'ENC:'
)
```

**Parameters:**
- `$configryptService` (ConfigryptService) - The main encryption service
- `$prefix` (string) - The prefix for encrypted values

#### get()

Get and decrypt an environment variable if needed.

```php
public function get(string $key, mixed $default = null): mixed
```

**Parameters:**
- `$key` (string) - Environment variable key
- `$default` (mixed) - Default value on error or missing key

**Returns:**
- `mixed` - Decrypted value, original value, or default

**Example:**
```php
$decryptor = app(EnvironmentDecryptor::class);
$password = $decryptor->get('DB_PASSWORD');
```

#### isEncrypted()

Check if a value appears to be encrypted.

```php
public function isEncrypted(mixed $value): bool
```

**Parameters:**
- `$value` (mixed) - Value to check

**Returns:**
- `bool` - True if value is string and has encryption prefix

**Example:**
```php
$isEncrypted = $decryptor->isEncrypted('ENC:abc123'); // true
$isEncrypted = $decryptor->isEncrypted('plain-text'); // false
```

#### decryptAll()

Decrypt all encrypted environment variables in place.

```php
public function decryptAll(): void
```

**Behavior:**
- Modifies `$_ENV` and `putenv()` with decrypted values
- Processes all environment variables with encryption prefix
- Handles errors gracefully without breaking the application
- Does not affect Laravel's `env()` cache

**Example:**
```php
$decryptor->decryptAll(); // Decrypts all ENC: prefixed variables
```

#### getAllDecrypted()

Get all environment variables with encrypted values decrypted.

```php
public function getAllDecrypted(): array<string, mixed>
```

**Returns:**
- `array` - Array of all environment variables with encrypted values decrypted

**Example:**
```php
$allVars = $decryptor->getAllDecrypted();
// Returns associative array of all env vars with decrypted values
```

## ConfigryptEnv Facade

Laravel facade providing static access to EnvironmentDecryptor methods.

### Class: `LaravelConfigrypt\Facades\ConfigryptEnv`

#### get()

```php
public static function get(string $key, mixed $default = null): mixed
```

**Example:**
```php
use LaravelConfigrypt\Facades\ConfigryptEnv;

$password = ConfigryptEnv::get('DB_PASSWORD');
$apiKey = ConfigryptEnv::get('API_KEY', 'default');
```

#### isEncrypted()

```php
public static function isEncrypted(mixed $value): bool
```

**Example:**
```php
$isEncrypted = ConfigryptEnv::isEncrypted('ENC:abc123');
```

#### decryptAll()

```php
public static function decryptAll(): void
```

**Example:**
```php
ConfigryptEnv::decryptAll(); // Decrypt all encrypted environment variables
```

#### getAllDecrypted()

```php
public static function getAllDecrypted(): array<string, mixed>
```

**Example:**
```php
$allDecrypted = ConfigryptEnv::getAllDecrypted();
```

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

Registers services and handles early auto-decryption.

```php
public function register(): void
```

**Functionality:**
- Merges configuration from `src/Config/configrypt.php`
- Checks for auto-decryption setting and performs early decryption if enabled
- Registers `ConfigryptService` as singleton in service container
- Registers `EnvironmentDecryptor` as singleton
- Creates aliases: 'configrypt' and 'configrypt.env'

#### boot()

Bootstrap services and register additional features.

```php
public function boot(): void
```

**Functionality:**
- Publishes configuration file with tag 'configrypt-config'
- Adds Str macro for `decryptEnv` method
- Registers Artisan commands (`configrypt:encrypt`, `configrypt:decrypt`)

#### earlyAutoDecryptEnvironmentVariables()

Performs early auto-decryption during service provider registration.

```php
protected function earlyAutoDecryptEnvironmentVariables(): void
```

**Advanced Features:**
- Creates temporary ConfigryptService instance for early decryption
- Iterates through all `$_ENV` variables looking for encrypted values
- Decrypts values and updates `$_ENV`, `$_SERVER`, and `putenv()`
- **Clears Laravel's environment cache** using reflection to force re-reading
- Handles errors gracefully to prevent application crashes
- Only runs when `CONFIGRYPT_AUTO_DECRYPT=true`

**Cache Clearing Methods:**
- `clearLaravelEnvironmentCache()` - Clears specific key from cache
- `forceResetEnvironmentCache()` - Resets entire environment cache using reflection
- `reinitializeEnvRepository()` - Forces Laravel to recreate its environment repository

This innovative approach bypasses Laravel's early environment caching, making `env()` calls return decrypted values.

#### addConfigryptMacros()

Adds helpful macros to Laravel classes.

```php
protected function addConfigryptMacros(): void
```

**Adds:**
- `Str::decryptEnv()` macro for easy migration from `env()` calls

## Configuration

### Configuration Array Structure

```php
return [
    'key' => env('CONFIGRYPT_KEY', env('APP_KEY')),
    'prefix' => env('CONFIGRYPT_PREFIX', 'ENC:'),
    'cipher' => env('CONFIGRYPT_CIPHER', 'AES-256-CBC'),
    'auto_decrypt' => env('CONFIGRYPT_AUTO_DECRYPT', false),
];
```

#### Configuration Keys

- **key** (string) - Encryption key for encrypting/decrypting values
- **prefix** (string) - Prefix to identify encrypted values in environment variables  
- **cipher** (string) - Encryption cipher method ('AES-256-CBC' or 'AES-128-CBC')
- **auto_decrypt** (bool) - Whether to automatically decrypt environment variables during early bootstrap (default: false)

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
**Default:** `false`  
**Required:** No  

```env
CONFIGRYPT_AUTO_DECRYPT=true
```

**When enabled:**
- Encrypted environment variables are decrypted during service provider registration
- Laravel's environment cache is cleared to ensure `env()` returns decrypted values
- All `ENC:` prefixed values are processed automatically
- Enables seamless usage with existing `env()` and `config()` calls

**When disabled:**
- Use helper functions (`configrypt_env()`, `encrypted_env()`) for decryption
- Use facades (`ConfigryptEnv::get()`) for more control
- Use Str macro (`Str::decryptEnv()`) for easy migration

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

The ConfigryptService and EnvironmentDecryptor are automatically registered in Laravel's service container:

```php
// Resolve from container
$configrypt = app(ConfigryptService::class);
$envDecryptor = app(EnvironmentDecryptor::class);

// Or use dependency injection
class MyController extends Controller
{
    public function __construct(
        private ConfigryptService $configrypt,
        private EnvironmentDecryptor $envDecryptor
    ) {
    }
    
    public function example()
    {
        $encrypted = $this->configrypt->encrypt('secret');
        $decrypted = $this->envDecryptor->get('DB_PASSWORD');
    }
}

// Or use aliases
$configrypt = app('configrypt');
$envDecryptor = app('configrypt.env');
```

### Helper Functions

Helper functions are automatically available globally:

```php
// No need to import or register - available everywhere
$password = configrypt_env('DB_PASSWORD');
$secret = encrypted_env('JWT_SECRET');
```

### Str Macro

The Str macro is automatically registered:

```php
use Illuminate\Support\Str;

// Available after service provider boots
$password = Str::decryptEnv('DB_PASSWORD');
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
use LaravelConfigrypt\Support\EnvironmentDecryptor;

class ConfigryptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Override services for testing
        $this->app->singleton(ConfigryptService::class, function () {
            return new ConfigryptService(
                key: 'test-key-32-characters-long----',
                prefix: 'TEST_ENC:',
                cipher: 'AES-256-CBC'
            );
        });
        
        // Test auto-decryption
        $_ENV['CONFIGRYPT_AUTO_DECRYPT'] = 'true';
        $_ENV['CONFIGRYPT_KEY'] = 'test-key-32-characters-long----';
    }
    
    public function test_helper_functions(): void
    {
        $_ENV['TEST_VAR'] = 'TEST_ENC:encrypted-value';
        
        $decrypted = configrypt_env('TEST_VAR');
        $this->assertEquals('original-value', $decrypted);
    }
    
    public function test_str_macro(): void
    {
        $decrypted = Str::decryptEnv('TEST_VAR');
        $this->assertNotNull($decrypted);
    }
}
```

## Next Steps

- [Examples](../examples/README.md) - See practical usage examples
- [Security Considerations](security.md) - Learn about security best practices
- [Troubleshooting](troubleshooting.md) - Common issues and solutions