# Advanced Usage

This guide covers advanced usage patterns and features of Laravel Configrypt for complex scenarios and integrations.

## Service Container Integration

### Dependency Injection

You can inject both `ConfigryptService` and `EnvironmentDecryptor` into any class that's resolved through Laravel's service container:

```php
use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Support\EnvironmentDecryptor;

class EncryptionController extends Controller
{
    public function __construct(
        private ConfigryptService $configrypt,
        private EnvironmentDecryptor $envDecryptor
    ) {}

    public function encrypt(Request $request)
    {
        $encrypted = $this->configrypt->encrypt($request->value);
        
        return response()->json([
            'encrypted' => $encrypted,
            'prefix' => $this->configrypt->getPrefix()
        ]);
    }
    
    public function getEnvironmentValue(string $key)
    {
        // Use EnvironmentDecryptor for environment-specific operations
        $value = $this->envDecryptor->get($key);
        
        return response()->json(['value' => $value]);
    }
}
```

### Service Resolution

Resolve services manually when needed:

```php
// Main encryption service
$configrypt = app(ConfigryptService::class);
$encrypted = $configrypt->encrypt('secret-value');

// Environment decryption service
$envDecryptor = app(EnvironmentDecryptor::class);
$dbPassword = $envDecryptor->get('DB_PASSWORD');

// Using aliases
$configrypt = app('configrypt');
$envDecryptor = app('configrypt.env');
```

## Multiple Access Patterns

### Pattern 1: Auto-Decryption (Recommended for Most Use Cases)

Enable auto-decryption for seamless integration:

```php
// .env file
CONFIGRYPT_AUTO_DECRYPT=true
DB_PASSWORD=ENC:encrypted-password

// Your existing code works normally
class DatabaseManager
{
    public function connect()
    {
        $config = [
            'host' => env('DB_HOST'),
            'password' => env('DB_PASSWORD'), // Returns decrypted value automatically
            'username' => env('DB_USERNAME'),
        ];
        
        return new PDO($this->buildDsn($config), $config['username'], $config['password']);
    }
}
```

### Pattern 2: Explicit Helpers (Recommended for New Code)

Use helper functions for explicit control:

```php
class ApiKeyManager
{
    public function getStripeSecret(): string
    {
        // Primary helper function
        return configrypt_env('STRIPE_SECRET', 'default-key');
    }
    
    public function getMailgunKey(): string
    {
        // Alias helper function
        return encrypted_env('MAILGUN_SECRET');
    }
    
    public function getJwtSecret(): string
    {
        // Str macro for easy migration
        return Str::decryptEnv('JWT_SECRET');
    }
}
```

### Pattern 3: Facade Usage

Use facades for more advanced operations:

```php
use LaravelConfigrypt\Facades\Configrypt;
use LaravelConfigrypt\Facades\ConfigryptEnv;

class ConfigurationService
{
    public function encryptSensitiveConfig(array $config): array
    {
        foreach ($config as $key => $value) {
            if ($this->isSensitive($key)) {
                $config[$key] = Configrypt::encrypt($value);
            }
        }
        
        return $config;
    }
    
    public function getAllDecryptedEnvVars(): array
    {
        // Get all environment variables with encrypted values decrypted
        return ConfigryptEnv::getAllDecrypted();
    }
    
    public function forceDecryptAll(): void
    {
        // Manually decrypt all ENC: prefixed environment variables
        ConfigryptEnv::decryptAll();
    }
}
```

## Advanced Auto-Decryption Features

### Understanding the Auto-Decryption Process

Laravel Configrypt uses an innovative approach to bypass Laravel's environment caching:

```php
// How auto-decryption works internally:

// 1. Early execution during service provider registration
public function register(): void
{
    if ($_ENV['CONFIGRYPT_AUTO_DECRYPT'] === 'true') {
        $this->earlyAutoDecryptEnvironmentVariables();
    }
    // ... rest of registration
}

// 2. Decrypts all ENC: prefixed variables
private function earlyAutoDecryptEnvironmentVariables(): void
{
    foreach ($_ENV as $key => $value) {
        if (str_starts_with($value, 'ENC:')) {
            $decrypted = $this->decrypt($value);
            
            // Update all possible sources
            $_ENV[$key] = $decrypted;
            $_SERVER[$key] = $decrypted;
            putenv("{$key}={$decrypted}");
            
            // Clear Laravel's environment cache
            $this->clearLaravelEnvironmentCache($key);
        }
    }
}
```

### Custom Auto-Decryption Configuration

```php
class CustomAutoDecryption
{
    public function configureConditionalAutoDecryption(): void
    {
        // Only enable auto-decryption in specific environments
        $autoDecrypt = in_array(app()->environment(), ['local', 'staging', 'production']);
        
        config(['configrypt.auto_decrypt' => $autoDecrypt]);
        
        if ($autoDecrypt) {
            // Force auto-decryption even if not enabled in env
            $this->triggerAutoDecryption();
        }
    }
    
    private function triggerAutoDecryption(): void
    {
        $envDecryptor = app(EnvironmentDecryptor::class);
        $envDecryptor->decryptAll();
    }
}
```

## Facade Usage Patterns

### Enhanced Facade Methods

```php
use LaravelConfigrypt\Facades\Configrypt;
use LaravelConfigrypt\Facades\ConfigryptEnv;

// Basic encryption operations
$encrypted = Configrypt::encrypt('my-secret');
$decrypted = Configrypt::decrypt('ENC:encrypted-value');
$isEncrypted = Configrypt::isEncrypted('ENC:some-value');
$prefix = Configrypt::getPrefix();

// Environment-specific operations
$dbPassword = ConfigryptEnv::get('DB_PASSWORD');
$allDecrypted = ConfigryptEnv::getAllDecrypted();
ConfigryptEnv::decryptAll(); // Decrypt all ENC: prefixed variables
$isEncryptedEnv = ConfigryptEnv::isEncrypted($_ENV['SOME_VAR']);
```

### Conditional Encryption and Smart Helpers

```php
use LaravelConfigrypt\Facades\Configrypt;

class SmartConfigHelper
{
    public static function smartEncrypt(string $value): string
    {
        // Only encrypt if not already encrypted
        if (Configrypt::isEncrypted($value)) {
            return $value;
        }
        
        return Configrypt::encrypt($value);
    }
    
    public static function smartDecrypt(string $value, ?string $default = null): ?string
    {
        // Handle both encrypted and plain values gracefully
        if (Configrypt::isEncrypted($value)) {
            try {
                return Configrypt::decrypt($value);
            } catch (Exception $e) {
                return $default;
            }
        }
        
        return $value;
    }
    
    public static function getValueSafely(string $key, ?string $default = null): ?string
    {
        // Try multiple approaches for maximum compatibility
        
        // 1. Try helper function first (recommended)
        try {
            return configrypt_env($key, $default);
        } catch (Exception $e) {
            // Continue to next approach
        }
        
        // 2. Try environment decryptor facade
        try {
            return ConfigryptEnv::get($key, $default);
        } catch (Exception $e) {
            // Continue to next approach
        }
        
        // 3. Try manual approach
        $value = env($key);
        if (Configrypt::isEncrypted($value)) {
            try {
                return Configrypt::decrypt($value);
            } catch (Exception $e) {
                return $default;
            }
        }
        
        return $value ?? $default;
    }
}
```

## Custom Configuration Scenarios

### Runtime Configuration Changes

```php
use LaravelConfigrypt\Services\ConfigryptService;

class DynamicConfigService
{
    public function updateEncryptionSettings(array $settings): void
    {
        // Update configuration at runtime
        config(['configrypt.prefix' => $settings['prefix'] ?? 'ENC:']);
        config(['configrypt.cipher' => $settings['cipher'] ?? 'AES-256-CBC']);
        
        // Re-bind service with new settings
        app()->singleton(ConfigryptService::class, function () use ($settings) {
            return new ConfigryptService(
                key: $settings['key'] ?? config('configrypt.key'),
                prefix: $settings['prefix'] ?? config('configrypt.prefix'),
                cipher: $settings['cipher'] ?? config('configrypt.cipher')
            );
        });
    }
}
```

### Multiple Encryption Contexts

```php
use LaravelConfigrypt\Services\ConfigryptService;

class MultiContextEncryption
{
    private ConfigryptService $userEncryption;
    private ConfigryptService $systemEncryption;
    private ConfigryptService $apiEncryption;
    
    public function __construct()
    {
        // User-specific encryption (for user data)
        $this->userEncryption = new ConfigryptService(
            key: config('app.user_encryption_key'),
            prefix: 'USER_ENC:',
            cipher: 'AES-256-CBC'
        );
        
        // System-level encryption (for system configurations)
        $this->systemEncryption = new ConfigryptService(
            key: config('app.system_encryption_key'),
            prefix: 'SYS_ENC:',
            cipher: 'AES-256-CBC'
        );
        
        // API-specific encryption (for external API keys)
        $this->apiEncryption = new ConfigryptService(
            key: config('app.api_encryption_key'),
            prefix: 'API_ENC:',
            cipher: 'AES-256-CBC'
        );
    }
    
    public function encryptUserData(string $data): string
    {
        return $this->userEncryption->encrypt($data);
    }
    
    public function encryptSystemData(string $data): string
    {
        return $this->systemEncryption->encrypt($data);
    }
    
    public function encryptApiKey(string $key): string
    {
        return $this->apiEncryption->encrypt($key);
    }
    
    public function decryptByPrefix(string $encryptedValue): string
    {
        // Auto-detect and use appropriate decryptor based on prefix
        if (str_starts_with($encryptedValue, 'USER_ENC:')) {
            return $this->userEncryption->decrypt($encryptedValue);
        } elseif (str_starts_with($encryptedValue, 'SYS_ENC:')) {
            return $this->systemEncryption->decrypt($encryptedValue);
        } elseif (str_starts_with($encryptedValue, 'API_ENC:')) {
            return $this->apiEncryption->decrypt($encryptedValue);
        } else {
            // Default to main service
            return app(ConfigryptService::class)->decrypt($encryptedValue);
        }
    }
}
```

## Integration with External Systems

### Key Management Systems

```php
use LaravelConfigrypt\Services\ConfigryptService;

class KeyVaultIntegration
{
    public function createEncryptorFromVault(string $keyId): ConfigryptService
    {
        // Retrieve encryption key from external vault
        $key = $this->retrieveKeyFromVault($keyId);
        
        return new ConfigryptService(
            key: $key,
            prefix: 'VAULT_ENC:',
            cipher: 'AES-256-CBC'
        );
    }
    
    private function retrieveKeyFromVault(string $keyId): string
    {
        // Implementation depends on your key vault system
        // e.g., AWS KMS, HashiCorp Vault, Azure Key Vault
        return $this->vaultClient->getKey($keyId);
    }
}
```

### CI/CD Pipeline Integration

```php
class CIPipelineEncryption
{
    public function encryptForEnvironment(string $value, string $environment): string
    {
        $key = $this->getEnvironmentKey($environment);
        
        $encryptor = new ConfigryptService(
            key: $key,
            prefix: strtoupper($environment) . '_ENC:',
            cipher: 'AES-256-CBC'
        );
        
        return $encryptor->encrypt($value);
    }
    
    private function getEnvironmentKey(string $environment): string
    {
        return match($environment) {
            'production' => env('PROD_ENCRYPTION_KEY'),
            'staging' => env('STAGING_ENCRYPTION_KEY'),
            'development' => env('DEV_ENCRYPTION_KEY'),
            default => throw new InvalidArgumentException("Unknown environment: {$environment}")
        };
    }
}
```

## Batch Operations

### Bulk Encryption

```php
use LaravelConfigrypt\Facades\Configrypt;

class BulkEncryption
{
    public function encryptConfiguration(array $config): array
    {
        $encrypted = [];
        
        foreach ($config as $key => $value) {
            if ($this->shouldEncrypt($key)) {
                $encrypted[$key] = Configrypt::encrypt($value);
            } else {
                $encrypted[$key] = $value;
            }
        }
        
        return $encrypted;
    }
    
    private function shouldEncrypt(string $key): bool
    {
        $sensitiveKeys = [
            'password', 'secret', 'key', 'token', 'credential'
        ];
        
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (str_contains(strtolower($key), $sensitiveKey)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function generateEnvFile(array $config): string
    {
        $lines = [];
        
        foreach ($config as $key => $value) {
            $lines[] = "{$key}={$value}";
        }
        
        return implode(PHP_EOL, $lines);
    }
}
```

### Migration from Plain to Encrypted

```php
use LaravelConfigrypt\Facades\Configrypt;

class EncryptionMigration
{
    public function migrateEnvironmentFile(string $envPath): void
    {
        $content = file_get_contents($envPath);
        $lines = explode(PHP_EOL, $content);
        $updated = [];
        
        foreach ($lines as $line) {
            if ($this->isEnvironmentVariable($line)) {
                [$key, $value] = explode('=', $line, 2);
                
                if ($this->shouldEncrypt($key) && !Configrypt::isEncrypted($value)) {
                    $value = Configrypt::encrypt($value);
                }
                
                $updated[] = "{$key}={$value}";
            } else {
                $updated[] = $line;
            }
        }
        
        file_put_contents($envPath, implode(PHP_EOL, $updated));
    }
    
    private function isEnvironmentVariable(string $line): bool
    {
        return str_contains($line, '=') && !str_starts_with(trim($line), '#');
    }
    
    private function shouldEncrypt(string $key): bool
    {
        $encryptPatterns = [
            '/.*PASSWORD.*/',
            '/.*SECRET.*/',
            '/.*KEY.*/',
            '/.*TOKEN.*/',
            '/.*CREDENTIAL.*/'
        ];
        
        foreach ($encryptPatterns as $pattern) {
            if (preg_match($pattern, strtoupper($key))) {
                return true;
            }
        }
        
        return false;
    }
}
```

## Performance Considerations

### Caching Decrypted Values

```php
use Illuminate\Support\Facades\Cache;
use LaravelConfigrypt\Facades\Configrypt;

class CachedDecryption
{
    public function getDecryptedValue(string $key, int $ttl = 3600): string
    {
        $cacheKey = "decrypted_{$key}";
        
        return Cache::remember($cacheKey, $ttl, function () use ($key) {
            $encryptedValue = env($key);
            
            if (Configrypt::isEncrypted($encryptedValue)) {
                return Configrypt::decrypt($encryptedValue);
            }
            
            return $encryptedValue;
        });
    }
    
    public function clearDecryptionCache(): void
    {
        Cache::flush(); // Or more specific cache clearing
    }
}
```

### Lazy Loading

```php
class LazyConfigrypt
{
    private ?ConfigryptService $service = null;
    
    private function getService(): ConfigryptService
    {
        if ($this->service === null) {
            $this->service = app(ConfigryptService::class);
        }
        
        return $this->service;
    }
    
    public function encrypt(string $value): string
    {
        return $this->getService()->encrypt($value);
    }
    
    public function decrypt(string $value): string
    {
        return $this->getService()->decrypt($value);
    }
}
```

## Testing with Encrypted Values

### Test Configuration

```php
// tests/TestCase.php
use LaravelConfigrypt\Services\ConfigryptService;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use a test-specific encryption key
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

### Testing Encrypted Environment Variables

```php
use LaravelConfigrypt\Facades\Configrypt;

class EncryptionTest extends TestCase
{
    public function test_encrypted_environment_variables()
    {
        // Encrypt a test value
        $original = 'test-secret-value';
        $encrypted = Configrypt::encrypt($original);
        
        // Set as environment variable
        config(['app.test_secret' => $encrypted]);
        
        // Test auto-decryption (if enabled)
        $this->assertEquals($original, config('app.test_secret'));
    }
    
    public function test_manual_decryption()
    {
        $original = 'manual-test-value';
        $encrypted = Configrypt::encrypt($original);
        
        $decrypted = Configrypt::decrypt($encrypted);
        
        $this->assertEquals($original, $decrypted);
    }
}
```

## Error Handling and Debugging

### Custom Error Handling

```php
use LaravelConfigrypt\Facades\Configrypt;
use Illuminate\Support\Facades\Log;

class SafeDecryption
{
    public function safeDecrypt(string $value, ?string $default = null): ?string
    {
        try {
            if (Configrypt::isEncrypted($value)) {
                return Configrypt::decrypt($value);
            }
            
            return $value;
        } catch (Exception $e) {
            Log::warning('Decryption failed', [
                'value' => substr($value, 0, 10) . '...',
                'error' => $e->getMessage()
            ]);
            
            return $default;
        }
    }
    
    public function validateEncryptedValues(array $keys): array
    {
        $results = [];
        
        foreach ($keys as $key) {
            $value = env($key);
            
            if (Configrypt::isEncrypted($value)) {
                try {
                    Configrypt::decrypt($value);
                    $results[$key] = 'valid';
                } catch (Exception $e) {
                    $results[$key] = 'invalid: ' . $e->getMessage();
                }
            } else {
                $results[$key] = 'not_encrypted';
            }
        }
        
        return $results;
    }
}
```

## Next Steps

- [API Reference](api-reference.md) - Complete API documentation
- [Security Considerations](security.md) - Security best practices
- [Troubleshooting](troubleshooting.md) - Common issues and solutions