<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

class ArchiveManager
{
    /**
     * Forced engine override for testing or manual configuration.
     */
    protected ?string $forcedEngine = null;

    /**
     * Custom CLI executor for testing.
     *
     * @var (callable(string $command, ?string $cwd): array{exit_code: int, stdout: string, stderr: string})|null
     */
    protected $cliExecutor = null;

    /**
     * Set a forced engine for testing ('ziparchive', 'cli_zip', 'unavailable', or null to auto-detect).
     */
    public function forceEngine(?string $engine): void
    {
        $this->forcedEngine = $engine;
    }

    /**
     * Set a custom CLI executor callable for testing.
     */
    public function setCliExecutor(?callable $executor): void
    {
        $this->cliExecutor = $executor;
    }

    /**
     * Detect the available archive engine according to priority:
     * 1. ziparchive (PHP ZipArchive extension)
     * 2. cli_zip (System zip / unzip CLI binaries)
     * 3. unavailable (Neither is functional)
     */
    public function archiveEngine(): string
    {
        if ($this->forcedEngine !== null) {
            return $this->forcedEngine;
        }

        if (class_exists(ZipArchive::class)) {
            return 'ziparchive';
        }

        if ($this->canUseCliZip()) {
            return 'cli_zip';
        }

        return 'unavailable';
    }

    /**
     * Determine if an archive engine is available.
     */
    public function isAvailable(): bool
    {
        return $this->archiveEngine() !== 'unavailable';
    }

    /**
     * Check if CLI zip and unzip binaries are available and process execution is enabled.
     */
    public function canUseCliZip(): bool
    {
        if (! function_exists('proc_open') && ! function_exists('exec')) {
            return false;
        }

        $disabled = explode(',', (string) ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);

        if (in_array('proc_open', $disabled, true) && in_array('exec', $disabled, true)) {
            return false;
        }

        // Test zip binary
        $zipRes = $this->runCommand('zip -v');
        if ($zipRes['exit_code'] !== 0) {
            $zipRes = $this->runCommand('zip -h');
            if ($zipRes['exit_code'] !== 0) {
                return false;
            }
        }

        // Test unzip binary
        $unzipRes = $this->runCommand('unzip -v');
        if ($unzipRes['exit_code'] !== 0) {
            $unzipRes = $this->runCommand('unzip -h');
            if ($unzipRes['exit_code'] !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a standard ZIP archive from all files and directories in $sourceDir.
     *
     * @param  string  $sourceDir  Staging directory containing archive payload
     * @param  string  $destinationZipPath  Target .zip file path
     * @return string Engine used ('ziparchive' | 'cli_zip')
     *
     * @throws RuntimeException
     */
    public function createArchive(string $sourceDir, string $destinationZipPath): string
    {
        if (! is_dir($sourceDir)) {
            throw new RuntimeException("Direktori sumber arsip tidak ditemukan: {$sourceDir}");
        }

        File::ensureDirectoryExists(dirname($destinationZipPath));

        $engine = $this->archiveEngine();

        try {
            if ($engine === 'ziparchive') {
                $this->createWithZipArchive($sourceDir, $destinationZipPath);

                return 'ziparchive';
            }

            if ($engine === 'cli_zip') {
                $this->createWithCliZip($sourceDir, $destinationZipPath);

                return 'cli_zip';
            }

            throw new RuntimeException('Server tidak memiliki ZipArchive maupun binary zip/unzip yang dapat digunakan.');
        } catch (Throwable $e) {
            if (file_exists($destinationZipPath)) {
                @unlink($destinationZipPath);
            }

            throw $e;
        }
    }

    /**
     * Create archive using PHP ZipArchive.
     */
    protected function createWithZipArchive(string $sourceDir, string $destinationZipPath): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Class "ZipArchive" not found.');
        }

        $zip = new ZipArchive();
        $res = $zip->open($destinationZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res !== true) {
            throw new RuntimeException("Gagal membuat arsip ZIP (ZipArchive error code: {$res})");
        }

        $realSourceDir = realpath($sourceDir);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($realSourceDir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();

        if (! file_exists($destinationZipPath) || filesize($destinationZipPath) === 0) {
            throw new RuntimeException('Pembuatan arsip ZIP menghasilkan berkas kosong atau tidak ditemukan.');
        }
    }

    /**
     * Create archive using CLI zip command.
     */
    protected function createWithCliZip(string $sourceDir, string $destinationZipPath): void
    {
        $items = scandir($sourceDir);
        $escapedArgs = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                continue;
            }
            $escapedArgs[] = escapeshellarg($item);
        }

        if (empty($escapedArgs)) {
            throw new RuntimeException('Tidak ada berkas di dalam staging directory untuk dicadangkan.');
        }

        $cmd = sprintf('zip -q -r %s %s', escapeshellarg($destinationZipPath), implode(' ', $escapedArgs));
        $res = $this->runCommand($cmd, $sourceDir);

        if ($res['exit_code'] !== 0) {
            $err = trim($res['stderr'] ?: $res['stdout']);
            throw new RuntimeException("CLI zip gagal membuat arsip (exit code {$res['exit_code']}): {$err}");
        }

        if (! file_exists($destinationZipPath) || filesize($destinationZipPath) === 0) {
            throw new RuntimeException('Pembuatan arsip CLI zip menghasilkan berkas kosong atau tidak ditemukan.');
        }
    }

    /**
     * Extract a ZIP archive safely into the destination directory.
     * All entries are validated against Zip Slip and forbidden paths prior to extraction.
     *
     * @throws RuntimeException
     */
    public function extractArchive(string $zipPath, string $destinationDir): void
    {
        if (! file_exists($zipPath) || ! is_readable($zipPath)) {
            throw new RuntimeException("Berkas arsip tidak ditemukan atau tidak dapat dibaca: {$zipPath}");
        }

        // 1. Audit all entries strictly before extracting anything
        $entries = $this->listEntries($zipPath);
        if (empty($entries)) {
            throw new RuntimeException('Arsip ZIP kosong atau rusak.');
        }

        foreach ($entries as $entry) {
            if ($this->isUnsafeEntryName($entry)) {
                throw new RuntimeException('Terdeteksi path tidak aman dalam arsip ZIP: ' . $entry);
            }
        }

        File::ensureDirectoryExists($destinationDir);

        $engine = $this->archiveEngine();

        if ($engine === 'ziparchive') {
            $this->extractWithZipArchive($zipPath, $destinationDir, $entries);
        } elseif ($engine === 'cli_zip') {
            $this->extractWithCliZip($zipPath, $destinationDir);
        } else {
            throw new RuntimeException('Server tidak memiliki ZipArchive maupun binary zip/unzip yang dapat digunakan.');
        }

        // 2. Post-extraction safety audit: ensure no symlinks were created
        $this->ensureNoSymlinks($destinationDir);
    }

    /**
     * Extract using ZipArchive.
     *
     * @param  array<string>  $entries
     */
    protected function extractWithZipArchive(string $zipPath, string $destinationDir, array $entries): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Class "ZipArchive" not found.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Gagal membuka arsip ZIP saat proses ekstraksi.');
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if ($this->isUnsafeEntryName($entryName)) {
                    throw new RuntimeException('Terdeteksi path tidak aman dalam arsip ZIP: ' . $entryName);
                }

                $targetPath = $destinationDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $entryName);

                if (str_ends_with($entryName, '/') || str_ends_with($entryName, '\\')) {
                    File::ensureDirectoryExists($targetPath);
                    continue;
                }

                File::ensureDirectoryExists(dirname($targetPath));

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    throw new RuntimeException('Gagal mengekstrak berkas: ' . $entryName);
                }

                File::put($targetPath, $content);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Extract using CLI unzip.
     */
    protected function extractWithCliZip(string $zipPath, string $destinationDir): void
    {
        $cmd = sprintf('unzip -q -o %s -d %s', escapeshellarg($zipPath), escapeshellarg($destinationDir));
        $res = $this->runCommand($cmd);

        if ($res['exit_code'] !== 0) {
            $err = trim($res['stderr'] ?: $res['stdout']);
            throw new RuntimeException("CLI unzip gagal mengekstrak arsip (exit code {$res['exit_code']}): {$err}");
        }
    }

    /**
     * List relative entry paths in a ZIP archive.
     *
     * @return array<string>
     */
    public function listEntries(string $zipPath): array
    {
        if (! file_exists($zipPath) || ! is_readable($zipPath)) {
            return [];
        }

        $engine = $this->archiveEngine();

        if ($engine === 'ziparchive' && class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return [];
            }

            $entries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entries[] = $zip->getNameIndex($i);
            }
            $zip->close();

            return $entries;
        }

        if ($engine === 'cli_zip') {
            $cmd = sprintf('unzip -Z -1 %s', escapeshellarg($zipPath));
            $res = $this->runCommand($cmd);

            if ($res['exit_code'] !== 0) {
                return [];
            }

            $lines = preg_split('/\r\n|\r|\n/', trim($res['stdout']));
            $entries = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $entries[] = $trimmed;
                }
            }

            return $entries;
        }

        return [];
    }

    /**
     * Read content of a single entry from a ZIP archive directly.
     */
    public function readEntry(string $zipPath, string $entryName): ?string
    {
        if (! file_exists($zipPath) || ! is_readable($zipPath)) {
            return null;
        }

        $engine = $this->archiveEngine();

        if ($engine === 'ziparchive' && class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                return null;
            }

            try {
                $content = $zip->getFromName($entryName);

                return $content === false ? null : $content;
            } finally {
                $zip->close();
            }
        }

        if ($engine === 'cli_zip') {
            $cmd = sprintf('unzip -p %s %s', escapeshellarg($zipPath), escapeshellarg($entryName));
            $res = $this->runCommand($cmd);

            if ($res['exit_code'] !== 0) {
                return null;
            }

            return $res['stdout'];
        }

        return null;
    }

    /**
     * Check if a zip entry path is unsafe (Zip Slip, drive letter, sensitive files, or outside permitted prefixes).
     */
    public function isUnsafeEntryName(string $name): bool
    {
        // Path traversal check
        if (str_contains($name, '..') ||
            str_starts_with($name, '/') ||
            str_starts_with($name, '\\') ||
            preg_match('/^[a-zA-Z]:/', $name)) {
            return true;
        }

        // Sensitive filename checks
        $basename = basename(str_replace('\\', '/', $name));
        if ($basename === '.env' || str_starts_with($basename, '.env.') || $basename === 'APP_KEY') {
            return true;
        }

        // Must strictly reside inside known top-level directories: manifest.json, database/, storage/private/
        $normalized = str_replace('\\', '/', $name);
        if ($normalized !== 'manifest.json' &&
            ! str_starts_with($normalized, 'database/') &&
            ! str_starts_with($normalized, 'storage/private/')) {
            return true;
        }

        return false;
    }

    /**
     * Ensure no extracted files or folders are symbolic links.
     */
    protected function ensureNoSymlinks(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_link($path)) {
                @unlink($path);
                throw new RuntimeException("Symlink terdeteksi dalam arsip yang diekstrak: {$item}");
            }

            if (is_dir($path)) {
                $this->ensureNoSymlinks($path);
            }
        }
    }

    /**
     * Execute a shell command safely.
     *
     * @return array{exit_code: int, stdout: string, stderr: string}
     */
    public function runCommand(string $command, ?string $cwd = null): array
    {
        if ($this->cliExecutor !== null) {
            return ($this->cliExecutor)($command, $cwd);
        }

        if (function_exists('proc_open')) {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = @proc_open($command, $descriptors, $pipes, $cwd);
            if (is_resource($process)) {
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                $exitCode = proc_close($process);

                return [
                    'exit_code' => $exitCode,
                    'stdout' => (string) $stdout,
                    'stderr' => (string) $stderr,
                ];
            }
        }

        // Fallback to exec if proc_open is unavailable or disabled
        $output = [];
        $exitCode = 1;
        $fullCmd = $cwd ? sprintf('cd %s && %s 2>&1', escapeshellarg($cwd), $command) : "{$command} 2>&1";
        @exec($fullCmd, $output, $exitCode);

        return [
            'exit_code' => $exitCode,
            'stdout' => implode("\n", $output),
            'stderr' => $exitCode === 0 ? '' : implode("\n", $output),
        ];
    }
}
