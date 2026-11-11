<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LaravelConfigrypt\Services\ConfigryptService;

class RotateKeysCommand extends Command
{
    protected $signature = 'configrypt:rotate-keys
                            {--old-key= : The current encryption key (defaults to the configured key)}
                            {--new-key= : The new encryption key (a random key is generated when omitted)}
                            {--file= : Path to the environment file (defaults to the application .env file)}
                            {--dry-run : Show what would be rotated without writing any file}
                            {--no-backup : Do not create a backup of the environment file}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Re-encrypt the encrypted values of an environment file with a new key';

    public function handle(): int
    {
        $file = $this->resolveFilePath();

        if (! is_file($file) || ! is_readable($file)) {
            $this->error("Environment file not found or not readable: {$file}");

            return self::FAILURE;
        }

        $prefix = config('configrypt.prefix', 'ENC:');
        $prefix = is_string($prefix) && $prefix !== '' ? $prefix : 'ENC:';
        $cipher = config('configrypt.cipher', 'AES-256-CBC');
        $cipher = is_string($cipher) && $cipher !== '' ? $cipher : 'AES-256-CBC';

        $oldKey = $this->stringOption('old-key') ?? $this->configuredKey();
        $newKey = $this->stringOption('new-key');
        $generated = false;

        if ($oldKey === null) {
            $this->error('No current key available. Set CONFIGRYPT_KEY or APP_KEY, or pass --old-key.');

            return self::FAILURE;
        }

        if ($newKey === null) {
            $newKey = Str::random(32);
            $generated = true;
        }

        if ($newKey === $oldKey) {
            $this->error('The new key must be different from the current key.');

            return self::FAILURE;
        }

        try {
            $oldService = new ConfigryptService($oldKey, $prefix, $cipher);
            $newService = new ConfigryptService($newKey, $prefix, $cipher);
        } catch (Exception $e) {
            $this->error('Invalid key: ' . $e->getMessage());

            return self::FAILURE;
        }

        $content = (string) file_get_contents($file);
        $pattern = $this->encryptedLinePattern($prefix);

        $rotated = [];
        $failed = [];

        $newContent = preg_replace_callback(
            $pattern,
            function (array $matches) use ($oldService, $newService, $prefix, &$rotated, &$failed): string {
                $name = $matches[2];

                try {
                    $newValue = $oldService->reEncrypt($prefix . $matches[4], $newService);
                } catch (Exception) {
                    $failed[] = $name;

                    return $matches[0];
                }

                $rotated[] = $name;

                return $matches[1] . $matches[3] . $newValue . $matches[3] . $matches[5];
            },
            $content
        );

        if ($newContent === null) {
            $this->error("Unable to parse the environment file: {$file}");

            return self::FAILURE;
        }

        if ($failed !== []) {
            $this->error('Unable to decrypt the following values with the current key: ' . implode(', ', $failed));
            $this->comment('No changes were made. Check that --old-key (or the configured key) is correct.');

            return self::FAILURE;
        }

        if ($rotated === []) {
            $this->info("No encrypted values found in {$file}.");

            return self::SUCCESS;
        }

        $keyLinePattern = '/^([ \t]*(?:export[ \t]+)?CONFIGRYPT_KEY[ \t]*=[ \t]*)[^\r\n]*(?=\r?$)/m';
        $updatesKeyLine = preg_match($keyLinePattern, $newContent) === 1;

        $this->info(sprintf('%d encrypted value(s) found in %s:', count($rotated), $file));

        foreach ($rotated as $name) {
            $this->line("  - {$name}");
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run: no changes were made.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Re-encrypt these values with the new key?', false)) {
            $this->comment('Key rotation cancelled.');

            return self::FAILURE;
        }

        if ($updatesKeyLine) {
            $newContent = (string) preg_replace_callback(
                $keyLinePattern,
                fn (array $matches): string => $matches[1] . $this->formatEnvValue($newKey),
                $newContent
            );
        }

        if (! $this->option('no-backup')) {
            $backup = $file . '.backup-' . date('YmdHis');

            if (! copy($file, $backup)) {
                $this->error("Unable to create the backup file: {$backup}");

                return self::FAILURE;
            }

            $this->comment("Backup created: {$backup}");
        }

        if (file_put_contents($file, $newContent, LOCK_EX) === false) {
            $this->error("Unable to write the environment file: {$file}");

            return self::FAILURE;
        }

        $this->info(sprintf('Rotated %d value(s).', count($rotated)));

        if ($generated) {
            $this->newLine();
            $this->warn('A new key was generated. Store it safely, it is required to decrypt the values:');
            $this->line($newKey);
        }

        if ($updatesKeyLine) {
            $this->comment('CONFIGRYPT_KEY was updated in the environment file.');
        } else {
            $this->warn('Set CONFIGRYPT_KEY to the new key wherever the application reads it, otherwise decryption will fail.');
        }

        $this->comment('Clear the configuration cache (php artisan config:clear) if it is cached.');

        return self::SUCCESS;
    }

    protected function resolveFilePath(): string
    {
        $file = $this->stringOption('file');

        if ($file !== null) {
            return $file;
        }

        return app()->environmentFilePath();
    }

    protected function configuredKey(): ?string
    {
        $key = config('configrypt.key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    protected function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Pattern matching `NAME=PREFIX<payload>` lines, optionally quoted,
     * prefixed by `export` and followed by an inline comment.
     *
     * Groups: 1 = everything up to the value, 2 = variable name,
     * 3 = opening quote, 4 = payload without prefix, 5 = trailing part.
     */
    protected function encryptedLinePattern(string $prefix): string
    {
        return '/^([ \t]*(?:export[ \t]+)?([A-Za-z_][A-Za-z0-9_.]*)[ \t]*=[ \t]*)(["\']?)'
            . preg_quote($prefix, '/')
            . '([A-Za-z0-9+\/=]+)\3([ \t]*(?:#[^\r\n]*)?)(?=\r?$)/m';
    }

    protected function formatEnvValue(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9_\-.:+\/=]*$/', $value) === 1) {
            return $value;
        }

        if (! str_contains($value, "'")) {
            return "'{$value}'";
        }

        return '"' . addcslashes($value, '"\\$') . '"';
    }
}
