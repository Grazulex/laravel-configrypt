# Laravel Confi🔏 Laravel Configrypt lets you **encrypt secrets directly in your `.env` file** using a secure key, and decrypt them using helper functions that work reliably with Laravel's environment caching.rypt

<div align="center">
  <img src="new_logo.png" alt="Laravel Configrypt" width="100">
  <p><strong>Encrypt sensitive values in your Laravel .env file and decrypt them automatically at runtime — safe, seamless, and config-driven.</strong></p>

[![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-configrypt.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-configrypt)
[![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-configrypt.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-configrypt)
[![License](https://img.shields.io/github/license/grazulex/laravel-configrypt.svg?style=flat-square)](https://github.com/Grazulex/laravel-configrypt/blob/main/LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/grazulex/laravel-configrypt.svg?style=flat-square)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-12.x-ff2d20?style=flat-square&logo=laravel)](https://laravel.com/)
[![Tests](https://img.shields.io/github/actions/workflow/status/grazulex/laravel-configrypt/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Grazulex/laravel-configrypt/actions)
[![Code Style](https://img.shields.io/badge/code%20style-pint-000000?style=flat-square&logo=laravel)](https://github.com/laravel/pint)

</div>

## 🔐 Overview

🔏 Laravel Configrypt lets you **encrypt secrets directly in your `.env` file** using a secure key, and automatically decrypts them when accessed via Laravel’s `env()` or configuration helpers.

It protects values like API tokens, database credentials, or secret keys — especially when sharing `.env` files across environments or storing encrypted configs in source control or CI/CD.

## ✨ Features

- 🔐 Encrypt `.env` values using AES-256
- 🔓 Transparent decryption at runtime
- 🔧 Seamless Laravel integration via service provider
- 🔑 Custom encryption key support (fallback to `APP_KEY`)
- 📦 Works with both `env()` and `config()` helpers
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

    // Automatically decrypt during early bootstrap (default: false)
    // When true, encrypted env vars are decrypted during service provider registration
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

// ✅ Enable auto-decryption to bypass Laravel's env cache (advanced)
// Set CONFIGRYPT_AUTO_DECRYPT=true in .env to decrypt during early bootstrap
```

## ⚠️ Important: Laravel env() Cache Limitation

**Laravel caches environment variables very early in the boot process, before service providers load.** This means the standard `env()` function **cannot** be automatically decrypted.

### 🔧 Migration Solutions

**Option 1: Use Helper Functions (Recommended)**
```php
// ❌ This won't work - returns encrypted value
$password = env('DB_PASSWORD'); // Still returns "ENC:xyz..."

// ✅ These work - return decrypted values
$password = configrypt_env('DB_PASSWORD');
$password = encrypted_env('DB_PASSWORD');
$password = ConfigryptEnv::get('DB_PASSWORD');
```

**Option 2: Use Str Macro (Easy Migration)**
```php
use Illuminate\Support\Str;

// Easy to search and replace in your codebase
$password = Str::decryptEnv('DB_PASSWORD');
```

**Option 3: Enable Auto-Decryption (Advanced)**
```php
// Set in .env file:
CONFIGRYPT_AUTO_DECRYPT=true

// This enables early decryption that bypasses Laravel's env cache
// After enabling, env() will return decrypted values
$password = env('DB_PASSWORD'); // Now returns decrypted value
```

**Option 4: Manual Decryption**
```php
use LaravelConfigrypt\Facades\Configrypt;

$rawValue = env('DB_PASSWORD');
$password = Configrypt::isEncrypted($rawValue) ? Configrypt::decrypt($rawValue) : $rawValue;
```

### 🚀 Quick Migration

**Option A: Enable Auto-Decryption (Recommended)**
```bash
# Add to your .env file
echo "CONFIGRYPT_AUTO_DECRYPT=true" >> .env

# Now env() calls work normally - no code changes needed!
# Your existing env() calls will return decrypted values
```

**Option B: Find and Replace in Codebase**
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
ConfigryptEnv::decryptAll(); // Process all ENC: prefixed variables
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

### Auto-Decryption Feature

```php
// Enable in .env file:
CONFIGRYPT_AUTO_DECRYPT=true

// This enables early decryption during service provider registration
// After enabling, Laravel's env() function returns decrypted values:
$password = env('DB_PASSWORD'); // Returns decrypted value

// The auto-decryption feature:
// 1. Decrypts all ENC: prefixed env vars during early bootstrap
// 2. Updates $_ENV, $_SERVER, and putenv()
// 3. Clears Laravel's environment cache to ensure env() returns decrypted values
// 4. Handles errors gracefully (silent in production, logged in debug mode)
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
    // OR if auto-decryption is enabled:
    'password' => env('DB_PASSWORD'), // Works with auto-decrypt enabled
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
    // OR with auto-decrypt: 'secret' => env('STRIPE_SECRET'),
],

'mailgun' => [
    'secret' => configrypt_env('MAILGUN_SECRET'),
    // OR with auto-decrypt: 'secret' => env('MAILGUN_SECRET'),
],

// config/filesystems.php
's3' => [
    'driver' => 's3',
    'secret' => configrypt_env('AWS_SECRET_ACCESS_KEY'),
    // OR with auto-decrypt: 'secret' => env('AWS_SECRET_ACCESS_KEY'),
],
```

### Multi-Environment Setup

```bash
# Development
CONFIGRYPT_KEY=dev-key-32-characters-long-----
CONFIGRYPT_AUTO_DECRYPT=true
DB_PASSWORD=ENC:dev-encrypted-password

# Production  
CONFIGRYPT_KEY=prod-key-32-characters-long----
CONFIGRYPT_AUTO_DECRYPT=true
DB_PASSWORD=ENC:prod-encrypted-password
```

More examples available in the [`examples/`](examples/) directory.

## 🔑 Changing Keys

You can define a custom `CONFIGRYPT_KEY` in `.env` to use a dedicated encryption key different from `APP_KEY`.

> 💡 Remember: only encrypted values with the correct key can be decrypted. Keep your key safe!

## 🛡️ Security Considerations

- **Auto-Decryption Security**: When `CONFIGRYPT_AUTO_DECRYPT=true`, decryption happens during early bootstrap and values are stored in memory only
- **Environment Variable Safety**: Decrypted values never touch disk after load, only stored in runtime memory
- **Prefix Protection**: `ENC:` prefix ensures only intended values are decrypted
- **Error Handling**: Graceful fallbacks prevent application crashes from decryption failures
- **Key Management**: Only encrypted values with the correct key can be decrypted - keep your key safe!
- **Production Usage**: Ideal for `.env.staging`, `.env.production`, or vault-managed `.env` overrides
- **Team Sharing**: Perfect for sharing `.env` securely in teams or across pipelines

## 🚀 Quick Start

```bash
# 1. Install the package
composer require grazulex/laravel-configrypt

# 2. Publish configuration (optional)
php artisan vendor:publish --tag=configrypt-config

# 3. Enable auto-decryption (recommended)
echo "CONFIGRYPT_AUTO_DECRYPT=true" >> .env

# 4. Encrypt a secret
php artisan configrypt:encrypt "your-secret-value"

# 5. Add to .env file
echo "MY_SECRET=ENC:your-encrypted-value" >> .env

# 6. Use in your application (multiple options)
# Option A: Works automatically with auto-decrypt enabled
$secret = env('MY_SECRET');

# Option B: Use helper functions (always works)
$secret = configrypt_env('MY_SECRET');

# Option C: Use Str macro for easy migration
$secret = Str::decryptEnv('MY_SECRET');
```

## 📚 Documentation

Comprehensive documentation is available in the [`docs/`](docs/) directory:

- **[Installation](docs/installation.md)** - Getting started with Laravel Configrypt
- **[Configuration](docs/configuration.md)** - Customizing encryption settings
- **[Basic Usage](docs/basic-usage.md)** - Fundamental encryption/decryption operations
- **[Advanced Usage](docs/advanced-usage.md)** - Complex scenarios and integrations
- **[Artisan Commands](docs/artisan-commands.md)** - Command-line tools reference
- **[API Reference](docs/api-reference.md)** - Complete API documentation
- **[Security Considerations](docs/security.md)** - Security best practices
- **[Troubleshooting](docs/troubleshooting.md)** - Common issues and solutions
- **[Examples](examples/README.md)** - Practical usage examples

## 📄 License

MIT License — see [LICENSE.md](LICENSE.md)

---

<div align="center">
  Made with 🔐 for Laravel developers who care about secrets.
</div>
