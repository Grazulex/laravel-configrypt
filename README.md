# Laravel Configrypt

<img src="new_logo.png" alt="Laravel Configrypt" width="200">

Encrypt sensitive values in your Laravel .env file and decrypt them using helper functions that work around Laravel's environment caching limitations.

[![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-configrypt.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-configrypt)
[![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-configrypt.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-configrypt)
[![License](https://img.shields.io/github/license/grazulex/laravel-configrypt.svg?style=flat-square)](https://github.com/Grazulex/laravel-configrypt/blob/main/LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/grazulex/laravel-configrypt.svg?style=flat-square)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-12.x-ff2d20?style=flat-square&logo=laravel)](https://laravel.com/)
[![Tests](https://img.shields.io/github/actions/workflow/status/grazulex/laravel-configrypt/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Grazulex/laravel-configrypt/actions)
[![Code Style](https://img.shields.io/badge/code%20style-pint-000000?style=flat-square&logo=laravel)](https://github.com/laravel/pint)

## 🔐 Overview

🔏 Laravel Configrypt lets you **encrypt secrets directly in your `.env` file** using a secure key, and decrypt them using reliable helper functions that work around Laravel's environment caching limitations.

It protects values like API tokens, database credentials, or secret keys — especially when sharing `.env` files across environments or storing encrypted configs in source control or CI/CD.

## ✨ Features

- 🔐 Encrypt `.env` values using AES-256
- 🔓 Reliable decryption with helper functions
- 🔧 Seamless Laravel integration via service provider
- 🔑 Custom encryption key support (fallback to `APP_KEY`)
- 🛡️ Secure by default: decryption only happens inside app runtime
- ⚙️ Configurable via `config/configrypt.php`
- 🧪 Safe for CI/CD, secrets rotation, and external vault injection

## 💡 Example

In your `.env`:

```env
MAIL_PASSWORD=ENC:gk9AvRZgx6Jyds7K2uFctw==
```

In your Laravel code:

```php
// Method 1: Use helper functions (recommended)
$password = configrypt_env('MAIL_PASSWORD');     // returns decrypted value
$password = encrypted_env('MAIL_PASSWORD');      // alias for configrypt_env()

// Method 2: Use the Str macro for easy migration
use Illuminate\Support\Str;
$password = Str::decryptEnv('MAIL_PASSWORD');    // easy search & replace from env()

// Method 3: Use the environment facade
use LaravelConfigrypt\Facades\ConfigryptEnv;
$password = ConfigryptEnv::get('MAIL_PASSWORD'); // returns decrypted value

// Method 4: Manual decryption
use LaravelConfigrypt\Facades\Configrypt;
$rawValue = env('MAIL_PASSWORD'); // still encrypted due to Laravel's env cache
$password = Configrypt::decrypt($rawValue);      // manual decrypt

// Note: env('MAIL_PASSWORD') returns encrypted value due to Laravel's cache limitation
```

## ⚙️ Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=configrypt-config
```

Result in `config/configrypt.php`:

```php
return [
    // Use a dedicated key or fallback to APP_KEY
    'key' => env('CONFIGRYPT_KEY', env('APP_KEY')),

    // Prefix used to identify encrypted values
    'prefix' => env('CONFIGRYPT_PREFIX', 'ENC:'),

    // Cipher method
    'cipher' => env('CONFIGRYPT_CIPHER', 'AES-256-CBC'),

    // Auto decrypt (deprecated - has no effect)
    'auto_decrypt' => env('CONFIGRYPT_AUTO_DECRYPT', false),
];
```

## 🚀 Quick Start

### 1. Install the package

```bash
composer require grazulex/laravel-configrypt
```

### 2. Publish configuration (optional)

```bash
php artisan vendor:publish --tag=configrypt-config
```

### 3. Encrypt your secrets

```bash
php artisan configrypt:encrypt "my-super-secret-password"
```

Output:
```
Encrypted value:
ENC:gk9AvRZgx6Jyds7K2uFctw==

You can now use this encrypted value in your .env file:
SOME_SECRET=ENC:gk9AvRZgx6Jyds7K2uFctw==
```

### 4. Add to your .env file

```env
DB_PASSWORD=ENC:gk9AvRZgx6Jyds7K2uFctw==
API_SECRET=ENC:XyZ123AbC456DeF789GhI012JkL==
JWT_SECRET=ENC:MnOpQrStUvWxYzAbCdEfGhIjKl==
```

### 5. Use in your application

**⚠️ Important: Laravel's `env()` function cannot be automatically decrypted due to early caching.**

```php
// ❌ This won't work - Laravel caches env() before our package loads
$dbPassword = env('DB_PASSWORD'); // Returns "ENC:xyz..." (still encrypted)

// ✅ Use our helper functions instead (recommended)
$dbPassword = configrypt_env('DB_PASSWORD');  // Returns decrypted value
$apiSecret = encrypted_env('API_SECRET');     // Alias for consistency

// ✅ Or use the facade for more control
use LaravelConfigrypt\Facades\ConfigryptEnv;
$dbPassword = ConfigryptEnv::get('DB_PASSWORD');

// ✅ Or use the Str macro for easy migration
use Illuminate\Support\Str;
$dbPassword = Str::decryptEnv('DB_PASSWORD');
```

## ⚠️ Important: Laravel env() Cache Limitation

**Laravel caches environment variables very early in the boot process, before service providers load.** This means the standard `env()` function **cannot** be automatically decrypted.

### 🔧 Solution: Use Helper Functions

```php
// ❌ This won't work - returns encrypted value
$password = env('DB_PASSWORD'); // Still returns "ENC:xyz..."

// ✅ These work - return decrypted values
$password = configrypt_env('DB_PASSWORD');
$password = encrypted_env('DB_PASSWORD');
$password = ConfigryptEnv::get('DB_PASSWORD');
```

### 🚀 Quick Migration

Find and replace in your codebase:

```bash
# Replace env() calls with configrypt_env()
find . -name "*.php" -exec sed -i 's/env(/configrypt_env(/g' {} \;

# Or use Str::decryptEnv() for easier reversal
find . -name "*.php" -exec sed -i 's/env(/Str::decryptEnv(/g' {} \;
```

## 🔧 Advanced Usage

### Using the Facades

```php
use LaravelConfigrypt\Facades\Configrypt;
use LaravelConfigrypt\Facades\ConfigryptEnv;

// Encrypt a value
$encrypted = Configrypt::encrypt('my-secret-value');

// Decrypt a value
$decrypted = Configrypt::decrypt('ENC:encrypted-value');

// Check if a value is encrypted
$isEncrypted = Configrypt::isEncrypted('ENC:some-value');

// Environment-specific methods
$dbPassword = ConfigryptEnv::get('DB_PASSWORD');
$allDecrypted = ConfigryptEnv::getAllDecrypted();
```

### Helper Functions

```php
// Primary helper functions (recommended approach)
$dbPassword = configrypt_env('DB_PASSWORD', 'default-value');
$apiKey = encrypted_env('API_KEY'); // alias for configrypt_env()

// Str macro for easy migration from env() calls
use Illuminate\Support\Str;
$secret = Str::decryptEnv('JWT_SECRET');
```

### Dependency Injection

```php
use LaravelConfigrypt\Services\ConfigryptService;
use LaravelConfigrypt\Services\EnvironmentDecryptor;

class MyController extends Controller
{
    public function __construct(
        private ConfigryptService $configrypt,
        private EnvironmentDecryptor $envDecryptor
    ) {
    }

    public function encryptValue(Request $request)
    {
        $encrypted = $this->configrypt->encrypt($request->value);
        return response()->json(['encrypted' => $encrypted]);
    }

    public function getDecryptedEnv(string $key)
    {
        return $this->envDecryptor->get($key);
    }
}
```

## 🧪 Practical Examples

### Database Configuration

```env
# Encrypt your database password
DB_PASSWORD=ENC:W3+f/2ZzZfl9KQ==
```

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'password' => configrypt_env('DB_PASSWORD'), // Use helper function
],
```

### API Keys Management

```env
# Third-party service credentials
STRIPE_SECRET=ENC:Nq8j8hlc3PMp9uE=
MAILGUN_SECRET=ENC:XYZ123456789abc=
AWS_SECRET_ACCESS_KEY=ENC:AbCdEf1234567890=
```

```php
// config/services.php
'stripe' => [
    'secret' => configrypt_env('STRIPE_SECRET'),
],

'mailgun' => [
    'secret' => configrypt_env('MAILGUN_SECRET'),
],

// config/filesystems.php
's3' => [
    'driver' => 's3',
    'secret' => configrypt_env('AWS_SECRET_ACCESS_KEY'),
],
```

### Multi-Environment Setup

```bash
# Development
CONFIGRYPT_KEY=dev-key-32-characters-long-----
DB_PASSWORD=ENC:dev-encrypted-password

# Production  
CONFIGRYPT_KEY=prod-key-32-characters-long----
DB_PASSWORD=ENC:prod-encrypted-password
```

More examples are available in the **[Examples Wiki](https://github.com/Grazulex/laravel-configrypt/wiki/Examples)**.

## 🔑 Changing Keys

You can define a custom `CONFIGRYPT_KEY` in `.env` to use a dedicated encryption key different from `APP_KEY`.

> 💡 Remember: only encrypted values with the correct key can be decrypted. Keep your key safe!

## 🛡️ Security Considerations

- **Environment Variable Safety**: Decrypted values never touch disk after load, only stored in runtime memory
- **Prefix Protection**: `ENC:` prefix ensures only intended values are decrypted
- **Error Handling**: Graceful fallbacks prevent application crashes from decryption failures
- **Key Management**: Only encrypted values with the correct key can be decrypted - keep your key safe!
- **Production Usage**: Ideal for `.env.staging`, `.env.production`, or vault-managed `.env` overrides
- **Team Sharing**: Perfect for sharing `.env` securely in teams or across pipelines

## 📚 Documentation

Comprehensive documentation and examples are available in the **[GitHub Wiki](https://github.com/Grazulex/laravel-configrypt/wiki)**:

- **[Installation Guide](https://github.com/Grazulex/laravel-configrypt/wiki/Installation)** - Getting started with Laravel Configrypt
- **[Configuration](https://github.com/Grazulex/laravel-configrypt/wiki/Configuration)** - Customizing encryption settings
- **[Basic Usage](https://github.com/Grazulex/laravel-configrypt/wiki/Basic-Usage)** - Fundamental encryption/decryption operations
- **[Advanced Usage](https://github.com/Grazulex/laravel-configrypt/wiki/Advanced-Usage)** - Complex scenarios and integrations
- **[Artisan Commands](https://github.com/Grazulex/laravel-configrypt/wiki/Artisan-Commands)** - Command-line tools reference
- **[API Reference](https://github.com/Grazulex/laravel-configrypt/wiki/API-Reference)** - Complete API documentation
- **[Security Considerations](https://github.com/Grazulex/laravel-configrypt/wiki/Security)** - Security best practices
- **[Troubleshooting](https://github.com/Grazulex/laravel-configrypt/wiki/Troubleshooting)** - Common issues and solutions
- **[Examples](https://github.com/Grazulex/laravel-configrypt/wiki/Examples)** - Practical usage examples

## 📄 License

MIT License — see [LICENSE.md](LICENSE.md)

---

<div align="center">
  Made with 🔐 for Laravel developers who care about secrets.
</div>
