<?php

namespace App\Services\Backup;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use ZipArchive;

class RestoreService
{
    public const SUPPORTED_VERSION = 1;
    public const REQUIRED_APP_NAME = 'KasMe';

    public function __construct(
        protected BackupService $backupService,
        protected DatabaseRestorer $databaseRestorer
    ) {}

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

        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath);
        if ($openResult !== true) {
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

        try {
            // Check for Zip Slip or prohibited entries
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);

                if ($this->isUnsafeEntryName($entryName)) {
                    $errors[] = 'Arsip mengandung path tidak aman (potensi Zip Slip atau penimpaan sistem): ' . $entryName;
                    break;
                }
            }

            // Check manifest
            $manifestRaw = $zip->getFromName('manifest.json');
            if ($manifestRaw === false) {
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
            $dbContent = $zip->getFromName('database/kasme.sql');
            if ($dbContent === false) {
                $errors[] = 'Arsip tidak memiliki berkas database/kasme.sql.';
            }

            // Verify checksums if available in manifest
            if ($manifest && ! empty($manifest['files_checksum']) && is_array($manifest['files_checksum'])) {
                foreach ($manifest['files_checksum'] as $filePath => $expectedHash) {
                    $fileContent = $zip->getFromName($filePath);
                    if ($fileContent === false) {
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
        } finally {
            $zip->close();
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

            // 4. Extract archive securely to staging directory
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
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Gagal membuka arsip ZIP saat proses ekstraksi.');
        }

        $realDestination = realpath($destination);
        if ($realDestination === false) {
            $realDestination = $destination;
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);

                if ($this->isUnsafeEntryName($entryName)) {
                    throw new RuntimeException('Terdeteksi path tidak aman dalam arsip ZIP: ' . $entryName);
                }

                $targetPath = $destination . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $entryName);

                // If entry is directory
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
