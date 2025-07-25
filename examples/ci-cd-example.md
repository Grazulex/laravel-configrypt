# CI/CD Pipeline Integration Example

This example demonstrates how to integrate Laravel Configrypt with CI/CD pipelines, specifically showing GitHub Actions configuration for secure deployment with encrypted environment variables.

## GitHub Actions Workflow

### .github/workflows/deploy.yml

```yaml
name: Deploy Laravel Application

on:
  push:
    branches: [ main, production ]
  pull_request:
    branches: [ main ]

env:
  PHP_VERSION: '8.3'

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: laravel_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        extensions: mbstring, pdo, pdo_mysql, intl, zip
        coverage: xdebug
        
    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-
          
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress --no-suggest --no-interaction
      
    - name: Setup test environment
      run: |
        # Copy test environment file
        cp .env.example .env.testing
        
        # Generate Laravel application key
        php artisan key:generate --env=testing
        
        # Set up test-specific encryption key
        echo "CONFIGRYPT_KEY=${{ secrets.CONFIGRYPT_KEY_TEST }}" >> .env.testing
        
        # Add encrypted test database password
        DB_PASSWORD_ENC=$(php artisan configrypt:encrypt "password")
        echo "DB_PASSWORD=$DB_PASSWORD_ENC" >> .env.testing
        
    - name: Run tests
      run: |
        php artisan test --env=testing
        
    - name: Run static analysis
      run: |
        composer run phpstan
        composer run pint -- --test

  deploy-staging:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    environment: staging
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
      
    - name: Create staging environment file
      run: |
        echo "# Staging Environment Configuration" > .env.staging
        echo "APP_NAME=\"Laravel App (Staging)\"" >> .env.staging
        echo "APP_ENV=staging" >> .env.staging
        echo "APP_DEBUG=false" >> .env.staging
        echo "APP_URL=https://staging.example.com" >> .env.staging
        echo "" >> .env.staging
        
        # Configrypt settings
        echo "# Configrypt Configuration" >> .env.staging
        echo "CONFIGRYPT_KEY=${{ secrets.CONFIGRYPT_KEY_STAGING }}" >> .env.staging
        echo "CONFIGRYPT_PREFIX=ENC:" >> .env.staging
        echo "CONFIGRYPT_AUTO_DECRYPT=true" >> .env.staging
        echo "" >> .env.staging
        
        # Database configuration
        echo "# Database Configuration" >> .env.staging
        echo "DB_CONNECTION=mysql" >> .env.staging
        echo "DB_HOST=${{ secrets.DB_HOST_STAGING }}" >> .env.staging
        echo "DB_PORT=3306" >> .env.staging
        echo "DB_DATABASE=${{ secrets.DB_DATABASE_STAGING }}" >> .env.staging
        echo "DB_USERNAME=${{ secrets.DB_USERNAME_STAGING }}" >> .env.staging
        
        # Encrypt database password
        DB_PASSWORD_ENC=$(php artisan configrypt:encrypt "${{ secrets.DB_PASSWORD_STAGING }}")
        echo "DB_PASSWORD=$DB_PASSWORD_ENC" >> .env.staging
        echo "" >> .env.staging
        
        # API Keys (encrypt sensitive ones)
        echo "# API Configuration" >> .env.staging
        STRIPE_SECRET_ENC=$(php artisan configrypt:encrypt "${{ secrets.STRIPE_SECRET_STAGING }}")
        echo "STRIPE_SECRET=$STRIPE_SECRET_ENC" >> .env.staging
        
        MAILGUN_SECRET_ENC=$(php artisan configrypt:encrypt "${{ secrets.MAILGUN_SECRET_STAGING }}")
        echo "MAILGUN_SECRET=$MAILGUN_SECRET_ENC" >> .env.staging
        
    - name: Deploy to staging
      run: |
        # Deployment commands (rsync, SSH, etc.)
        echo "Deploying to staging server..."
        # rsync -avz --exclude='.git' . user@staging-server:/var/www/laravel/
        # ssh user@staging-server "cd /var/www/laravel && php artisan migrate --force"

  deploy-production:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/production'
    
    environment: production
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
      
    - name: Create production environment file
      run: |
        echo "# Production Environment Configuration" > .env.production
        echo "APP_NAME=\"Laravel App\"" >> .env.production
        echo "APP_ENV=production" >> .env.production
        echo "APP_DEBUG=false" >> .env.production
        echo "APP_URL=https://example.com" >> .env.production
        echo "" >> .env.production
        
        # Configrypt settings
        echo "# Configrypt Configuration" >> .env.production
        echo "CONFIGRYPT_KEY=${{ secrets.CONFIGRYPT_KEY_PRODUCTION }}" >> .env.production
        echo "CONFIGRYPT_PREFIX=ENC:" >> .env.production
        echo "CONFIGRYPT_AUTO_DECRYPT=true" >> .env.production
        echo "" >> .env.production
        
        # Database configuration
        echo "# Database Configuration" >> .env.production
        echo "DB_CONNECTION=mysql" >> .env.production
        echo "DB_HOST=${{ secrets.DB_HOST_PRODUCTION }}" >> .env.production
        echo "DB_PORT=3306" >> .env.production
        echo "DB_DATABASE=${{ secrets.DB_DATABASE_PRODUCTION }}" >> .env.production
        echo "DB_USERNAME=${{ secrets.DB_USERNAME_PRODUCTION }}" >> .env.production
        
        # Encrypt database password
        DB_PASSWORD_ENC=$(php artisan configrypt:encrypt "${{ secrets.DB_PASSWORD_PRODUCTION }}")
        echo "DB_PASSWORD=$DB_PASSWORD_ENC" >> .env.production
        echo "" >> .env.production
        
        # API Keys (encrypt all sensitive ones)
        echo "# API Configuration" >> .env.production
        STRIPE_SECRET_ENC=$(php artisan configrypt:encrypt "${{ secrets.STRIPE_SECRET_PRODUCTION }}")
        echo "STRIPE_SECRET=$STRIPE_SECRET_ENC" >> .env.production
        
        MAILGUN_SECRET_ENC=$(php artisan configrypt:encrypt "${{ secrets.MAILGUN_SECRET_PRODUCTION }}")
        echo "MAILGUN_SECRET=$MAILGUN_SECRET_ENC" >> .env.production
        
        AWS_SECRET_ENC=$(php artisan configrypt:encrypt "${{ secrets.AWS_SECRET_ACCESS_KEY_PRODUCTION }}")
        echo "AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ENC" >> .env.production
        
    - name: Validate encryption
      run: |
        echo "Validating encrypted values..."
        
        # Test that we can decrypt the values we just encrypted
        php artisan configrypt:decrypt "$(grep 'DB_PASSWORD=' .env.production | cut -d'=' -f2)"
        php artisan configrypt:decrypt "$(grep 'STRIPE_SECRET=' .env.production | cut -d'=' -f2)"
        
        echo "✓ All encrypted values validated successfully"
        
    - name: Deploy to production
      run: |
        # Production deployment commands
        echo "Deploying to production server..."
        # rsync -avz --exclude='.git' . user@production-server:/var/www/laravel/
        # ssh user@production-server "cd /var/www/laravel && php artisan migrate --force"
        # ssh user@production-server "cd /var/www/laravel && php artisan config:cache"
        # ssh user@production-server "cd /var/www/laravel && php artisan route:cache"
        
    - name: Health check
      run: |
        echo "Performing post-deployment health checks..."
        # curl -f https://example.com/health || exit 1

  security-scan:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Run security audit
      run: |
        composer audit
        
    - name: Check for exposed secrets
      run: |
        # Ensure no plain text secrets are committed
        if grep -r "sk_live_" . --exclude-dir=.git --exclude-dir=vendor; then
          echo "❌ Found potential plain text Stripe keys"
          exit 1
        fi
        
        if grep -r "key-[a-f0-9]" . --exclude-dir=.git --exclude-dir=vendor; then
          echo "❌ Found potential plain text Mailgun keys"
          exit 1
        fi
        
        echo "✓ No plain text secrets found"
```

## GitLab CI/CD Example

### .gitlab-ci.yml

```yaml
stages:
  - test
  - security
  - deploy

variables:
  PHP_VERSION: "8.3"
  MYSQL_ROOT_PASSWORD: "password"
  MYSQL_DATABASE: "laravel_test"

cache:
  paths:
    - vendor/

test:
  stage: test
  image: php:8.3
  
  services:
    - mysql:8.0
    
  before_script:
    # Install PHP extensions
    - apt-get update -qq && apt-get install -y -qq git curl libmcrypt-dev libjpeg-dev libpng-dev libfreetype6-dev libbz2-dev
    - docker-php-ext-install pdo pdo_mysql
    
    # Install Composer
    - curl -sS https://getcomposer.org/installer | php
    - mv composer.phar /usr/local/bin/composer
    
    # Install dependencies
    - composer install --prefer-dist --no-ansi --no-interaction --no-progress --no-scripts
    
    # Setup test environment
    - cp .env.example .env.testing
    - php artisan key:generate --env=testing
    - echo "CONFIGRYPT_KEY=$CONFIGRYPT_KEY_TEST" >> .env.testing
    
    # Encrypt test database password
    - DB_PASSWORD_ENC=$(php artisan configrypt:encrypt "password")
    - echo "DB_PASSWORD=$DB_PASSWORD_ENC" >> .env.testing
    
  script:
    - php artisan test --env=testing
    - composer run phpstan
    
deploy_staging:
  stage: deploy
  image: php:8.3
  
  environment:
    name: staging
    url: https://staging.example.com
    
  only:
    - main
    
  before_script:
    - curl -sS https://getcomposer.org/installer | php
    - mv composer.phar /usr/local/bin/composer
    - composer install --no-dev --optimize-autoloader
    
  script:
    # Create staging environment
    - |
      cat > .env.staging << EOF
      APP_NAME="Laravel App (Staging)"
      APP_ENV=staging
      APP_DEBUG=false
      CONFIGRYPT_KEY=$CONFIGRYPT_KEY_STAGING
      
      DB_CONNECTION=mysql
      DB_HOST=$DB_HOST_STAGING
      DB_DATABASE=$DB_DATABASE_STAGING
      DB_USERNAME=$DB_USERNAME_STAGING
      DB_PASSWORD=$(php artisan configrypt:encrypt "$DB_PASSWORD_STAGING")
      
      STRIPE_SECRET=$(php artisan configrypt:encrypt "$STRIPE_SECRET_STAGING")
      MAILGUN_SECRET=$(php artisan configrypt:encrypt "$MAILGUN_SECRET_STAGING")
      EOF
      
    # Deploy to staging server
    - echo "Deploying to staging..."
    
deploy_production:
  stage: deploy
  image: php:8.3
  
  environment:
    name: production
    url: https://example.com
    
  only:
    - production
    
  when: manual
  
  before_script:
    - curl -sS https://getcomposer.org/installer | php
    - mv composer.phar /usr/local/bin/composer
    - composer install --no-dev --optimize-autoloader
    
  script:
    # Create production environment
    - |
      cat > .env.production << EOF
      APP_NAME="Laravel App"
      APP_ENV=production
      APP_DEBUG=false
      CONFIGRYPT_KEY=$CONFIGRYPT_KEY_PRODUCTION
      
      DB_CONNECTION=mysql
      DB_HOST=$DB_HOST_PRODUCTION
      DB_DATABASE=$DB_DATABASE_PRODUCTION
      DB_USERNAME=$DB_USERNAME_PRODUCTION
      DB_PASSWORD=$(php artisan configrypt:encrypt "$DB_PASSWORD_PRODUCTION")
      
      STRIPE_SECRET=$(php artisan configrypt:encrypt "$STRIPE_SECRET_PRODUCTION")
      MAILGUN_SECRET=$(php artisan configrypt:encrypt "$MAILGUN_SECRET_PRODUCTION")
      AWS_SECRET_ACCESS_KEY=$(php artisan configrypt:encrypt "$AWS_SECRET_ACCESS_KEY_PRODUCTION")
      EOF
      
    # Validate encryption
    - php artisan configrypt:decrypt "$(grep 'DB_PASSWORD=' .env.production | cut -d'=' -f2)"
    
    # Deploy to production server
    - echo "Deploying to production..."

security_audit:
  stage: security
  image: php:8.3
  
  script:
    - composer audit
    - |
      if grep -r "sk_live_" . --exclude-dir=.git --exclude-dir=vendor; then
        echo "❌ Found potential plain text secrets"
        exit 1
      fi
    - echo "✓ Security audit passed"
```

## Required Secrets Configuration

### GitHub Secrets

Configure these secrets in your GitHub repository settings:

**Test Environment:**
- `CONFIGRYPT_KEY_TEST`: Test encryption key

**Staging Environment:**
- `CONFIGRYPT_KEY_STAGING`: Staging encryption key
- `DB_HOST_STAGING`: Staging database host
- `DB_DATABASE_STAGING`: Staging database name
- `DB_USERNAME_STAGING`: Staging database username
- `DB_PASSWORD_STAGING`: Staging database password
- `STRIPE_SECRET_STAGING`: Staging Stripe secret key
- `MAILGUN_SECRET_STAGING`: Staging Mailgun API key

**Production Environment:**
- `CONFIGRYPT_KEY_PRODUCTION`: Production encryption key
- `DB_HOST_PRODUCTION`: Production database host
- `DB_DATABASE_PRODUCTION`: Production database name
- `DB_USERNAME_PRODUCTION`: Production database username
- `DB_PASSWORD_PRODUCTION`: Production database password
- `STRIPE_SECRET_PRODUCTION`: Production Stripe secret key
- `MAILGUN_SECRET_PRODUCTION`: Production Mailgun API key
- `AWS_SECRET_ACCESS_KEY_PRODUCTION`: Production AWS secret key

### GitLab Variables

Configure these variables in your GitLab project settings:

**Protected Variables (for production):**
- `CONFIGRYPT_KEY_PRODUCTION`
- `DB_PASSWORD_PRODUCTION`
- `STRIPE_SECRET_PRODUCTION`

**Regular Variables (for staging/test):**
- `CONFIGRYPT_KEY_STAGING`
- `CONFIGRYPT_KEY_TEST`
- `DB_PASSWORD_STAGING`
- `STRIPE_SECRET_STAGING`

## Docker Integration

### Dockerfile

```dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create user for Laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Change ownership of our applications
COPY --chown=www:www . /var/www

# Change to the www user
USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: laravel-app
    container_name: laravel-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    environment:
      - CONFIGRYPT_KEY=${CONFIGRYPT_KEY}
    networks:
      - laravel

  webserver:
    image: nginx:alpine
    container_name: laravel-webserver
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
    networks:
      - laravel

  database:
    image: mysql:8.0
    container_name: laravel-database
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: laravel
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD_PLAIN}
      MYSQL_USER: ${DB_USERNAME}
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - laravel

networks:
  laravel:
    driver: bridge

volumes:
  dbdata:
    driver: local
```

### .env.docker

```bash
# Docker environment configuration
CONFIGRYPT_KEY=docker-key-32-characters-long--

# Database configuration (plain text for Docker environment variables)
DB_ROOT_PASSWORD=root_password
DB_PASSWORD_PLAIN=app_password
DB_USERNAME=laravel_user

# Application database password (encrypted for Laravel)
DB_PASSWORD=ENC:encrypted-password-here
```

## Security Best Practices

1. **Never commit encryption keys to repository**
2. **Use different keys for each environment**
3. **Encrypt at deployment time, not in source code**
4. **Validate encrypted values after deployment**
5. **Audit for plain text secrets in CI/CD logs**
6. **Use secure secret management in CI/CD platforms**
7. **Implement proper access controls for deployment environments**
8. **Monitor deployment processes for security issues**

## Deployment Validation Script

### scripts/validate-deployment.sh

```bash
#!/bin/bash

# Validate deployment script
ENVIRONMENT=$1

if [ -z "$ENVIRONMENT" ]; then
    echo "Usage: $0 <environment>"
    exit 1
fi

echo "Validating $ENVIRONMENT deployment..."

# Check if environment file exists
if [ ! -f ".env.$ENVIRONMENT" ]; then
    echo "❌ .env.$ENVIRONMENT file not found"
    exit 1
fi

# Load environment variables
source .env.$ENVIRONMENT

# Check if encryption key is set
if [ -z "$CONFIGRYPT_KEY" ]; then
    echo "❌ CONFIGRYPT_KEY not set"
    exit 1
fi

# Test decryption of critical values
echo "Testing encrypted value decryption..."

if php artisan configrypt:decrypt "$DB_PASSWORD" > /dev/null 2>&1; then
    echo "✓ Database password decryption successful"
else
    echo "❌ Database password decryption failed"
    exit 1
fi

if [ ! -z "$STRIPE_SECRET" ]; then
    if php artisan configrypt:decrypt "$STRIPE_SECRET" > /dev/null 2>&1; then
        echo "✓ Stripe secret decryption successful"
    else
        echo "❌ Stripe secret decryption failed"
        exit 1
    fi
fi

echo "✓ Deployment validation successful for $ENVIRONMENT"
```

This CI/CD integration ensures that:
- Secrets are encrypted at deployment time
- Different encryption keys are used per environment
- Deployment validation confirms encrypted values work
- Security audits prevent plain text secrets from being committed
- Proper environment separation is maintained