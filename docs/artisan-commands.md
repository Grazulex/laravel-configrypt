# Artisan Commands

Laravel Configrypt provides two Artisan commands for encrypting and decrypting values from the command line.

## Available Commands

- [`configrypt:encrypt`](#configryptencrypt) - Encrypt a value for use in .env files
- [`configrypt:decrypt`](#configryptdecrypt) - Decrypt an encrypted value

## configrypt:encrypt

Encrypt a value that can be used in your `.env` file.

### Syntax

```bash
php artisan configrypt:encrypt {value}
```

### Parameters

- `value` (required) - The plain text value to encrypt

### Examples

#### Basic Encryption

```bash
php artisan configrypt:encrypt "my-secret-password"
```

Output:
```
Encrypted value:
ENC:gk9AvRZgx6Jyds7K2uFctw==

You can now use this encrypted value in your .env file:
SOME_SECRET=ENC:gk9AvRZgx6Jyds7K2uFctw==
```

#### Encrypting Different Types of Values

```bash
# Database password
php artisan configrypt:encrypt "super-secret-db-password"

# API key
php artisan configrypt:encrypt "sk_live_1234567890abcdef"

# JWT secret
php artisan configrypt:encrypt "your-jwt-secret-key-here"

# OAuth client secret
php artisan configrypt:encrypt "oauth2-client-secret"
```

#### Encrypting Values with Special Characters

```bash
# Values with spaces (use quotes)
php artisan configrypt:encrypt "password with spaces"

# Values with special characters
php artisan configrypt:encrypt "p@ssw0rd!#$%"

# JSON strings
php artisan configrypt:encrypt '{"api_key":"12345","secret":"abcdef"}'
```

#### Long Values

```bash
# Long text or keys
php artisan configrypt:encrypt "-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC..."
```

### Output Format

The command outputs:
1. The encrypted value with the configured prefix (default: `ENC:`)
2. A helpful example showing how to use it in your `.env` file

### Error Cases

#### Empty Value

```bash
php artisan configrypt:encrypt ""
```

Output:
```
Value cannot be empty.
```

#### Missing Encryption Key

If no encryption key is configured:

```bash
php artisan configrypt:encrypt "test"
```

Output:
```
Encryption failed: Encryption key cannot be empty. Please set CONFIGRYPT_KEY or APP_KEY.
```

## configrypt:decrypt

Decrypt an encrypted value to see the original plain text.

### Syntax

```bash
php artisan configrypt:decrypt {value}
```

### Parameters

- `value` (required) - The encrypted value to decrypt (with or without prefix)

### Examples

#### Basic Decryption

```bash
php artisan configrypt:decrypt "ENC:gk9AvRZgx6Jyds7K2uFctw=="
```

Output:
```
Decrypted value:
my-secret-password
```

#### Decryption Without Prefix

The prefix is optional when decrypting:

```bash
# These are equivalent
php artisan configrypt:decrypt "ENC:gk9AvRZgx6Jyds7K2uFctw=="
php artisan configrypt:decrypt "gk9AvRZgx6Jyds7K2uFctw=="
```

#### Verifying .env Values

```bash
# Decrypt values from your .env file to verify they're correct
php artisan configrypt:decrypt "ENC:AbCdEfGhIjKlMnOpQrStUvWxYz=="
```

### Error Cases

#### Empty Value

```bash
php artisan configrypt:decrypt ""
```

Output:
```
Encrypted value cannot be empty.
```

#### Invalid Encrypted Value

```bash
php artisan configrypt:decrypt "invalid-encrypted-value"
```

Output:
```
Decryption failed: The payload is invalid.
Make sure the value is properly encrypted and you have the correct encryption key.
```

#### Wrong Encryption Key

If the encryption key is different from when the value was encrypted:

```bash
php artisan configrypt:decrypt "ENC:gk9AvRZgx6Jyds7K2uFctw=="
```

Output:
```
Decryption failed: The MAC is invalid.
Make sure the value is properly encrypted and you have the correct encryption key.
```

## Practical Workflows

### Setting Up New Environment Variables

1. **Encrypt the secret value:**
   ```bash
   php artisan configrypt:encrypt "actual-secret-value"
   ```

2. **Copy the output to your .env file:**
   ```env
   MY_SECRET=ENC:generated-encrypted-value
   ```

3. **Verify it works:**
   ```bash
   php artisan configrypt:decrypt "ENC:generated-encrypted-value"
   ```

### Rotating Secrets

1. **Decrypt the current value:**
   ```bash
   php artisan configrypt:decrypt "ENC:current-encrypted-value"
   ```

2. **Encrypt the new value:**
   ```bash
   php artisan configrypt:encrypt "new-secret-value"
   ```

3. **Update your .env file with the new encrypted value**

### Troubleshooting Environment Variables

1. **Check if a value is properly encrypted:**
   ```bash
   php artisan configrypt:decrypt "ENC:suspected-encrypted-value"
   ```

2. **Verify all encrypted values in .env work:**
   ```bash
   # Create a script to test all ENC: values
   grep "ENC:" .env | while read line; do
     key=$(echo $line | cut -d'=' -f1)
     value=$(echo $line | cut -d'=' -f2-)
     echo "Testing $key..."
     php artisan configrypt:decrypt "$value"
   done
   ```

## Script Integration

### Bash Scripts

```bash
#!/bin/bash

# Function to encrypt a value
encrypt_value() {
    local value="$1"
    php artisan configrypt:encrypt "$value" | grep "ENC:" | head -1
}

# Function to decrypt a value
decrypt_value() {
    local encrypted="$1"
    php artisan configrypt:decrypt "$encrypted" | tail -1
}

# Usage examples
DB_PASSWORD_ENCRYPTED=$(encrypt_value "my-db-password")
echo "Encrypted password: $DB_PASSWORD_ENCRYPTED"

ORIGINAL_VALUE=$(decrypt_value "$DB_PASSWORD_ENCRYPTED")
echo "Original value: $ORIGINAL_VALUE"
```

### CI/CD Integration

```yaml
# GitHub Actions example
- name: Encrypt secrets for production
  run: |
    DB_PASSWORD_ENC=$(php artisan configrypt:encrypt "${{ secrets.DB_PASSWORD }}")
    echo "DB_PASSWORD=$DB_PASSWORD_ENC" >> .env.production
    
    API_KEY_ENC=$(php artisan configrypt:encrypt "${{ secrets.API_KEY }}")
    echo "API_KEY=$API_KEY_ENC" >> .env.production
```

### PHP Scripts

```php
<?php
// encrypt-env.php - Utility script to encrypt environment variables

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

function encryptValue(string $value): string
{
    $output = shell_exec("php artisan configrypt:encrypt " . escapeshellarg($value));
    preg_match('/ENC:[^\s]+/', $output, $matches);
    return $matches[0] ?? '';
}

function decryptValue(string $encrypted): string
{
    $output = shell_exec("php artisan configrypt:decrypt " . escapeshellarg($encrypted));
    return trim($output);
}

// Usage
$secrets = [
    'DB_PASSWORD' => 'secret-password',
    'API_KEY' => 'api-key-value',
];

foreach ($secrets as $key => $value) {
    $encrypted = encryptValue($value);
    echo "{$key}={$encrypted}\n";
}
```

## Command Options and Environment Variables

### Affected by Configuration

The commands respect your configuration settings:

```env
# Custom prefix
CONFIGRYPT_PREFIX=CUSTOM_ENC:

# Custom cipher
CONFIGRYPT_CIPHER=AES-128-CBC

# Custom encryption key
CONFIGRYPT_KEY=your-custom-32-character-key---
```

When these are set, the commands will use these settings:

```bash
php artisan configrypt:encrypt "test"
# Output: CUSTOM_ENC:encrypted-value-here
```

### Environment-Specific Usage

```bash
# Encrypt for different environments
APP_ENV=production php artisan configrypt:encrypt "prod-secret"
APP_ENV=staging php artisan configrypt:encrypt "staging-secret"
APP_ENV=development php artisan configrypt:encrypt "dev-secret"
```

## Best Practices

1. **Always Quote Values**: Use quotes around values to prevent shell interpretation
2. **Test Decryption**: Always test that you can decrypt values after encrypting them
3. **Document Secrets**: Keep track of which environment variables are encrypted
4. **Verify Keys**: Ensure you're using the correct encryption key for each environment
5. **Backup Process**: Have a process to recover if encryption keys are lost

## Next Steps

- [API Reference](api-reference.md) - Complete API documentation
- [Examples](../examples/README.md) - See practical examples
- [Troubleshooting](troubleshooting.md) - Common command issues