<?php

use LaravelConfigrypt\LaravelConfigryptServiceProvider;
use LaravelConfigrypt\Services\ConfigryptService;
use Orchestra\Testbench\TestCase;

class RotateKeysCommandTest extends TestCase
{
    private const string OLD_KEY = 'test-key-1234567890123456789012';

    private const string NEW_KEY = 'new-key-abcdefghijklmnopqrstuvw';

    private string $directory;

    private string $envFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/configrypt-rotate-' . bin2hex(random_bytes(6));
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
        $app['config']->set('configrypt.key', self::OLD_KEY);
        $app['config']->set('configrypt.prefix', 'ENC:');
        $app['config']->set('configrypt.cipher', 'AES-256-CBC');
    }

    private function service(string $key): ConfigryptService
    {
        return new ConfigryptService($key, 'ENC:', 'AES-256-CBC');
    }

    /**
     * @return array<string, string>
     */
    private function readEnv(): array
    {
        $values = [];

        foreach (preg_split('/\r?\n/', (string) file_get_contents($this->envFile)) ?: [] as $line) {
            if (str_contains($line, '=')) {
                [$name, $value] = explode('=', $line, 2);
                $values[trim($name)] = $value;
            }
        }

        return $values;
    }

    public function test_command_is_registered(): void
    {
        $this->artisan('configrypt:rotate-keys --help')->assertExitCode(0);
    }

    public function test_rotates_encrypted_values_with_new_key(): void
    {
        $old = $this->service(self::OLD_KEY);

        file_put_contents($this->envFile, implode("\n", [
            'APP_NAME=Demo',
            'CONFIGRYPT_KEY=' . self::OLD_KEY,
            'DB_PASSWORD=' . $old->encrypt('db-secret'),
            'API_KEY="' . $old->encrypt('api-secret') . '"',
            'export MAIL_PASSWORD=' . $old->encrypt('mail-secret') . ' # mail',
            'PLAIN=value',
            '',
        ]));

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--new-key' => self::NEW_KEY,
            '--force' => true,
            '--no-backup' => true,
        ])->expectsOutput('Rotated 3 value(s).')->assertExitCode(0)->run();

        $new = $this->service(self::NEW_KEY);
        $env = $this->readEnv();

        $this->assertSame('Demo', $env['APP_NAME']);
        $this->assertSame('value', $env['PLAIN']);
        $this->assertSame(self::NEW_KEY, $env['CONFIGRYPT_KEY']);
        $this->assertSame('db-secret', $new->decrypt($env['DB_PASSWORD']));

        $this->assertStringStartsWith('"ENC:', $env['API_KEY']);
        $this->assertSame('api-secret', $new->decrypt(trim($env['API_KEY'], '"')));

        $this->assertArrayHasKey('export MAIL_PASSWORD', $env);
        $this->assertStringEndsWith(' # mail', $env['export MAIL_PASSWORD']);
        $this->assertSame('mail-secret', $new->decrypt(substr($env['export MAIL_PASSWORD'], 0, -strlen(' # mail'))));

        $this->expectException(Exception::class);
        $old->decrypt($env['DB_PASSWORD']);
    }

    public function test_creates_a_backup_by_default(): void
    {
        $original = 'SECRET=' . $this->service(self::OLD_KEY)->encrypt('value') . "\n";
        file_put_contents($this->envFile, $original);

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--new-key' => self::NEW_KEY,
            '--force' => true,
        ])->assertExitCode(0)->run();

        $backups = glob($this->envFile . '.backup-*') ?: [];

        $this->assertCount(1, $backups);
        $this->assertSame($original, file_get_contents($backups[0]));
        $this->assertNotSame($original, file_get_contents($this->envFile));
    }

    public function test_uses_old_key_option(): void
    {
        $otherKey = 'other-key-0987654321098765432109';
        file_put_contents($this->envFile, 'SECRET=' . $this->service($otherKey)->encrypt('value') . "\n");

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--old-key' => $otherKey,
            '--new-key' => self::NEW_KEY,
            '--force' => true,
            '--no-backup' => true,
        ])->assertExitCode(0)->run();

        $this->assertSame('value', $this->service(self::NEW_KEY)->decrypt($this->readEnv()['SECRET']));
    }

    public function test_generates_a_new_key_when_none_is_given(): void
    {
        file_put_contents($this->envFile, implode("\n", [
            'CONFIGRYPT_KEY=' . self::OLD_KEY,
            'SECRET=' . $this->service(self::OLD_KEY)->encrypt('value'),
        ]));

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--force' => true,
            '--no-backup' => true,
        ])->assertExitCode(0)->run();

        $env = $this->readEnv();

        $this->assertNotSame(self::OLD_KEY, $env['CONFIGRYPT_KEY']);
        $this->assertSame(32, strlen($env['CONFIGRYPT_KEY']));
        $this->assertSame('value', $this->service($env['CONFIGRYPT_KEY'])->decrypt($env['SECRET']));
    }

    public function test_dry_run_does_not_modify_the_file(): void
    {
        $original = 'SECRET=' . $this->service(self::OLD_KEY)->encrypt('value') . "\n";
        file_put_contents($this->envFile, $original);

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--new-key' => self::NEW_KEY,
            '--dry-run' => true,
        ])->expectsOutput('Dry run: no changes were made.')->assertExitCode(0)->run();

        $this->assertSame($original, file_get_contents($this->envFile));
        $this->assertSame([], glob($this->envFile . '.backup-*') ?: []);
    }

    public function test_aborts_without_changes_when_a_value_cannot_be_decrypted(): void
    {
        $original = implode("\n", [
            'GOOD=' . $this->service(self::OLD_KEY)->encrypt('good'),
            'BAD=' . $this->service('unrelated-key-11111111111111111111')->encrypt('bad'),
        ]);
        file_put_contents($this->envFile, $original);

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--new-key' => self::NEW_KEY,
            '--force' => true,
        ])->expectsOutput('Unable to decrypt the following values with the current key: BAD')
            ->assertExitCode(1)
            ->run();

        $this->assertSame($original, file_get_contents($this->envFile));
    }

    public function test_cancelled_confirmation_does_not_modify_the_file(): void
    {
        $original = 'SECRET=' . $this->service(self::OLD_KEY)->encrypt('value') . "\n";
        file_put_contents($this->envFile, $original);

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--new-key' => self::NEW_KEY,
        ])->expectsConfirmation('Re-encrypt these values with the new key?', 'no')
            ->expectsOutput('Key rotation cancelled.')
            ->assertExitCode(1)
            ->run();

        $this->assertSame($original, file_get_contents($this->envFile));
    }

    public function test_preserves_crlf_line_endings(): void
    {
        file_put_contents($this->envFile, 'SECRET=' . $this->service(self::OLD_KEY)->encrypt('value') . "\r\nOTHER=1\r\n");

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--new-key' => self::NEW_KEY,
            '--force' => true,
            '--no-backup' => true,
        ])->expectsOutput('Rotated 1 value(s).')->assertExitCode(0)->run();

        $content = (string) file_get_contents($this->envFile);

        $this->assertStringEndsWith("\r\nOTHER=1\r\n", $content);
        $this->assertSame('value', $this->service(self::NEW_KEY)->decrypt($this->readEnv()['SECRET']));
    }

    public function test_succeeds_when_no_encrypted_values_exist(): void
    {
        file_put_contents($this->envFile, "APP_NAME=Demo\n");

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--new-key' => self::NEW_KEY,
            '--force' => true,
        ])->expectsOutput("No encrypted values found in {$this->envFile}.")->assertExitCode(0)->run();

        $this->assertSame("APP_NAME=Demo\n", file_get_contents($this->envFile));
    }

    public function test_fails_when_file_is_missing(): void
    {
        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->directory . '/missing.env',
            '--new-key' => self::NEW_KEY,
            '--force' => true,
        ])->assertExitCode(1)->run();
    }

    public function test_fails_when_new_key_equals_old_key(): void
    {
        file_put_contents($this->envFile, 'SECRET=' . $this->service(self::OLD_KEY)->encrypt('value') . "\n");

        $this->artisan('configrypt:rotate-keys', [
            '--file' => $this->envFile,
            '--new-key' => self::OLD_KEY,
            '--force' => true,
        ])->expectsOutput('The new key must be different from the current key.')->assertExitCode(1)->run();
    }

    public function test_service_re_encrypts_values(): void
    {
        $old = $this->service(self::OLD_KEY);
        $new = $this->service(self::NEW_KEY);

        $rotated = $old->reEncrypt($old->encrypt('secret'), $new);

        $this->assertStringStartsWith('ENC:', $rotated);
        $this->assertSame('secret', $new->decrypt($rotated));
    }
}
