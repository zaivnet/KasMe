<?php

namespace App\Services\Backup;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use Throwable;

class DatabaseDumper
{
    /**
     * Dump the database to the specified SQL file path.
     *
     * @return array{method: string, engine: string, tables: array<string>, checksum: string}
     */
    public function dump(string $outputPath): array
    {
        File::ensureDirectoryExists(dirname($outputPath));

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'mysql' && $this->canUseMysqldump()) {
            try {
                $tables = $this->getTableNames($driver);
                $this->dumpWithMysqldump($outputPath);

                return [
                    'method' => 'mysqldump',
                    'engine' => 'mysql',
                    'tables' => $tables,
                    'checksum' => hash_file('sha256', $outputPath),
                ];
            } catch (Throwable $e) {
                // Fallback to PHP dumper if mysqldump fails
            }
        }

        $tables = $this->getTableNames($driver);
        $this->dumpWithPhp($outputPath, $driver, $tables);

        return [
            'method' => 'php_export',
            'engine' => $driver,
            'tables' => $tables,
            'checksum' => hash_file('sha256', $outputPath),
        ];
    }

    /**
     * Check if mysqldump is available and executable.
     */
    public function canUseMysqldump(): bool
    {
        if (! function_exists('proc_open') || ! function_exists('exec')) {
            return false;
        }

        $output = [];
        $returnVar = 0;
        @exec('mysqldump --version', $output, $returnVar);

        return $returnVar === 0;
    }

    /**
     * Dump database using mysqldump CLI securely without printing passwords to process list.
     */
    protected function dumpWithMysqldump(string $outputPath): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $tempCnf = tempnam(sys_get_temp_dir(), 'mycnf_');
        $cnfContent = "[client]\n";
        $cnfContent .= "host=" . addcslashes($config['host'] ?? '127.0.0.1', "\\\n") . "\n";
        $cnfContent .= "port=" . addcslashes((string) ($config['port'] ?? 3306), "\\\n") . "\n";
        $cnfContent .= "user=" . addcslashes($config['username'] ?? '', "\\\n") . "\n";
        $cnfContent .= "password=" . addcslashes($config['password'] ?? '', "\\\n") . "\n";

        File::put($tempCnf, $cnfContent);

        try {
            $cmd = sprintf(
                'mysqldump --defaults-extra-file=%s --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 --no-tablespaces %s > %s',
                escapeshellarg($tempCnf),
                escapeshellarg($config['database']),
                escapeshellarg($outputPath)
            );

            $returnVar = 0;
            $output = [];
            exec($cmd, $output, $returnVar);

            if ($returnVar !== 0 || ! file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('mysqldump failed with return code ' . $returnVar);
            }
        } finally {
            if (file_exists($tempCnf)) {
                @unlink($tempCnf);
            }
        }
    }

    /**
     * Get user tables list for MySQL or SQLite.
     *
     * @return array<string>
     */
    public function getTableNames(string $driver): array
    {
        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

            return array_map(fn ($r) => (string) $r->name, $rows);
        }

        $rows = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name");

        return array_map(fn ($r) => (string) ($r->table_name ?? $r->TABLE_NAME), $rows);
    }

    /**
     * Application-level SQL dump in PHP supporting MySQL and SQLite.
     *
     * @param  array<string>  $tables
     */
    protected function dumpWithPhp(string $outputPath, string $driver, array $tables): void
    {
        $handle = fopen($outputPath, 'wb');
        if (! $handle) {
            throw new RuntimeException("Cannot open file for writing: {$outputPath}");
        }

        try {
            fwrite($handle, "-- KasMe Database Backup\n");
            fwrite($handle, "-- Generated: " . date('c') . "\n");
            fwrite($handle, "-- Driver: {$driver}\n\n");

            if ($driver === 'mysql') {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
                fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");
            } elseif ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");
            }

            $pdo = DB::connection()->getPdo();

            // Order tables: children first for dropping, parents first for creation
            $orderedTables = $this->orderTablesByDependency($tables);
            $reverseOrderedTables = array_reverse($orderedTables);

            // 1. Emit all DROP TABLE statements (child first)
            foreach ($reverseOrderedTables as $table) {
                if ($driver === 'mysql') {
                    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                } elseif ($driver === 'sqlite') {
                    fwrite($handle, "DROP TABLE IF EXISTS \"{$table}\";\n");
                }
            }
            fwrite($handle, "\n");

            // 2. Emit all CREATE TABLE statements (parents first)
            foreach ($orderedTables as $table) {
                if ($driver === 'mysql') {
                    $createRow = DB::selectOne("SHOW CREATE TABLE `{$table}`");
                    $createSql = $createRow->{'Create Table'} ?? array_values((array) $createRow)[1] ?? null;
                    if ($createSql) {
                        fwrite($handle, $createSql . ";\n\n");
                    }
                } elseif ($driver === 'sqlite') {
                    $createRow = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
                    if ($createRow && ! empty($createRow->sql)) {
                        fwrite($handle, $createRow->sql . ";\n\n");
                    }
                }
            }

            // 3. Emit all INSERT data statements
            foreach ($orderedTables as $table) {
                $columns = $this->getTableColumns($table, $driver);
                if (empty($columns)) {
                    continue;
                }

                $query = DB::table($table);
                $cursor = $query->cursor();
                $rowsBatch = [];
                $batchSize = 250;

                foreach ($cursor as $row) {
                    $values = [];
                    foreach ($columns as $col) {
                        $val = $row->{$col} ?? null;
                        if ($val === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($val)) {
                            $values[] = (string) $val;
                        } else {
                            $values[] = $pdo->quote((string) $val);
                        }
                    }
                    $rowsBatch[] = '(' . implode(', ', $values) . ')';

                    if (count($rowsBatch) >= $batchSize) {
                        $this->writeInsertStatement($handle, $table, $columns, $rowsBatch, $driver);
                        $rowsBatch = [];
                    }
                }

                if (! empty($rowsBatch)) {
                    $this->writeInsertStatement($handle, $table, $columns, $rowsBatch, $driver);
                }

                fwrite($handle, "\n");
            }

            if ($driver === 'mysql') {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
            } elseif ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys = ON;\n");
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Order tables so that parent tables come before dependent child tables.
     *
     * @param  array<string>  $tables
     * @return array<string>
     */
    protected function orderTablesByDependency(array $tables): array
    {
        $hierarchy = [
            'users' => 10,
            'cache' => 11,
            'cache_locks' => 12,
            'jobs' => 13,
            'job_batches' => 14,
            'failed_jobs' => 15,
            'sessions' => 16,
            'settings' => 20,
            'accounts' => 21,
            'categories' => 22,
            'debts' => 30,
            'saving_goals' => 31,
            'bills' => 32,
            'budgets' => 33,
            'transactions' => 40,
            'transfers' => 41,
            'debt_payments' => 50,
            'saving_goal_transactions' => 51,
        ];

        usort($tables, function ($a, $b) use ($hierarchy) {
            $rankA = $hierarchy[$a] ?? 99;
            $rankB = $hierarchy[$b] ?? 99;

            if ($rankA === $rankB) {
                return strcmp($a, $b);
            }

            return $rankA <=> $rankB;
        });

        return $tables;
    }

    /**
     * Get column names for a table.
     *
     * @return array<string>
     */
    protected function getTableColumns(string $table, string $driver): array
    {
        if ($driver === 'sqlite') {
            $cols = DB::select("PRAGMA table_info(\"{$table}\")");

            return array_map(fn ($c) => (string) $c->name, $cols);
        }

        $cols = DB::select("SHOW COLUMNS FROM `{$table}`");

        return array_map(fn ($c) => (string) ($c->Field ?? $c->field), $cols);
    }

    /**
     * Write multi-row INSERT statement.
     *
     * @param  resource  $handle
     * @param  array<string>  $columns
     * @param  array<string>  $rowsBatch
     */
    protected function writeInsertStatement($handle, string $table, array $columns, array $rowsBatch, string $driver): void
    {
        $colList = implode(', ', array_map(fn ($c) => $driver === 'mysql' ? "`{$c}`" : "\"{$c}\"", $columns));
        $tableIdentifier = $driver === 'mysql' ? "`{$table}`" : "\"{$table}\"";

        fwrite(
            $handle,
            "INSERT INTO {$tableIdentifier} ({$colList}) VALUES\n" . implode(",\n", $rowsBatch) . ";\n"
        );
    }
}
