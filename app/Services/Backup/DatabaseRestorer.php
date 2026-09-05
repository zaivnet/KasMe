<?php

namespace App\Services\Backup;

use Exception;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DatabaseRestorer
{
    /**
     * Restore database from the specified SQL dump file.
     *
     * @throws Throwable
     */
    public function restore(string $sqlFilePath): void
    {
        if (! file_exists($sqlFilePath) || ! is_readable($sqlFilePath)) {
            throw new RuntimeException("SQL dump file not found or not readable: {$sqlFilePath}");
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $sqlContent = file_get_contents($sqlFilePath);
        if ($sqlContent === false) {
            throw new RuntimeException("Failed to read SQL dump file: {$sqlFilePath}");
        }

        $this->executeSql($sqlContent, $driver);
    }

    /**
     * Execute SQL statements safely with foreign key checks toggled.
     */
    protected function executeSql(string $sqlContent, string $driver): void
    {
        $statements = $this->splitSqlStatements($sqlContent);

        // Temporarily disable foreign keys
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            DB::statement('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement === '') {
                    continue;
                }
                DB::unprepared($statement);
            }
        } finally {
            // Re-enable foreign keys
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    /**
     * Split SQL content into individual runnable statements, respecting quotes and comments.
     *
     * @return array<string>
     */
    public function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $length = strlen($sql);
        $buffer = '';
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $nextChar = $i + 1 < $length ? $sql[$i + 1] : '';

            // Handle line comments (-- or #)
            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
                continue;
            }

            // Handle block comments (/* ... */)
            if ($inBlockComment) {
                if ($char === '*' && $nextChar === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            // Check for comment starts when outside quotes
            if (! $inSingleQuote && ! $inDoubleQuote && ! $inBacktick) {
                if ($char === '-' && $nextChar === '-') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inLineComment = true;
                    continue;
                }
                if ($char === '/' && $nextChar === '*') {
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            // Handle string quotes
            if ($char === "'" && ! $inDoubleQuote && ! $inBacktick) {
                // Escaped quote
                if ($inSingleQuote && $nextChar === "'") {
                    $buffer .= "''";
                    $i++;
                    continue;
                }
                $inSingleQuote = ! $inSingleQuote;
                $buffer .= $char;
                continue;
            }

            if ($char === '"' && ! $inSingleQuote && ! $inBacktick) {
                $inDoubleQuote = ! $inDoubleQuote;
                $buffer .= $char;
                continue;
            }

            if ($char === '`' && ! $inSingleQuote && ! $inDoubleQuote) {
                $inBacktick = ! $inBacktick;
                $buffer .= $char;
                continue;
            }

            // End of statement
            if ($char === ';' && ! $inSingleQuote && ! $inDoubleQuote && ! $inBacktick) {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}
