<?php

namespace App\Services\Backup;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use ZipArchive;

class BackupService
{
    public const BACKUP_DIR = 'app/private/backups';
    public const FORMAT_VERSION = 1;
    public const APPLICATION_NAME = 'KasMe';

    public function __construct(
        protected DatabaseDumper $databaseDumper
    ) {}

    /**
     * Get the absolute path to the backup storage directory.
     */
    public function getBackupDirectory(): string
    {
        $path = storage_path(self::BACKUP_DIR);
        File::ensureDirectoryExists($path);

        return $path;
    }

    /**
     * Create a full backup archive.
     *
     * @param  'manual'|'scheduled'|'pre_restore'  $type
     * @return string Filename of the created backup
     *
     * @throws Throwable
     */
    public function createBackup(string $type = 'manual', ?User $user = null): string
    {
        $backupDir = $this->getBackupDirectory();
        $timestamp = now()->format('Y-m-d-His');
        $randomSuffix = bin2hex(random_bytes(3));
        $filename = sprintf('kasme-backup-%s-%s-%s.zip', $timestamp, $type, $randomSuffix);
        $archivePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $tempDir = storage_path('app/private/temp_backup_' . $timestamp . '_' . $randomSuffix);
        File::ensureDirectoryExists($tempDir);

        try {
            // 1. Dump database
            $dbDumpPath = $tempDir . DIRECTORY_SEPARATOR . 'kasme.sql';
            $dumpResult = $this->databaseDumper->dump($dbDumpPath);

            // 2. Prepare payload files and calculate checksums
            $filesChecksum = [
                'database/kasme.sql' => $dumpResult['checksum'],
            ];

            // 3. Scan private files (e.g., attachments), strictly excluding backups and temp
            $privateStoragePath = storage_path('app/private');
            $privateFiles = $this->collectPrivateFiles($privateStoragePath);

            foreach ($privateFiles as $relativePath => $fullPath) {
                $filesChecksum['storage/private/' . $relativePath] = hash_file('sha256', $fullPath);
            }

            // 4. Generate manifest.json
            $manifest = [
                'application' => self::APPLICATION_NAME,
                'backup_format_version' => self::FORMAT_VERSION,
                'created_at' => now()->toIso8601String(),
                'type' => $type,
                'database_engine' => $dumpResult['engine'],
                'database_dump_method' => $dumpResult['method'],
                'includes_database' => true,
                'includes_private_files' => ! empty($privateFiles),
                'checksum_algorithm' => 'sha256',
                'files_checksum' => $filesChecksum,
                'app_version' => '1.0.0',
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'created_by_user_id' => $user?->id,
                'created_by_user_name' => $user?->name,
                'created_by_user_email' => $user?->email,
            ];

            $manifestPath = $tempDir . DIRECTORY_SEPARATOR . 'manifest.json';
            File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // 5. Build ZIP archive
            $zip = new ZipArchive();
            $res = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($res !== true) {
                throw new RuntimeException("Failed to create ZIP archive: code {$res}");
            }

            $zip->addFile($manifestPath, 'manifest.json');
            $zip->addFile($dbDumpPath, 'database/kasme.sql');

            foreach ($privateFiles as $relativePath => $fullPath) {
                $zip->addFile($fullPath, 'storage/private/' . $relativePath);
            }

            $zip->close();

            // Verify created archive
            if (! file_exists($archivePath) || filesize($archivePath) === 0) {
                throw new RuntimeException('Backup archive creation produced an empty or missing file');
            }

            // Update user last backup settings if user provided
            if ($user && $user->setting) {
                $user->setting->update([
                    'last_backup_at' => now(),
                    'last_backup_status' => 'success',
                ]);
            }

            // Log audit
            $this->logAudit('backup_create', $user?->id, 'success', $filename, $type);

            return $filename;
        } catch (Throwable $e) {
            if (file_exists($archivePath)) {
                @unlink($archivePath);
            }

            if ($user && $user->setting) {
                $user->setting->update([
                    'last_backup_status' => 'failed',
                ]);
            }

            $this->logAudit('backup_create', $user?->id, 'failed', $filename, $type, $e->getMessage());

            throw $e;
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Collect private files safely excluding backups and temp files.
     *
     * @return array<string, string> Map of relative path => absolute path
     */
    public function collectPrivateFiles(string $baseDir): array
    {
        $files = [];
        if (! File::isDirectory($baseDir)) {
            return $files;
        }

        $allFiles = File::allFiles($baseDir);

        foreach ($allFiles as $file) {
            $relativePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file->getRelativePathname());

            // Exclude backups, temp directories, audit logs, locks, and hidden files
            if (str_starts_with($relativePath, 'backups' . DIRECTORY_SEPARATOR) ||
                str_starts_with($relativePath, 'temp_') ||
                str_starts_with($relativePath, '.') ||
                $relativePath === 'backups' ||
                str_contains($relativePath, '.lock') ||
                str_contains($relativePath, 'audit.log')) {
                continue;
            }

            // Exclude any accidentally placed secrets
            $basename = $file->getFilename();
            if ($basename === '.env' || str_starts_with($basename, '.env.') || $basename === 'APP_KEY') {
                continue;
            }

            $files[str_replace('\\', '/', $relativePath)] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * List all backups in descending date order.
     *
     * @return array<array{
     *     filename: string,
     *     path: string,
     *     size: int,
     *     formatted_size: string,
     *     created_at: Carbon,
     *     type: string,
     *     status: string,
     *     manifest: ?array
     * }>
     */
    public function listBackups(): array
    {
        $dir = $this->getBackupDirectory();
        $files = File::glob($dir . DIRECTORY_SEPARATOR . 'kasme-backup-*.zip');
        $backups = [];

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $size = filesize($filePath);
            $createdAt = Carbon::createFromTimestamp(filemtime($filePath));
            $manifest = $this->readManifestFromZip($filePath);

            $type = $manifest['type'] ?? $this->inferTypeFromFilename($filename);

            $backups[] = [
                'filename' => $filename,
                'path' => $filePath,
                'size' => $size,
                'formatted_size' => $this->formatBytes($size),
                'created_at' => $createdAt,
                'type' => $type,
                'status' => 'available',
                'manifest' => $manifest,
            ];
        }

        // Sort by created_at descending
        usort($backups, fn ($a, $b) => $b['created_at']->timestamp <=> $a['created_at']->timestamp);

        return $backups;
    }

    /**
     * Read manifest.json directly from a ZIP archive.
     */
    public function readManifestFromZip(string $zipPath): ?array
    {
        if (! file_exists($zipPath) || ! is_readable($zipPath)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        try {
            $content = $zip->getFromName('manifest.json');
            if ($content === false) {
                return null;
            }

            return json_decode($content, true);
        } finally {
            $zip->close();
        }
    }

    /**
     * Infer backup type from filename if manifest is missing or damaged.
     */
    protected function inferTypeFromFilename(string $filename): string
    {
        if (str_contains($filename, '-pre_restore-')) {
            return 'pre_restore';
        }
        if (str_contains($filename, '-scheduled-')) {
            return 'scheduled';
        }

        return 'manual';
    }

    /**
     * Delete a single backup by filename safely.
     */
    public function deleteBackup(string $filename, ?User $user = null): bool
    {
        $this->validateFilename($filename);
        $path = $this->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (! file_exists($path)) {
            return false;
        }

        $deleted = @unlink($path);
        if ($deleted) {
            $this->logAudit('backup_delete', $user?->id, 'success', $filename);
        }

        return $deleted;
    }

    /**
     * Apply retention policy by deleting oldest backups beyond the retention limit.
     * Never deletes pre-restore backups or archives currently undergoing restore.
     *
     * @param  int  $retentionCount  0 means keep all
     * @return int Number of pruned backups
     */
    public function applyRetention(int $retentionCount, ?string $excludeFilename = null): int
    {
        if ($retentionCount <= 0) {
            return 0;
        }

        $backups = $this->listBackups();
        // Filter only manual and scheduled backups for retention pruning
        // Pre-restore backups are safety checkpoints and handled carefully
        $eligible = array_filter($backups, function ($b) use ($excludeFilename) {
            if ($excludeFilename && $b['filename'] === $excludeFilename) {
                return false;
            }
            if ($b['type'] === 'pre_restore') {
                return false;
            }

            return true;
        });

        $eligible = array_values($eligible);
        $prunedCount = 0;

        if (count($eligible) > $retentionCount) {
            $toDelete = array_slice($eligible, $retentionCount);
            foreach ($toDelete as $item) {
                if ($this->deleteBackup($item['filename'])) {
                    $prunedCount++;
                }
            }
        }

        return $prunedCount;
    }

    /**
     * Total storage size used by backup files.
     */
    public function getStorageUsage(): int
    {
        $total = 0;
        $files = File::glob($this->getBackupDirectory() . DIRECTORY_SEPARATOR . 'kasme-backup-*.zip');
        foreach ($files as $file) {
            $total += filesize($file);
        }

        return $total;
    }

    /**
     * Format bytes to human-readable size.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
    }

    /**
     * Validate that a filename contains only permitted characters and no path traversal.
     */
    public function validateFilename(string $filename): void
    {
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+\.zip$/', $filename) ||
            str_contains($filename, '..') ||
            str_contains($filename, '/') ||
            str_contains($filename, '\\')) {
            throw new RuntimeException('Invalid backup filename');
        }
    }

    /**
     * Log an administrative backup audit event.
     */
    public function logAudit(
        string $operation,
        ?int $userId,
        string $result,
        ?string $filename = null,
        ?string $source = null,
        ?string $error = null
    ): void {
        $entry = [
            'timestamp' => now()->toIso8601String(),
            'operation' => $operation,
            'user_id' => $userId,
            'result' => $result,
            'filename' => $filename,
            'source' => $source,
            'error' => $error ? substr(strip_tags($error), 0, 500) : null,
        ];

        $logPath = $this->getBackupDirectory() . DIRECTORY_SEPARATOR . 'audit.log';
        @file_put_contents($logPath, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);

        Log::info("Backup audit: [{$operation}] result={$result} file={$filename}", [
            'user_id' => $userId,
            'error' => $error,
        ]);
    }
}
