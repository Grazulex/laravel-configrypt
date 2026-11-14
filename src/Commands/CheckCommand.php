<?php

declare(strict_types=1);

namespace LaravelConfigrypt\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckCommand extends Command
{
    /**
     * Variable name patterns considered sensitive when none are configured.
     *
     * @var list<string>
     */
    protected const array DEFAULT_PATTERNS = [
        '*PASSWORD*',
        '*PASSWD*',
        '*_PASS',
        '*SECRET*',
        '*TOKEN*',
        '*_KEY',
        '*API_KEY*',
        '*PRIVATE*',
        '*CREDENTIAL*',
    ];

    /**
     * Variables that are never reported when none are configured.
     *
     * @var list<string>
     */
    protected const array DEFAULT_IGNORE = [
        'APP_KEY',
        'CONFIGRYPT_KEY',
        'CONFIGRYPT_PREVIOUS_KEYS',
    ];

    protected $signature = 'configrypt:check
                            {--file= : Path to the environment file (defaults to the application .env file)}
                            {--all : Report every non-empty plain-text value, not only sensitive ones}
                            {--pattern=* : Additional variable name pattern to treat as sensitive (e.g. "*_DSN")}
                            {--ignore=* : Variable name (or pattern) to skip}';

    protected $description = 'Detect sensitive values stored in plain text in an environment file';

    public function handle(): int
    {
        $file = $this->resolveFilePath();

        if (! is_file($file) || ! is_readable($file)) {
            $this->error("Environment file not found or not readable: {$file}");

            return self::FAILURE;
        }

        $prefix = config('configrypt.prefix', 'ENC:');
        $prefix = is_string($prefix) && $prefix !== '' ? $prefix : 'ENC:';

        $patterns = array_merge(
            $this->configuredList('configrypt.check.patterns', self::DEFAULT_PATTERNS),
            $this->arrayOption('pattern')
        );
        $ignore = array_merge(
            $this->configuredList('configrypt.check.ignore', self::DEFAULT_IGNORE),
            $this->arrayOption('ignore')
        );
        $all = (bool) $this->option('all');

        $findings = [];

        foreach ($this->parseEnvironment((string) file_get_contents($file)) as $entry) {
            $name = $entry['name'];

            if ($this->matchesAny($name, $ignore)) {
                continue;
            }

            if (! $all && ! $this->matchesAny($name, $patterns)) {
                continue;
            }

            if ($this->isPlainText($entry['value'], $prefix)) {
                $findings[] = $entry;
            }
        }

        if ($findings === []) {
            $this->info("No plain-text sensitive values found in {$file}.");

            return self::SUCCESS;
        }

        $this->error(sprintf('%d plain-text sensitive value(s) found in %s:', count($findings), $file));

        foreach ($findings as $finding) {
            $this->line(sprintf('  - %s (line %d)', $finding['name'], $finding['line']));
        }

        $this->comment('Encrypt them with: php artisan configrypt:encrypt "<value>"');

        return self::FAILURE;
    }

    protected function resolveFilePath(): string
    {
        $file = $this->option('file');

        if (is_string($file) && $file !== '') {
            return $file;
        }

        return app()->environmentFilePath();
    }

    /**
     * @return list<string>
     */
    protected function arrayOption(string $name): array
    {
        $value = $this->option($name);

        return $this->stringList(is_array($value) ? $value : []);
    }

    /**
     * @param  list<string>  $default
     *
     * @return list<string>
     */
    protected function configuredList(string $key, array $default): array
    {
        $value = config($key);

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return is_array($value) ? $this->stringList($value) : $default;
    }

    /**
     * @param  array<mixed>  $values
     *
     * @return list<string>
     */
    protected function stringList(array $values): array
    {
        $list = [];

        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                $list[] = trim($value);
            }
        }

        return $list;
    }

    /**
     * @param  list<string>  $patterns
     */
    protected function matchesAny(string $name, array $patterns): bool
    {
        $name = strtoupper($name);

        foreach ($patterns as $pattern) {
            if (Str::is(strtoupper($pattern), $name)) {
                return true;
            }
        }

        return false;
    }

    protected function isPlainText(string $value, string $prefix): bool
    {
        $value = trim($value);

        if ($value === '' || in_array(strtolower($value), ['null', '(null)'], true)) {
            return false;
        }

        if (str_starts_with($value, $prefix)) {
            return false;
        }

        // A value that only references another variable is checked through that variable.
        return preg_match('/^\$\{[A-Za-z_][A-Za-z0-9_.]*\}$/', $value) !== 1;
    }

    /**
     * Parse the variables of an environment file.
     *
     * Supports `export` prefixes, single and double quoted values (including
     * multi-line quoted values) and inline comments on unquoted values.
     *
     * @return list<array{name: string, value: string, line: int}>
     */
    protected function parseEnvironment(string $content): array
    {
        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        $count = count($lines);
        $entries = [];

        for ($index = 0; $index < $count; $index++) {
            $line = ltrim($lines[$index]);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^(?:export[ \t]+)?([A-Za-z_][A-Za-z0-9_.]*)[ \t]*=[ \t]*(.*)$/', $line, $matches) !== 1) {
                continue;
            }

            $lineNumber = $index + 1;
            $raw = $matches[2];
            $quote = $raw !== '' ? $raw[0] : '';

            if ($quote === '"' || $quote === "'") {
                $rest = substr($raw, 1);
                $end = $this->closingQuotePosition($rest, $quote);

                while ($end === null && $index + 1 < $count) {
                    $index++;
                    $rest .= "\n" . $lines[$index];
                    $end = $this->closingQuotePosition($rest, $quote);
                }

                $value = $end === null ? $rest : substr($rest, 0, $end);
            } else {
                $value = trim((string) preg_replace('/(^|[ \t]+)#.*$/', '', $raw));
            }

            $entries[] = ['name' => $matches[1], 'value' => $value, 'line' => $lineNumber];
        }

        return $entries;
    }

    protected function closingQuotePosition(string $value, string $quote): ?int
    {
        $length = strlen($value);

        for ($position = 0; $position < $length; $position++) {
            if ($quote === '"' && $value[$position] === '\\') {
                $position++;

                continue;
            }

            if ($value[$position] === $quote) {
                return $position;
            }
        }

        return null;
    }
}
