<?php

namespace App\Services\Backup;

class CronCommandGenerator
{
    /**
     * Generate the recommended cPanel Cron Job command.
     */
    public function generate(?string $basePath = null, ?string $phpBinary = null, ?string $extensions = null): string
    {
        $base = $basePath ?? base_path();
        $php = $phpBinary ?? (string) config('kasme.php_cli_binary', PHP_BINARY ?: 'php');
        $exts = $extensions ?? (string) config('kasme.php_cli_extensions', '');

        $escapedBase = $this->escapePath($base);
        $escapedPhp = $this->escapePath($php);
        $extensionFlags = $this->formatExtensionFlags($exts);

        $flagsPart = $extensionFlags !== '' ? ' ' . $extensionFlags : '';

        return "* * * * * cd {$escapedBase} && {$escapedPhp}{$flagsPart} artisan schedule:run >> /dev/null 2>&1";
    }

    /**
     * Format PHP extension flags (-d extension=foo.so) safely.
     * Validates strictly that extension names contain only [a-zA-Z0-9_-].
     */
    public function formatExtensionFlags(string $extensions): string
    {
        $items = explode(',', $extensions);
        $flags = [];

        foreach ($items as $item) {
            $name = trim($item);
            if ($name === '') {
                continue;
            }

            // Strict validation: only allow alphanumeric, underscore, and dash
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                $flags[] = "-d extension={$name}.so";
            }
        }

        return implode(' ', $flags);
    }

    /**
     * Escape path safely for POSIX shell command execution.
     */
    public function escapePath(string $path): string
    {
        return "'" . str_replace("'", "'\\''", $path) . "'";
    }
}
