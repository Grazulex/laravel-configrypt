<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use Orchestra\Testbench\TestCase;

class CheckCommandTest extends TestCase
{
    private const string KEY = 'test-key-1234567890123456789012';

    private string $directory;

    private string $envFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/configrypt-check-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
        $this->envFile = $this->directory . '/.env';
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->directory) ?: [] as $entry) {
            $path = $this->directory . '/' . $entry;

            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelConfigryptServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('configrypt.key', self::KEY);
        $app['config']->set('configrypt.prefix', 'ENC:');
        $app['config']->set('configrypt.cipher', 'AES-256-CBC');
    }

    private function encrypt(string $value): string
    {
        return (new ConfigryptService(self::KEY, 'ENC:', 'AES-256-CBC'))->encrypt($value);
    }

    /**
     * @param  list<string>  $lines
     */
    private function writeEnv(array $lines): void
    {
        file_put_contents($this->envFile, implode("\n", $lines) . "\n");
    }

    public function test_command_is_registered(): void
    {
        $this->artisan('configrypt:check --help')->assertExitCode(0);
    }

    public function test_succeeds_when_sensitive_values_are_encrypted(): void
    {
        $this->writeEnv([
            'APP_NAME=Demo',
            'APP_KEY=base64:abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG=',
            'CONFIGRYPT_KEY=' . self::KEY,
            'DB_PASSWORD=' . $this->encrypt('db-secret'),
            'API_TOKEN="' . $this->encrypt('token') . '"',
            'export STRIPE_SECRET=' . $this->encrypt('stripe') . ' # stripe',
            'MAIL_PASSWORD=',
            'REDIS_PASSWORD=null',
            'DB_HOST=127.0.0.1',
        ]);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])
            ->expectsOutput("No plain-text sensitive values found in {$this->envFile}.")
            ->assertExitCode(0)
            ->run();
    }

    public function test_fails_and_lists_plain_text_sensitive_values(): void
    {
        $this->writeEnv([
            'APP_NAME=Demo',
            'DB_PASSWORD=plain-secret',
            'API_TOKEN="plain-token"',
            'export AWS_SECRET_ACCESS_KEY=abc123 # comment',
            'STRIPE_KEY=' . $this->encrypt('stripe'),
        ]);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])
            ->expectsOutput("3 plain-text sensitive value(s) found in {$this->envFile}:")
            ->expectsOutput('  - DB_PASSWORD (line 2)')
            ->expectsOutput('  - API_TOKEN (line 3)')
            ->expectsOutput('  - AWS_SECRET_ACCESS_KEY (line 4)')
            ->doesntExpectOutput('  - STRIPE_KEY (line 5)')
            ->assertExitCode(1)
            ->run();
    }

    public function test_never_prints_the_plain_text_values(): void
    {
        $this->writeEnv(['DB_PASSWORD=super-secret-value']);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])
            ->doesntExpectOutputToContain('super-secret-value')
            ->assertExitCode(1)
            ->run();
    }

    public function test_ignores_comments_and_empty_values(): void
    {
        $this->writeEnv([
            '# DB_PASSWORD=commented-out',
            'DB_PASSWORD= # no value',
            "API_TOKEN=''",
            'SECRET_REF=${OTHER_SECRET}',
        ]);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])->assertExitCode(0)->run();
    }

    public function test_handles_multiline_quoted_values(): void
    {
        $this->writeEnv([
            'PRIVATE_CERT="-----BEGIN-----',
            'DB_PASSWORD=not-a-variable',
            '-----END-----"',
            'APP_NAME=Demo',
        ]);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])
            ->expectsOutput('  - PRIVATE_CERT (line 1)')
            ->doesntExpectOutput('  - DB_PASSWORD (line 2)')
            ->assertExitCode(1)
            ->run();
    }

    public function test_default_ignore_list_skips_encryption_keys(): void
    {
        $this->writeEnv([
            'APP_KEY=base64:abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG=',
            'CONFIGRYPT_KEY=' . self::KEY,
            'CONFIGRYPT_PREVIOUS_KEYS=old-key',
        ]);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])->assertExitCode(0)->run();
    }

    public function test_ignore_option_skips_variables(): void
    {
        $this->writeEnv([
            'DB_PASSWORD=plain',
            'PUSHER_APP_KEY=public-key',
        ]);

        $this->artisan('configrypt:check', [
            '--file' => $this->envFile,
            '--ignore' => ['DB_PASSWORD', 'PUSHER_*'],
        ])->assertExitCode(0)->run();
    }

    public function test_pattern_option_adds_sensitive_names(): void
    {
        $this->writeEnv(['SENTRY_DSN=https://public@sentry.example.com/1']);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])->assertExitCode(0)->run();

        $this->artisan('configrypt:check', [
            '--file' => $this->envFile,
            '--pattern' => ['*_dsn'],
        ])->expectsOutput('  - SENTRY_DSN (line 1)')->assertExitCode(1)->run();
    }

    public function test_all_option_reports_every_plain_text_value(): void
    {
        $this->writeEnv([
            'APP_NAME=Demo',
            'APP_KEY=base64:abc',
            'DB_PASSWORD=' . $this->encrypt('secret'),
        ]);

        $this->artisan('configrypt:check', ['--file' => $this->envFile, '--all' => true])
            ->expectsOutput("1 plain-text sensitive value(s) found in {$this->envFile}:")
            ->expectsOutput('  - APP_NAME (line 1)')
            ->assertExitCode(1)
            ->run();
    }

    public function test_uses_configured_patterns_and_ignore_list(): void
    {
        config()->set('configrypt.check.patterns', ['LICENSE_*']);
        config()->set('configrypt.check.ignore', []);

        $this->writeEnv([
            'DB_PASSWORD=plain',
            'LICENSE_NUMBER=1234',
        ]);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])
            ->expectsOutput('  - LICENSE_NUMBER (line 2)')
            ->doesntExpectOutput('  - DB_PASSWORD (line 1)')
            ->assertExitCode(1)
            ->run();
    }

    public function test_uses_configured_prefix(): void
    {
        config()->set('configrypt.prefix', 'SECURE:');

        $this->writeEnv([
            'DB_PASSWORD=SECURE:abc',
            'API_TOKEN=ENC:abc',
        ]);

        $this->artisan('configrypt:check', ['--file' => $this->envFile])
            ->expectsOutput('  - API_TOKEN (line 2)')
            ->assertExitCode(1)
            ->run();
    }

    public function test_handles_crlf_line_endings(): void
    {
        file_put_contents($this->envFile, "APP_NAME=Demo\r\nDB_PASSWORD=" . $this->encrypt('secret') . "\r\nAPI_TOKEN=plain\r\n");

        $this->artisan('configrypt:check', ['--file' => $this->envFile])
            ->expectsOutput('  - API_TOKEN (line 3)')
            ->doesntExpectOutput('  - DB_PASSWORD (line 2)')
            ->assertExitCode(1)
            ->run();
    }

    public function test_fails_when_file_is_missing(): void
    {
        $this->artisan('configrypt:check', ['--file' => $this->directory . '/missing.env'])
            ->assertExitCode(1)
            ->run();
    }
}
