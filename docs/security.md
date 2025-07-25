# Security Considerations

This guide covers important security considerations when using Laravel Configrypt to encrypt sensitive configuration values.

## Overview

Laravel Configrypt provides encryption for sensitive values in your `.env` files, but proper security requires careful attention to key management, encryption practices, and deployment strategies.

## Encryption Security

### Cipher Selection

Laravel Configrypt uses **AES-256-CBC** by default, which is a secure, industry-standard encryption algorithm.

```php
// Recommended (default)
'cipher' => 'AES-256-CBC',

// Alternative (faster, less secure)
'cipher' => 'AES-128-CBC',
```

**Recommendations:**
- ✅ Use AES-256-CBC for maximum security
- ⚠️ Only use AES-128-CBC if performance is critical and security requirements are lower
- ❌ Never implement custom cipher methods

### Key Requirements

#### Key Length

- **AES-256-CBC**: Requires 32-byte (256-bit) keys
- **AES-128-CBC**: Requires 16-byte (128-bit) keys

Laravel Configrypt automatically hashes keys to the correct length:

```php
// If your key is not exactly 32 characters, it will be hashed
$key = hash('sha256', $providedKey, true);
```

#### Key Strength

Use cryptographically secure random keys:

```bash
# Generate a secure 32-character key
openssl rand -base64 32

# Or use Laravel's key generation
php artisan key:generate --show
```

**❌ Don't use:**
- Predictable keys (e.g., "password123")
- Dictionary words
- Sequential patterns
- Short keys

**✅ Do use:**
- Cryptographically random keys
- Full-length keys (32 characters for AES-256)
- Unique keys for each environment

## Key Management

### Dedicated Encryption Keys

Use separate encryption keys instead of reusing `APP_KEY`:

```env
# ✅ Recommended: Dedicated key
CONFIGRYPT_KEY=your-dedicated-32-character-key--

# ⚠️ Fallback: APP_KEY (not recommended for production)
APP_KEY=base64:your-laravel-app-key
```

**Benefits of dedicated keys:**
- Separation of concerns
- Independent key rotation
- Reduced impact if one key is compromised
- Better audit trails

### Environment Separation

Use different keys for different environments:

```env
# .env.production
CONFIGRYPT_KEY=prod-key-32-characters-long----

# .env.staging  
CONFIGRYPT_KEY=staging-key-32-characters-long-

# .env.local
CONFIGRYPT_KEY=dev-key-32-characters-long-----
```

### Key Storage

#### ❌ Don't Store Keys In:
- Source control (even private repositories)
- Configuration files committed to git
- Log files
- Application databases
- Client-side code
- Environment variables visible to other processes

#### ✅ Do Store Keys In:
- Environment variables set by infrastructure
- Secure key management systems (AWS KMS, HashiCorp Vault, Azure Key Vault)
- CI/CD secret management
- Secure configuration management tools

### Key Rotation

Implement regular key rotation:

```php
class KeyRotationService
{
    public function rotateKeys(string $oldKey, string $newKey): void
    {
        // 1. Create new encryptor with new key
        $newEncryptor = new ConfigryptService($newKey);
        
        // 2. Create old encryptor for decryption
        $oldEncryptor = new ConfigryptService($oldKey);
        
        // 3. Re-encrypt all values
        $this->reEncryptEnvironmentVariables($oldEncryptor, $newEncryptor);
        
        // 4. Update key in secure storage
        $this->updateKeyInVault($newKey);
    }
    
    private function reEncryptEnvironmentVariables(
        ConfigryptService $oldEncryptor,
        ConfigryptService $newEncryptor
    ): void {
        foreach ($_ENV as $key => $value) {
            if ($oldEncryptor->isEncrypted($value)) {
                $decrypted = $oldEncryptor->decrypt($value);
                $reEncrypted = $newEncryptor->encrypt($decrypted);
                
                // Update environment file
                $this->updateEnvironmentFile($key, $reEncrypted);
            }
        }
    }
}
```

**Key Rotation Best Practices:**
- Rotate keys regularly (quarterly or semi-annually)
- Have a tested rollback plan
- Coordinate rotation across all environments
- Monitor for decryption failures after rotation
- Keep old keys temporarily for emergency rollback

## Data Protection

### What to Encrypt

**✅ Always Encrypt:**
- Database passwords
- API keys and tokens
- OAuth client secrets
- JWT signing keys
- Third-party service credentials
- Webhook secrets
- Certificate private keys
- Encryption keys for other systems

**⚠️ Consider Encrypting:**
- Database connection strings
- Cache connection strings
- Email service credentials
- Cloud service access keys

**❌ Don't Encrypt:**
- Non-sensitive configuration (app name, timezone)
- Public API endpoints
- Debug flags
- Log levels
- Cache prefixes

### Sensitive Data Handling

```php
class SecureConfigHelper
{
    private static array $sensitivePatterns = [
        '/.*password.*/i',
        '/.*secret.*/i',
        '/.*key.*/i',
        '/.*token.*/i',
        '/.*credential.*/i',
        '/.*private.*/i',
    ];
    
    public static function isSensitive(string $key): bool
    {
        foreach (self::$sensitivePatterns as $pattern) {
            if (preg_match($pattern, $key)) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function encryptSensitiveValues(array $config): array
    {
        foreach ($config as $key => $value) {
            if (self::isSensitive($key) && !Configrypt::isEncrypted($value)) {
                $config[$key] = Configrypt::encrypt($value);
            }
        }
        
        return $config;
    }
}
```

## Runtime Security

### Memory Protection

Laravel Configrypt minimizes exposure of sensitive data:

- Decryption happens only in memory during application bootstrap
- Decrypted values are not written to disk or persistent storage
- Auto-decryption replaces encrypted values in `$_ENV`, `$_SERVER`, and via `putenv()`
- Environment cache clearing uses reflection but doesn't persist changes

### Auto-Decryption Security

When `CONFIGRYPT_AUTO_DECRYPT=true` is enabled, additional security considerations apply:

#### ✅ Auto-Decryption Security Features

```php
// Auto-decryption security characteristics:
// 1. Runs during early service provider registration (limited attack surface)
// 2. Only processes values with correct encryption prefix
// 3. Failed decryptions are handled gracefully without breaking application
// 4. Decrypted values stored only in runtime memory
// 5. No persistent changes to environment files
```

**Security Benefits:**
- **Limited exposure window**: Decryption happens once during bootstrap, not on every request
- **Prefix protection**: Only `ENC:` prefixed values are processed, preventing accidental decryption
- **Graceful failures**: Invalid encrypted values don't break the application
- **Memory-only storage**: Decrypted values exist only in process memory

#### ⚠️ Auto-Decryption Security Considerations

**Environment Variable Visibility:**
```php
// After auto-decryption, decrypted values are visible in:
// - $_ENV array
// - $_SERVER array (if applicable)
// - getenv() calls
// - env() function calls

// Consider this when:
// - Using process monitoring tools
// - Running in shared hosting environments
// - Using debugging tools that inspect environment variables
```

**Process Memory Security:**
```php
// Decrypted values remain in memory until process termination
// This is normal for any application using sensitive configuration
// But consider:
// - Memory dumps (in development/debugging)
// - Process inspection tools
// - Long-running processes (workers, queues)
```

#### 🔒 Auto-Decryption Best Practices

**1. Environment Isolation:**
```bash
# Only enable auto-decryption in secure environments
# Development
CONFIGRYPT_AUTO_DECRYPT=true

# Production (ensure secure deployment environment)
CONFIGRYPT_AUTO_DECRYPT=true

# Shared/untrusted environments
CONFIGRYPT_AUTO_DECRYPT=false  # Use helper functions instead
```

**2. Monitoring and Auditing:**
```php
// Log auto-decryption events for security auditing
class SecureConfigryptProvider extends LaravelConfigryptServiceProvider
{
    protected function earlyAutoDecryptEnvironmentVariables(): void
    {
        $decryptedCount = 0;
        
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($value, $this->prefix)) {
                // Log for security audit
                Log::info('Auto-decrypting environment variable', [
                    'key' => $key,
                    'prefix' => $this->prefix,
                    'timestamp' => now(),
                ]);
                
                $decryptedCount++;
            }
        }
        
        if ($decryptedCount > 0) {
            Log::info('Auto-decryption completed', [
                'variables_decrypted' => $decryptedCount,
                'timestamp' => now(),
            ]);
        }
        
        parent::earlyAutoDecryptEnvironmentVariables();
    }
}
```

**3. Alternative for High-Security Environments:**
```php
// For maximum security, disable auto-decryption and use explicit helpers
class HighSecurityConfig
{
    private ConfigryptService $configrypt;
    
    public function __construct()
    {
        $this->configrypt = app(ConfigryptService::class);
    }
    
    public function getSecret(string $key): ?string
    {
        $value = $_ENV[$key] ?? null;
        
        if ($value && $this->configrypt->isEncrypted($value)) {
            // Decrypt on-demand, don't store in environment
            return $this->configrypt->decrypt($value);
        }
        
        return $value;
    }
}
```

### Error Handling

Secure error handling prevents information leakage:

```php
// ✅ Good: Generic error message
try {
    $decrypted = Configrypt::decrypt($value);
} catch (Exception $e) {
    Log::error('Decryption failed', ['key' => $envKey]);
    throw new RuntimeException('Configuration error');
}

// ❌ Bad: Exposes sensitive information
try {
    $decrypted = Configrypt::decrypt($value);
} catch (Exception $e) {
    throw new RuntimeException("Failed to decrypt: {$value}");
}
```

### Debug Mode Considerations

In debug mode, be careful about what gets logged:

```php
// In LaravelConfigryptServiceProvider
if (config('app.debug') && ! defined('PHPSTAN_ANALYSIS')) {
    report($e);
}
```

**Production checklist:**
- ✅ Ensure `APP_DEBUG=false`
- ✅ Configure proper error logging
- ✅ Don't expose stack traces
- ✅ Sanitize logs of sensitive data

## Deployment Security

### CI/CD Pipeline

Secure practices for CI/CD:

```yaml
# GitHub Actions example
name: Deploy
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      # ✅ Use secrets for encryption keys
      - name: Setup environment
        run: |
          echo "CONFIGRYPT_KEY=${{ secrets.CONFIGRYPT_KEY }}" >> .env
          
      # ✅ Encrypt values at deployment time
      - name: Encrypt secrets
        run: |
          DB_PASSWORD_ENC=$(php artisan configrypt:encrypt "${{ secrets.DB_PASSWORD }}")
          echo "DB_PASSWORD=$DB_PASSWORD_ENC" >> .env.production
```

**CI/CD Security Checklist:**
- ✅ Use secret management systems
- ✅ Encrypt at deployment time, not in source
- ✅ Audit access to secrets
- ✅ Use least-privilege access
- ✅ Monitor secret usage

### Infrastructure Security

#### Container Security

```dockerfile
# Dockerfile
FROM php:8.3-fpm

# ✅ Don't include encryption keys in image
# ✅ Use multi-stage builds to minimize attack surface
# ✅ Run as non-root user

# Set encryption key via environment variable at runtime
ENV CONFIGRYPT_KEY=""

COPY . /app
WORKDIR /app

# ✅ Remove sensitive files
RUN rm -f .env .env.* && \
    chown -R www-data:www-data /app
    
USER www-data
```

#### Kubernetes Security

```yaml
# k8s-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: laravel-app
spec:
  template:
    spec:
      containers:
      - name: app
        image: your-app:latest
        env:
        # ✅ Use secrets for encryption keys
        - name: CONFIGRYPT_KEY
          valueFrom:
            secretKeyRef:
              name: laravel-secrets
              key: configrypt-key
```

## Monitoring and Auditing

### Decryption Monitoring

Monitor decryption operations:

```php
class AuditingConfigryptService extends ConfigryptService
{
    public function decrypt(string $encryptedValue): string
    {
        $startTime = microtime(true);
        
        try {
            $result = parent::decrypt($encryptedValue);
            
            $this->logDecryption('success', $startTime);
            
            return $result;
        } catch (Exception $e) {
            $this->logDecryption('failure', $startTime, $e->getMessage());
            throw $e;
        }
    }
    
    private function logDecryption(string $status, float $startTime, ?string $error = null): void
    {
        $duration = microtime(true) - $startTime;
        
        Log::info('Configrypt decryption', [
            'status' => $status,
            'duration_ms' => $duration * 1000,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

### Security Metrics

Track security-related metrics:

- Number of decryption operations
- Decryption failure rates
- Key rotation frequency
- Access patterns to encrypted values

## Common Security Pitfalls

### ❌ Pitfall 1: Logging Encrypted Values

```php
// Don't do this
Log::info('Processing config', ['value' => $encryptedValue]);
```

### ❌ Pitfall 2: Exposing Keys in Error Messages

```php
// Don't do this
throw new Exception("Decryption failed with key: {$this->key}");
```

### ❌ Pitfall 3: Using Weak Keys

```php
// Don't do this
$service = new ConfigryptService('password123');
```

### ❌ Pitfall 4: Storing Keys in Git

```bash
# Don't commit this
git add .env
```

### ❌ Pitfall 5: Reusing Keys Across Environments

```env
# Don't use the same key everywhere
CONFIGRYPT_KEY=same-key-for-all-environments
```

## Security Checklist

### Development
- [ ] Use dedicated encryption keys
- [ ] Generate cryptographically secure keys
- [ ] Never commit keys to source control
- [ ] Test key rotation procedures
- [ ] Implement proper error handling

### Production
- [ ] Use strong, unique encryption keys
- [ ] Store keys in secure key management systems
- [ ] Enable proper logging and monitoring
- [ ] Implement key rotation schedule
- [ ] Audit access to encrypted values
- [ ] Ensure `APP_DEBUG=false`
- [ ] Use HTTPS for all communications

### Operations
- [ ] Regular security audits
- [ ] Monitor for decryption failures
- [ ] Have incident response procedures
- [ ] Backup and recovery procedures for keys
- [ ] Document key management procedures

## Compliance Considerations

### GDPR
- Encrypted personal data is still subject to GDPR
- Implement proper data retention policies
- Ensure ability to delete encrypted data
- Document encryption practices

### PCI DSS
- Use appropriate encryption for payment data
- Implement proper key management
- Regular security assessments
- Audit encryption practices

### SOC 2
- Document encryption procedures
- Implement access controls
- Monitor encryption operations
- Regular vulnerability assessments

## Next Steps

- [Troubleshooting](troubleshooting.md) - Security-related troubleshooting
- [Examples](../examples/README.md) - Security-focused examples
- [API Reference](api-reference.md) - Security methods and configuration