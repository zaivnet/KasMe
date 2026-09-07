<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Backup\ArchiveManager;
use App\Services\Backup\BackupService;
use App\Services\Backup\RestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ArchiveManagerTest extends TestCase
{
    use RefreshDatabase;

    protected string $testTempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testTempDir = storage_path('app/private/test_archive_' . bin2hex(random_bytes(3)));
        File::ensureDirectoryExists($this->testTempDir);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testTempDir)) {
            File::deleteDirectory($this->testTempDir);
        }

        // Clean up any test backups
        $backupDir = storage_path('app/private/backups');
        if (File::isDirectory($backupDir)) {
            $files = File::glob($backupDir . DIRECTORY_SEPARATOR . 'kasme-backup-*.zip');
            foreach ($files as $file) {
                @unlink($file);
            }
            @unlink($backupDir . DIRECTORY_SEPARATOR . 'audit.log');
            @unlink($backupDir . DIRECTORY_SEPARATOR . '.restore.lock');
        }

        // Clean temp directories
        $tempDirs = File::glob(storage_path('app/private/temp_*'));
        foreach ($tempDirs as $dir) {
            File::deleteDirectory($dir);
        }

        // Reset any forced engine on singleton
        app(ArchiveManager::class)->forceEngine(null);
        app(ArchiveManager::class)->setCliExecutor(null);

        parent::tearDown();
    }

    /**
     * Helper to create a standard ZIP using ZipArchive for test fixtures.
     */
    protected function createZipFixture(string $sourceDir, string $destinationZip): void
    {
        $zip = new ZipArchive();
        $zip->open($destinationZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $realSource = realpath($sourceDir);
        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $rel = substr($filePath, strlen($realSource) + 1);
                $rel = str_replace('\\', '/', $rel);
                $zip->addFile($filePath, $rel);
            }
        }

        $zip->close();
    }

    public function test_archive_engine_defaults_to_ziparchive_when_class_exists(): void
    {
        $archiveManager = app(ArchiveManager::class);
        $archiveManager->forceEngine(null);

        $this->assertSame('ziparchive', $archiveManager->archiveEngine());
        $this->assertTrue($archiveManager->isAvailable());
    }

    public function test_fallback_engine_is_selected_when_ziparchive_is_unavailable(): void
    {
        $archiveManager = new ArchiveManager();
        $archiveManager->forceEngine('cli_zip');

        $this->assertSame('cli_zip', $archiveManager->archiveEngine());
        $this->assertTrue($archiveManager->isAvailable());
    }

    public function test_archive_creation_with_ziparchive_produces_valid_zip(): void
    {
        $source = $this->testTempDir . '/source';
        File::ensureDirectoryExists($source . '/database');
        File::ensureDirectoryExists($source . '/storage/private');

        File::put($source . '/manifest.json', json_encode(['application' => 'KasMe']));
        File::put($source . '/database/kasme.sql', '-- test sql dump');
        File::put($source . '/storage/private/doc.pdf', 'pdf-binary-content');

        $targetZip = $this->testTempDir . '/output_ziparchive.zip';

        $archiveManager = new ArchiveManager();
        $archiveManager->forceEngine('ziparchive');

        $engineUsed = $archiveManager->createArchive($source, $targetZip);

        $this->assertSame('ziparchive', $engineUsed);
        $this->assertFileExists($targetZip);
        $this->assertGreaterThan(0, filesize($targetZip));

        $entries = $archiveManager->listEntries($targetZip);
        $this->assertContains('manifest.json', $entries);
        $this->assertContains('database/kasme.sql', $entries);
        $this->assertContains('storage/private/doc.pdf', $entries);

        $manifestContent = $archiveManager->readEntry($targetZip, 'manifest.json');
        $this->assertNotNull($manifestContent);
        $this->assertStringContainsString('KasMe', $manifestContent);
    }

    public function test_archive_creation_with_cli_zip_fallback_produces_standard_zip(): void
    {
        $source = $this->testTempDir . '/source_cli';
        File::ensureDirectoryExists($source . '/database');
        File::ensureDirectoryExists($source . '/storage/private');

        File::put($source . '/manifest.json', json_encode(['application' => 'KasMe']));
        File::put($source . '/database/kasme.sql', '-- test sql dump');
        File::put($source . '/storage/private/receipt.png', 'png-image-data');

        $targetZip = $this->testTempDir . '/output_cli.zip';

        $archiveManager = new ArchiveManager();
        $archiveManager->forceEngine('cli_zip');

        $executedCommands = [];
        $archiveManager->setCliExecutor(function (string $cmd, ?string $cwd) use (&$executedCommands, $source, $targetZip) {
            $executedCommands[] = ['cmd' => $cmd, 'cwd' => $cwd];

            if (str_starts_with($cmd, 'zip -q -r')) {
                // Simulate zip command by creating standard ZIP archive
                $this->createZipFixture($source, $targetZip);

                return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
            }

            return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'unknown command'];
        });

        $engineUsed = $archiveManager->createArchive($source, $targetZip);

        $this->assertSame('cli_zip', $engineUsed);
        $this->assertFileExists($targetZip);
        $this->assertNotEmpty($executedCommands);
        $this->assertSame($source, $executedCommands[0]['cwd']);
        $this->assertStringContainsString('zip -q -r', $executedCommands[0]['cmd']);
    }

    public function test_backup_service_creates_archive_using_fallback_engine(): void
    {
        $user = User::factory()->instanceOwner()->create();

        // Attach simulated cli executor to ArchiveManager singleton
        $archiveManager = app(ArchiveManager::class);
        $archiveManager->forceEngine('cli_zip');

        $archiveManager->setCliExecutor(function (string $cmd, ?string $cwd) {
            if (str_starts_with($cmd, 'zip -q -r')) {
                // Parse destination zip from command: zip -q -r 'target.zip' ...
                if (preg_match("/zip -q -r '(.*?)'/", $cmd, $matches) || preg_match('/zip -q -r "(.*?)"/', $cmd, $matches)) {
                    $targetZip = $matches[1];
                    $this->createZipFixture($cwd, $targetZip);

                    return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
                }
            }

            if (str_starts_with($cmd, 'unzip -Z -1')) {
                if (preg_match("/unzip -Z -1 '(.*?)'/", $cmd, $matches) || preg_match('/unzip -Z -1 "(.*?)"/', $cmd, $matches)) {
                    $zip = new ZipArchive();
                    $zip->open($matches[1]);
                    $entries = [];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entries[] = $zip->getNameIndex($i);
                    }
                    $zip->close();

                    return ['exit_code' => 0, 'stdout' => implode("\n", $entries), 'stderr' => ''];
                }
            }

            if (str_starts_with($cmd, 'unzip -p')) {
                if (preg_match("/unzip -p '(.*?)' '(.*?)'/", $cmd, $matches) || preg_match('/unzip -p "(.*?)" "(.*?)"/', $cmd, $matches)) {
                    $zip = new ZipArchive();
                    $zip->open($matches[1]);
                    $content = $zip->getFromName($matches[2]);
                    $zip->close();

                    return $content !== false
                        ? ['exit_code' => 0, 'stdout' => $content, 'stderr' => '']
                        : ['exit_code' => 11, 'stdout' => '', 'stderr' => 'file not found'];
                }
            }

            return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
        });

        // Add dummy private attachment
        $attachmentDir = storage_path('app/private/attachments');
        File::ensureDirectoryExists($attachmentDir);
        $attachmentFile = $attachmentDir . '/receipt.jpg';
        File::put($attachmentFile, 'receipt-test-data');

        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        $this->assertFileExists($zipPath);

        // Verify manifest recorded archive_engine
        $manifest = $backupService->readManifestFromZip($zipPath);
        $this->assertNotNull($manifest);
        $this->assertSame('KasMe', $manifest['application']);
        $this->assertSame('cli_zip', $manifest['archive_engine']);
        $this->assertTrue($manifest['includes_database']);
        $this->assertTrue($manifest['includes_private_files']);

        // Verify entries
        $entries = $archiveManager->listEntries($zipPath);
        $this->assertContains('manifest.json', $entries);
        $this->assertContains('database/kasme.sql', $entries);
        $this->assertContains('storage/private/attachments/receipt.jpg', $entries);

        // Verify SHA-256 checksum recorded in manifest matches payload
        $sqlContent = $archiveManager->readEntry($zipPath, 'database/kasme.sql');
        $this->assertSame($manifest['files_checksum']['database/kasme.sql'], hash('sha256', $sqlContent));

        $receiptContent = $archiveManager->readEntry($zipPath, 'storage/private/attachments/receipt.jpg');
        $this->assertSame($manifest['files_checksum']['storage/private/attachments/receipt.jpg'], hash('sha256', $receiptContent));
    }

    public function test_restore_preview_and_execution_works_with_fallback_engine(): void
    {
        $user = User::factory()->instanceOwner()->create();

        $archiveManager = app(ArchiveManager::class);
        $archiveManager->forceEngine('cli_zip');

        $archiveManager->setCliExecutor(function (string $cmd, ?string $cwd) {
            if (str_starts_with($cmd, 'zip -q -r')) {
                if (preg_match("/zip -q -r '(.*?)'/", $cmd, $matches) || preg_match('/zip -q -r "(.*?)"/', $cmd, $matches)) {
                    $this->createZipFixture($cwd, $matches[1]);

                    return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
                }
            }

            if (str_starts_with($cmd, 'unzip -Z -1')) {
                if (preg_match("/unzip -Z -1 '(.*?)'/", $cmd, $matches) || preg_match('/unzip -Z -1 "(.*?)"/', $cmd, $matches)) {
                    $zip = new ZipArchive();
                    $zip->open($matches[1]);
                    $entries = [];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entries[] = $zip->getNameIndex($i);
                    }
                    $zip->close();

                    return ['exit_code' => 0, 'stdout' => implode("\n", $entries), 'stderr' => ''];
                }
            }

            if (str_starts_with($cmd, 'unzip -p')) {
                if (preg_match("/unzip -p '(.*?)' '(.*?)'/", $cmd, $matches) || preg_match('/unzip -p "(.*?)" "(.*?)"/', $cmd, $matches)) {
                    $zip = new ZipArchive();
                    $zip->open($matches[1]);
                    $content = $zip->getFromName($matches[2]);
                    $zip->close();

                    return $content !== false
                        ? ['exit_code' => 0, 'stdout' => $content, 'stderr' => '']
                        : ['exit_code' => 11, 'stdout' => '', 'stderr' => 'file not found'];
                }
            }

            if (str_starts_with($cmd, 'unzip -q -o')) {
                // Simulate extraction: unzip -q -o 'zipPath' -d 'destDir'
                if (preg_match("/unzip -q -o '(.*?)' -d '(.*?)'/", $cmd, $matches) || preg_match('/unzip -q -o "(.*?)" -d "(.*?)"/', $cmd, $matches)) {
                    $zip = new ZipArchive();
                    $zip->open($matches[1]);
                    $zip->extractTo($matches[2]);
                    $zip->close();

                    return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
                }
            }

            return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
        });

        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        $restoreService = app(RestoreService::class);
        $preview = $restoreService->validateAndPreview($zipPath);

        $this->assertTrue($preview['is_valid']);
        $this->assertEmpty($preview['errors']);
        $this->assertTrue($preview['checksum_valid']);
        $this->assertSame('KasMe', $preview['manifest']['application']);

        // Perform restore
        $restoreService->restore($zipPath, $user);

        // Verify restore succeeded
        $this->assertFileExists(storage_path('app/private/backups/audit.log'));
        $auditLog = file_get_contents(storage_path('app/private/backups/audit.log'));
        $this->assertStringContainsString('"operation":"restore"', $auditLog);
        $this->assertStringContainsString('"result":"success"', $auditLog);
    }

    public function test_malicious_archive_entry_path_is_rejected_prior_to_extraction(): void
    {
        $maliciousZip = $this->testTempDir . '/malicious.zip';

        // Create zip with Zip Slip path
        $zip = new ZipArchive();
        $zip->open($maliciousZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode(['application' => 'KasMe']));
        $zip->addFromString('database/kasme.sql', '-- dump');
        $zip->addFromString('../../etc/passwd', 'malicious-data');
        $zip->close();

        $destinationDir = $this->testTempDir . '/extracted';

        $archiveManager = new ArchiveManager();
        $archiveManager->forceEngine('ziparchive');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Terdeteksi path tidak aman');

        $archiveManager->extractArchive($maliciousZip, $destinationDir);

        // Verify nothing was extracted
        $this->assertDirectoryDoesNotExist($destinationDir);
    }

    public function test_partial_archive_cleaned_up_on_failure(): void
    {
        $source = $this->testTempDir . '/source_partial';
        File::ensureDirectoryExists($source);
        File::put($source . '/manifest.json', '{}');

        $targetZip = $this->testTempDir . '/partial.zip';

        $archiveManager = new ArchiveManager();
        $archiveManager->forceEngine('cli_zip');

        $archiveManager->setCliExecutor(function (string $cmd, ?string $cwd) use ($targetZip) {
            // Write partial file then exit with error
            file_put_contents($targetZip, 'partial-bytes');

            return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'simulated failure'];
        });

        try {
            $archiveManager->createArchive($source, $targetZip);
            $this->fail('Expected exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('CLI zip gagal', $e->getMessage());
        }

        // Verify partial zip was deleted in catch block
        $this->assertFileDoesNotExist($targetZip);
    }

    public function test_unsupported_environment_throws_clear_exception(): void
    {
        $source = $this->testTempDir . '/source_unsupported';
        File::ensureDirectoryExists($source);
        File::put($source . '/manifest.json', '{}');

        $targetZip = $this->testTempDir . '/unsupported.zip';

        $archiveManager = new ArchiveManager();
        $archiveManager->forceEngine('unavailable');

        $this->assertFalse($archiveManager->isAvailable());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Server tidak memiliki ZipArchive maupun binary zip/unzip yang dapat digunakan.');

        $archiveManager->createArchive($source, $targetZip);
    }

    public function test_backup_service_fails_gracefully_when_engine_unavailable(): void
    {
        $user = User::factory()->instanceOwner()->create();

        $archiveManager = app(ArchiveManager::class);
        $archiveManager->forceEngine('unavailable');

        $backupService = app(BackupService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Server tidak memiliki ZipArchive maupun binary zip/unzip yang dapat digunakan.');

        $backupService->createBackup('manual', $user);
    }
}
