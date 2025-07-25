# Advanced Usage

This guide covers advanced usage patterns and features of Laravel Configrypt for complex scenarios and integrations.

## Service Container Integration

### Dependency Injection

You can inject the `ConfigryptService` into any class that's resolved through Laravel's service container:

```php
use LaravelConfigrypt\Services\ConfigryptService;

class EncryptionController extends Controller
{
    public function __construct(
        private ConfigryptService $configrypt
    ) {}

    public function encrypt(Request $request)
    {
        $encrypted = $this->configrypt->encrypt($request->value);
        
        return response()->json([
            'encrypted' => $encrypted,
            'prefix' => $this->configrypt->getPrefix()
        ]);
    }
}
```

### Service Resolution

Resolve the service manually when needed:

```php
$configrypt = app(ConfigryptService::class);
$encrypted = $configrypt->encrypt('secret-value');
```

## Facade Usage Patterns

### Basic Facade Methods

```php
use LaravelConfigrypt\Facades\Configrypt;

// Encrypt a value
$encrypted = Configrypt::encrypt('my-secret');

// Decrypt a value  
$decrypted = Configrypt::decrypt('ENC:encrypted-value');

// Check if a value is encrypted
$isEncrypted = Configrypt::isEncrypted('ENC:some-value');

// Get the current prefix
$prefix = Configrypt::getPrefix();

// Get the encryption key (use carefully)
$key = Configrypt::getKey();
```

### Conditional Encryption

```php
use LaravelConfigrypt\Facades\Configrypt;

class ConfigHelper
{
    public static function secureValue(string $value): string
    {
        // Only encrypt if not already encrypted
        if (Configrypt::isEncrypted($value)) {
            return $value;
        }
        
        return Configrypt::encrypt($value);
    }
    
    public static function getValue(string $key): string
    {
        $value = env($key);
        
        // Handle both encrypted and plain values
        if (Configrypt::isEncrypted($value)) {
            return Configrypt::decrypt($value);
        }
        
        return $value;
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
    
    public function __construct()
    {
        // User-specific encryption
        $this->userEncryption = new ConfigryptService(
            key: config('app.user_encryption_key'),
            prefix: 'USER_ENC:',
            cipher: 'AES-256-CBC'
        );
        
        // System-level encryption
        $this->systemEncryption = new ConfigryptService(
            key: config('app.system_encryption_key'),
            prefix: 'SYS_ENC:',
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