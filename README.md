# Laravel Configrypt

<div align="center">
  <img src="new_logo.png" alt="Laravel Configrypt" width="100">
  <p><strong>Encrypt sensitive values in your Laravel .env file and decrypt them automatically at runtime — safe, seamless, and config-driven.</strong></p>

  [![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-configrypt)](https://packagist.org/packages/grazulex/laravel-configrypt)
  [![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-configrypt)](https://packagist.org/packages/grazulex/laravel-configrypt)
  [![License](https://img.shields.io/github/license/grazulex/laravel-configrypt)](LICENSE.md)
  [![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://php.net)
  [![Laravel Version](https://img.shields.io/badge/laravel-%5E12.19-red)](https://laravel.com)
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

```
MAIL_PASSWORD=ENC:gk9AvRZgx6Jyds7K2uFctw==
```

In your Laravel code:

```php
config('mail.password'); // returns decrypted value
env('MAIL_PASSWORD');    // returns decrypted value
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
    'prefix' => 'ENC:',

    // Cipher method
    'cipher' => 'AES-256-CBC',

    // Automatically decrypt during config/bootstrap
    'auto_decrypt' => true,
];
```

## 🔧 Usage

### Encrypt a value

```bash
php artisan configrypt:encrypt "my-super-secret"
```

Output:
```
ENC:gk9AvRZgx6Jyds7K2uFctw==
```

You can then paste that into your `.env` file.

### Decrypt manually (optional)

```bash
php artisan configrypt:decrypt "ENC:gk9AvRZgx6Jyds7K2uFctw=="
```

Output:
```
my-super-secret
```

## 🔄 Auto-Decryption Behavior

When `auto_decrypt = true`, Laravel Configrypt will hook into the environment loading process, and decrypt all `ENC:` values transparently — no changes needed in your app code.

Supports:

- `env('KEY')`
- `config('service.key')` (if backed by env)

## 🧪 Example Use Case

```
DB_PASSWORD=ENC:W3+f/2ZzZfl9KQ==

MAILGUN_SECRET=ENC:Nq8j8hlc3PMp9uE=

APP_SECRET=ENC:XYZ==

MY_API_TOKEN=ENC:123456789abc
```

These values will be decrypted at runtime and accessible like any other environment variable or config.

## 🔑 Changing Keys

You can define a custom `CONFIGRYPT_KEY` in `.env` to use a dedicated encryption key different from `APP_KEY`.

> 💡 Remember: only encrypted values with the correct key can be decrypted. Keep your key safe!

## 🛡️ Security Considerations

- Decryption only happens in memory — encrypted values never touch disk after load
- `ENC:` prefix ensures only intended values are decrypted
- Best used with `.env.staging`, `.env.production`, or vault-managed `.env` overrides
- Ideal for sharing `.env` securely in teams or across pipelines

## 🚀 Quick Start

```bash
composer require grazulex/laravel-configrypt

php artisan vendor:publish --tag=configrypt-config
```

## 📚 Documentation

Coming soon: see `docs/` folder

## 📄 License

MIT License — see [LICENSE.md](LICENSE.md)

---

<div align="center">
  Made with 🔐 for Laravel developers who care about secrets.
</div>
