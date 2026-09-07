<?php

namespace App\Services\Backup;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class RestoreService
{
    public const SUPPORTED_VERSION = 1;
    public const REQUIRED_APP_NAME = 'KasMe';

    protected ArchiveManager $archiveManager;

    public function __construct(
        protected BackupService $backupService,
        protected DatabaseRestorer $databaseRestorer,
        ?ArchiveManager $archiveManager = null
    ) {
        $this->archiveManager = $archiveManager ?? app(ArchiveManager::class);
    }

    /**
     * Validate an archive and return its preview diagnostics.
     *
     * @return array{
     *     is_valid: bool,
     *     errors: array<string>,
     *     manifest: ?array,
     *     file_size: int,
     *     formatted_size: string,
     *     backup_date: ?string,
     *     database_engine: ?string,
     *     includes_private_files: bool,
     *     format_version: ?int,
     *     checksum_valid: bool
     * }
     */
    public function validateAndPreview(string $zipPath): array
    {
        $errors = [];
        $manifest = null;
        $fileSize = file_exists($zipPath) ? filesize($zipPath) : 0;
        $formattedSize = $this->backupService->formatBytes($fileSize);
        $checksumValid = true;

        if (! file_exists($zipPath) || ! is_readable($zipPath)) {
            return [
                'is_valid' => false,
                'errors' => ['Berkas backup tidak ditemukan atau tidak dapat dibaca.'],
                'manifest' => null,
                'file_size' => 0,
                'formatted_size' => '0 B',
                'backup_date' => null,
                'database_engine' => null,
                'includes_private_files' => false,
                'format_version' => null,
                'checksum_valid' => false,
            ];
        }

        $entries = $this->archiveManager->listEntries($zipPath);
        if (empty($entries)) {
            return [
                'is_valid' => false,
                'errors' => ['Arsip backup rusak atau bukan berkas ZIP yang valid.'],
                'manifest' => null,
                'file_size' => $fileSize,
                'formatted_size' => $formattedSize,
                'backup_date' => null,
                'database_engine' => null,
                'includes_private_files' => false,
                'format_version' => null,
                'checksum_valid' => false,
            ];
        }

        // Check for Zip Slip or prohibited entries
        foreach ($entries as $entryName) {
            if ($this->isUnsafeEntryName($entryName)) {
                $errors[] = 'Arsip mengandung path tidak aman (potensi Zip Slip atau penimpaan sistem): ' . $entryName;
                break;
            }
        }

        // Check manifest
        $manifestRaw = $this->archiveManager->readEntry($zipPath, 'manifest.json');
        if ($manifestRaw === null) {
            $errors[] = 'Arsip tidak memiliki berkas manifest.json.';
        } else {
            $manifest = json_decode($manifestRaw, true);
            if (! is_array($manifest)) {
                $errors[] = 'Format manifest.json tidak valid (bukan JSON yang dapat dibaca).';
            } else {
                if (($manifest['application'] ?? '') !== self::REQUIRED_APP_NAME) {
                    $errors[] = 'Aplikasi asal backup bukan ' . self::REQUIRED_APP_NAME . ' (terdeteksi: ' . ($manifest['application'] ?? 'kosong') . ').';
                }

                if (($manifest['backup_format_version'] ?? 0) > self::SUPPORTED_VERSION) {
                    $errors[] = 'Versi format backup (' . ($manifest['backup_format_version'] ?? '?') . ') tidak didukung oleh versi aplikasi ini.';
                }
            }
        }

        // Check database dump
        $hasDb = false;
        foreach ($entries as $entry) {
            if (str_replace('\\', '/', $entry) === 'database/kasme.sql') {
                $hasDb = true;
                break;
            }
        }

        if (! $hasDb) {
            $errors[] = 'Arsip tidak memiliki berkas database/kasme.sql.';
        }

        // Verify checksums if available in manifest
        if ($manifest && ! empty($manifest['files_checksum']) && is_array($manifest['files_checksum'])) {
            foreach ($manifest['files_checksum'] as $filePath => $expectedHash) {
                $fileContent = $this->archiveManager->readEntry($zipPath, $filePath);
                if ($fileContent === null) {
                    $errors[] = "Berkas terdaftar {$filePath} tidak ditemukan dalam arsip.";
                    $checksumValid = false;
                    break;
                }

                $actualHash = hash('sha256', $fileContent);
                if (! hash_equals($expectedHash, $actualHash)) {
                    $errors[] = "Integritas checksum tidak cocok untuk berkas {$filePath}.";
                    $checksumValid = false;
                    break;
                }
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'manifest' => $manifest,
            'file_size' => $fileSize,
            'formatted_size' => $formattedSize,
            'backup_date' => $manifest['created_at'] ?? null,
            'database_engine' => $manifest['database_engine'] ?? null,
            'includes_private_files' => (bool) ($manifest['includes_private_files'] ?? false),
            'format_version' => $manifest['backup_format_version'] ?? null,
            'checksum_valid' => $checksumValid,
        ];
    }

    /**
     * Check if a zip entry path contains path traversal, drive letters, or forbidden files.
     */
    public function isUnsafeEntryName(string $name): bool
    {
        return $this->archiveManager->isUnsafeEntryName($name);
    }

    /**
     * Perform the complete restore flow.
     *
     * @throws Throwable
     */
    public function restore(string $zipPath, ?User $user = null): void
    {
        // 1. Validate archive strictly
        $validation = $this->validateAndPreview($zipPath);
        if (! $validation['is_valid']) {
            throw new RuntimeException('Validasi backup gagal: ' . implode(' ', $validation['errors']));
        }

        $filename = basename($zipPath);
        $lockFile = storage_path('app/private/backups/.restore.lock');
        $preRestoreBackupFile = null;

        // 2. Set maintenance lock
        File::ensureDirectoryExists(dirname($lockFile));
        touch($lockFile);

        $tempStaging = storage_path('app/private/temp_restore_' . now()->format('YmdHis') . '_' . bin2hex(random_bytes(3)));
        File::ensureDirectoryExists($tempStaging);

        try {
            // 3. Create mandatory pre-restore backup
            try {
                $preRestoreBackupFile = $this->backupService->createBackup('pre_restore', $user);
            } catch (Throwable $e) {
                throw new RuntimeException('Gagal membuat backup otomatis sebelum restore (proses restore dibatalkan demi keamanan data): ' . $e->getMessage(), 0, $e);
            }

            // 4. Extract archive securely to staging directory via ArchiveManager
            $this->extractSafely($zipPath, $tempStaging);

            // 5. Restore database
            $dbSqlPath = $tempStaging . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'kasme.sql';
            if (! file_exists($dbSqlPath)) {
                throw new RuntimeException('Berkas SQL database tidak ditemukan pada direktori staging.');
            }

            $this->databaseRestorer->restore($dbSqlPath);

            // 6. Restore private files (excluding backups and locks)
            $stagingPrivate = $tempStaging . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private';
            if (File::isDirectory($stagingPrivate)) {
                $this->syncPrivateFiles($stagingPrivate, storage_path('app/private'));
            }

            // 7. Clear caches
            Artisan::call('optimize:clear');

            // 8. Log success audit
            $this->backupService->logAudit('restore', $user?->id, 'success', $filename, 'archive');
        } catch (Throwable $e) {
            // Log failure audit
            $this->backupService->logAudit('restore', $user?->id, 'failed', $filename, 'archive', $e->getMessage());

            // Attempt rollback if pre-restore backup exists
            if ($preRestoreBackupFile) {
                $preRestorePath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $preRestoreBackupFile;
                if (file_exists($preRestorePath)) {
                    $this->attemptRollback($preRestorePath, $tempStaging);
                }
            }

            throw $e;
        } finally {
            // Clean up staging
            if (File::exists($tempStaging)) {
                File::deleteDirectory($tempStaging);
            }

            // Release lock
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
        }
    }

    /**
     * Extract zip archive into staging directory with strict Zip Slip protection.
     */
    protected function extractSafely(string $zipPath, string $destination): void
    {
        $this->archiveManager->extractArchive($zipPath, $destination);
    }

    /**
     * Copy private files from staging to target storage, protecting backups directory and lock files.
     */
    protected function syncPrivateFiles(string $sourceDir, string $targetDir): void
    {
        $files = File::allFiles($sourceDir);

        foreach ($files as $file) {
            $relativePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file->getRelativePathname());

            // Safeguard against overwriting backups or lock files
            if (str_starts_with($relativePath, 'backups' . DIRECTORY_SEPARATOR) ||
                $relativePath === 'backups' ||
                str_contains($relativePath, '.lock') ||
                str_contains($relativePath, 'audit.log')) {
                continue;
            }

            $destination = $targetDir . DIRECTORY_SEPARATOR . $relativePath;
            File::ensureDirectoryExists(dirname($destination));
            File::copy($file->getRealPath(), $destination);
        }
    }

    /**
     * Attempt recovery from pre-restore backup if restoration failed mid-way.
     */
    protected function attemptRollback(string $preRestoreZipPath, string $stagingDir): void
    {
        try {
            Log::warning("Restore failed. Initiating automatic rollback using {$preRestoreZipPath}");

            $rollbackStaging = $stagingDir . '_rollback';
            File::ensureDirectoryExists($rollbackStaging);

            $this->extractSafely($preRestoreZipPath, $rollbackStaging);

            $dbSql = $rollbackStaging . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'kasme.sql';
            if (file_exists($dbSql)) {
                $this->databaseRestorer->restore($dbSql);
            }

            $stagingPrivate = $rollbackStaging . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private';
            if (File::isDirectory($stagingPrivate)) {
                $this->syncPrivateFiles($stagingPrivate, storage_path('app/private'));
            }

            Artisan::call('optimize:clear');
            Log::info('Automatic rollback succeeded.');
        } catch (Throwable $rollbackException) {
            Log::critical('Rollback after failed restore encountered an error: ' . $rollbackException->getMessage());
        }
    }
}
