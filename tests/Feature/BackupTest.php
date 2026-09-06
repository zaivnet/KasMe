<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountBalanceService;
use App\Services\Backup\BackupService;
use App\Services\Backup\RestoreService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Clean up test backup files from storage
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

        parent::tearDown();
    }

    public function test_authorized_user_can_access_backup_dashboard_and_create_backup(): void
    {
        $user = User::factory()->instanceOwner()->create();

        $response = $this->actingAs($user)->get(route('backups.index'));
        $response->assertOk();
        $response->assertSee('Backup & Restore');

        $storeResponse = $this->actingAs($user)->post(route('backups.store'));
        $storeResponse->assertRedirect(route('backups.index'));
        $storeResponse->assertSessionHas('success');

        $backupService = app(BackupService::class);
        $backups = $backupService->listBackups();

        $this->assertNotEmpty($backups);
        $this->assertSame('manual', $backups[0]['type']);
        $this->assertFileExists($backups[0]['path']);
    }

    public function test_unauthorized_guest_cannot_access_or_manage_backups(): void
    {
        $this->get(route('backups.index'))->assertRedirect(route('login'));
        $this->post(route('backups.store'))->assertRedirect(route('login'));
        $this->get(route('backups.download', 'test.zip'))->assertRedirect(route('login'));
        $this->delete(route('backups.destroy', 'test.zip'))->assertRedirect(route('login'));
    }

    public function test_backup_does_not_contain_env_app_key_or_database_credentials(): void
    {
        $user = User::factory()->create();
        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));

        // Scan all entries
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $this->assertFalse(str_contains($name, '.env'), "Archive contains .env: {$name}");
            $this->assertFalse(str_contains($name, 'APP_KEY'), "Archive contains APP_KEY: {$name}");
            $this->assertFalse(str_contains($name, 'storage/logs'), "Archive contains logs: {$name}");
            $this->assertFalse(str_contains($name, 'sessions'), "Archive contains sessions: {$name}");
            $this->assertFalse(str_contains($name, 'cache'), "Archive contains cache: {$name}");
        }

        // Check manifest content does not leak credentials
        $manifestContent = $zip->getFromName('manifest.json');
        $manifest = json_decode($manifestContent, true);
        $this->assertArrayNotHasKey('db_password', $manifest);
        $this->assertArrayNotHasKey('database_password', $manifest);
        $this->assertArrayNotHasKey('app_key', $manifest);

        $zip->close();
    }

    public function test_backup_contains_valid_manifest_and_database_payload(): void
    {
        $user = User::factory()->create();
        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));

        $manifestContent = $zip->getFromName('manifest.json');
        $this->assertNotFalse($manifestContent);
        $manifest = json_decode($manifestContent, true);

        $this->assertSame('KasMe', $manifest['application']);
        $this->assertSame(1, $manifest['backup_format_version']);
        $this->assertSame('manual', $manifest['type']);
        $this->assertTrue($manifest['includes_database']);

        $dbSql = $zip->getFromName('database/kasme.sql');
        $this->assertNotFalse($dbSql);
        $this->assertStringContainsString('users', $dbSql);

        $zip->close();
    }

    public function test_private_attachments_are_included_in_backup_archive(): void
    {
        $user = User::factory()->create();

        // Create a dummy private attachment in storage/app/private/attachments
        $attachmentDir = storage_path('app/private/attachments');
        File::ensureDirectoryExists($attachmentDir);
        $dummyReceipt = $attachmentDir . DIRECTORY_SEPARATOR . 'test-receipt.png';
        File::put($dummyReceipt, 'fake-png-content-data');

        try {
            $backupService = app(BackupService::class);
            $filename = $backupService->createBackup('manual', $user);
            $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

            $zip = new ZipArchive();
            $this->assertTrue($zip->open($zipPath));

            $entry = $zip->getFromName('storage/private/attachments/test-receipt.png');
            $this->assertSame('fake-png-content-data', $entry);

            $zip->close();
        } finally {
            @unlink($dummyReceipt);
        }
    }

    public function test_backup_download_is_authenticated_and_serves_valid_archive(): void
    {
        $user = User::factory()->instanceOwner()->create();
        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);

        $response = $this->actingAs($user)->get(route('backups.download', $filename));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
    }

    public function test_invalid_archive_is_rejected_by_restore_validation(): void
    {
        $user = User::factory()->create();
        $backupService = app(BackupService::class);
        $restoreService = app(RestoreService::class);

        // Create a corrupted zip file
        $corruptFile = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'kasme-backup-corrupt.zip';
        File::put($corruptFile, 'not-a-valid-zip-file-stream');

        $preview = $restoreService->validateAndPreview($corruptFile);
        $this->assertFalse($preview['is_valid']);
        $this->assertNotEmpty($preview['errors']);
    }

    public function test_wrong_application_manifest_is_rejected(): void
    {
        $backupService = app(BackupService::class);
        $restoreService = app(RestoreService::class);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'kasme-backup-wrong-app.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode([
            'application' => 'OtherFinanceApp',
            'backup_format_version' => 1,
            'includes_database' => true,
        ]));
        $zip->addFromString('database/kasme.sql', '-- dummy');
        $zip->close();

        $preview = $restoreService->validateAndPreview($zipPath);
        $this->assertFalse($preview['is_valid']);
        $this->assertStringContainsString('bukan KasMe', implode(' ', $preview['errors']));
    }

    public function test_unsupported_backup_version_is_rejected(): void
    {
        $backupService = app(BackupService::class);
        $restoreService = app(RestoreService::class);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'kasme-backup-future-ver.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode([
            'application' => 'KasMe',
            'backup_format_version' => 99,
            'includes_database' => true,
        ]));
        $zip->addFromString('database/kasme.sql', '-- dummy');
        $zip->close();

        $preview = $restoreService->validateAndPreview($zipPath);
        $this->assertFalse($preview['is_valid']);
        $this->assertStringContainsString('tidak didukung', implode(' ', $preview['errors']));
    }

    public function test_corrupt_checksum_is_rejected(): void
    {
        $backupService = app(BackupService::class);
        $restoreService = app(RestoreService::class);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'kasme-backup-bad-hash.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode([
            'application' => 'KasMe',
            'backup_format_version' => 1,
            'includes_database' => true,
            'files_checksum' => [
                'database/kasme.sql' => 'wrong_checksum_hash_value',
            ],
        ]));
        $zip->addFromString('database/kasme.sql', '-- valid sql content');
        $zip->close();

        $preview = $restoreService->validateAndPreview($zipPath);
        $this->assertFalse($preview['is_valid']);
        $this->assertFalse($preview['checksum_valid']);
        $this->assertStringContainsString('checksum tidak cocok', implode(' ', $preview['errors']));
    }

    public function test_zip_slip_archive_is_rejected(): void
    {
        $backupService = app(BackupService::class);
        $restoreService = app(RestoreService::class);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'kasme-backup-zip-slip.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode([
            'application' => 'KasMe',
            'backup_format_version' => 1,
            'includes_database' => true,
        ]));
        $zip->addFromString('../evil.php', '<?php echo "pwned";');
        $zip->addFromString('database/kasme.sql', '-- dummy');
        $zip->close();

        $preview = $restoreService->validateAndPreview($zipPath);
        $this->assertFalse($preview['is_valid']);
        $this->assertStringContainsString('tidak aman', implode(' ', $preview['errors']));
    }

    public function test_restore_requires_explicit_confirmation_text_restore(): void
    {
        $user = User::factory()->instanceOwner()->create();
        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);

        // Submitting with empty or wrong text fails validation
        $response = $this->actingAs($user)->post(route('backups.restore', $filename), [
            'confirm_restore' => 'YES',
        ]);
        $response->assertSessionHasErrors('confirm_restore');

        $responseEmpty = $this->actingAs($user)->post(route('backups.restore', $filename), [
            'confirm_restore' => '',
        ]);
        $responseEmpty->assertSessionHasErrors('confirm_restore');
    }

    public function test_valid_restore_replaces_database_state_and_restores_private_files(): void
    {
        $user = User::factory()->create(['name' => 'Original User Name']);
        $account = $user->accounts()->create([
            'name' => 'Original Account',
            'type' => 'bank',
            'opening_balance' => '5000000.00',
        ]);

        $privateDir = storage_path('app/private/attachments');
        File::ensureDirectoryExists($privateDir);
        $receipt = $privateDir . DIRECTORY_SEPARATOR . 'receipt-snapshot.txt';
        File::put($receipt, 'original-receipt-content');

        // Create backup of this original state
        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        // Now modify state in database and modify the file
        $user->update(['name' => 'Modified User Name']);
        $account->update(['name' => 'Modified Account Name']);
        File::put($receipt, 'tampered-receipt-content');

        $this->assertSame('Modified User Name', $user->fresh()->name);
        $this->assertSame('Modified Account Name', $account->fresh()->name);

        // Perform restore
        $restoreService = app(RestoreService::class);
        $restoreService->restore($zipPath, $user);

        // Verify state is restored to original
        $this->assertSame('Original User Name', $user->fresh()->name);
        $this->assertSame('Original Account', $account->fresh()->name);
        $this->assertSame('original-receipt-content', File::get($receipt));

        // Verify pre_restore backup was created
        $backups = $backupService->listBackups();
        $preRestoreBackups = array_filter($backups, fn ($b) => $b['type'] === 'pre_restore');
        $this->assertNotEmpty($preRestoreBackups);
    }

    public function test_backup_retention_prunes_only_eligible_managed_backups(): void
    {
        $user = User::factory()->create();
        $backupService = app(BackupService::class);

        // Create 5 manual backups
        $createdFiles = [];
        for ($i = 0; $i < 5; $i++) {
            $createdFiles[] = $backupService->createBackup('manual', $user);
            sleep(1); // ensure different timestamps
        }

        // Apply retention of 3
        $pruned = $backupService->applyRetention(3);
        $this->assertSame(2, $pruned);

        $remaining = $backupService->listBackups();
        $this->assertCount(3, $remaining);
    }

    public function test_scheduler_idempotency_prevents_duplicate_runs_in_same_window(): void
    {
        $user = User::factory()->create();
        $user->setting()->create([
            'backup_schedule_enabled' => true,
            'backup_schedule_frequency' => 'daily',
            'backup_schedule_time' => now()->format('H:i'),
            'backup_retention' => 7,
            'last_backup_at' => null,
        ]);

        $backupService = app(BackupService::class);

        // First run: should create backup
        Artisan::call('backup:scheduled');
        $backupsFirst = $backupService->listBackups();
        $this->assertCount(1, $backupsFirst);

        // Second run within same window: should be idempotent and NOT create another backup
        Artisan::call('backup:scheduled');
        $backupsSecond = $backupService->listBackups();
        $this->assertCount(1, $backupsSecond);
    }

    public function test_restore_aborts_if_pre_restore_backup_fails(): void
    {
        $user = User::factory()->create();
        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        // Mock BackupService to simulate failure during pre_restore backup creation
        $mockBackupService = $this->createMock(BackupService::class);
        $mockBackupService->method('formatBytes')->willReturn('1 KB');
        $mockBackupService->method('getBackupDirectory')->willReturn($backupService->getBackupDirectory());
        $mockBackupService->method('createBackup')
            ->willThrowException(new \RuntimeException('Disk storage full on pre-restore'));

        $restoreService = new RestoreService($mockBackupService, app(\App\Services\Backup\DatabaseRestorer::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gagal membuat backup otomatis sebelum restore');

        $restoreService->restore($zipPath, $user);
    }

    public function test_failed_restore_preserves_recovery_backup(): void
    {
        $user = User::factory()->create();
        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);
        $zipPath = $backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        // Mock DatabaseRestorer to simulate an unexpected error during restore execution
        $mockDbRestorer = $this->createMock(\App\Services\Backup\DatabaseRestorer::class);
        $mockDbRestorer->method('restore')
            ->willThrowException(new \RuntimeException('Simulated fatal SQL execution failure'));

        $restoreService = new RestoreService($backupService, $mockDbRestorer);

        try {
            $restoreService->restore($zipPath, $user);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated fatal SQL execution failure', $e->getMessage());
        }

        // Verify pre_restore backup exists in the backup directory
        $backups = $backupService->listBackups();
        $preRestoreBackups = array_filter($backups, fn ($b) => $b['type'] === 'pre_restore');
        $this->assertNotEmpty($preRestoreBackups, 'Pre-restore safety backup must be preserved after failure.');
    }

    public function test_backup_delete_requires_authenticated_user_and_valid_filename(): void
    {
        $user = User::factory()->instanceOwner()->create();
        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $user);

        // Path traversal in filename must be blocked
        $traversalResponse = $this->actingAs($user)->delete(route('backups.destroy', 'evil..traversal.zip'));
        $traversalResponse->assertRedirect(route('backups.index'));
        $traversalResponse->assertSessionHas('error');

        // Valid deletion by authenticated user succeeds
        $deleteResponse = $this->actingAs($user)->delete(route('backups.destroy', $filename));
        $deleteResponse->assertRedirect(route('backups.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertFileDoesNotExist($backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename);
    }

    public function test_non_owner_authenticated_user_is_forbidden_from_all_backup_operations(): void
    {
        $nonOwner = User::factory()->create(['is_instance_owner' => false]);
        $owner = User::factory()->instanceOwner()->create();

        $backupService = app(BackupService::class);
        $filename = $backupService->createBackup('manual', $owner);

        // Index
        $this->actingAs($nonOwner)->get(route('backups.index'))->assertForbidden();

        // Store
        $this->actingAs($nonOwner)->post(route('backups.store'))->assertForbidden();

        // Download
        $this->actingAs($nonOwner)->get(route('backups.download', $filename))->assertForbidden();

        // Update schedule
        $this->actingAs($nonOwner)->put(route('backups.updateSchedule'), [
            'backup_schedule_enabled' => true,
            'backup_schedule_frequency' => 'daily',
            'backup_schedule_time' => '03:00',
            'backup_retention' => 7,
        ])->assertForbidden();

        // Upload
        $this->actingAs($nonOwner)->post(route('backups.upload'), [])->assertForbidden();

        // Restore preview
        $this->actingAs($nonOwner)->get(route('backups.restorePreview', $filename))->assertForbidden();

        // Restore
        $this->actingAs($nonOwner)->post(route('backups.restore', $filename), [
            'confirm_restore' => 'RESTORE',
        ])->assertForbidden();

        // Destroy
        $this->actingAs($nonOwner)->delete(route('backups.destroy', $filename))->assertForbidden();
    }

    public function test_financial_regression_remains_exact_after_backup_feature(): void
    {
        $user = User::create(['name' => 'Regresi Sprint 19', 'email' => 'sprint19-regression@example.test', 'password' => 'SecurePassword123!']);
        $bank = $user->accounts()->create(['name' => 'Bank BCA', 'type' => 'bank', 'opening_balance' => '5000000.00', 'currency' => 'IDR', 'is_active' => true]);
        $cash = $user->accounts()->create(['name' => 'Cash', 'type' => 'cash', 'opening_balance' => '1000000.00', 'currency' => 'IDR', 'is_active' => true]);
        $income = $user->categories()->create(['name' => 'Pemasukan', 'type' => 'income', 'is_active' => true]);
        $expense = $user->categories()->create(['name' => 'Pengeluaran', 'type' => 'expense', 'is_active' => true]);
        $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $income->id, 'type' => 'income', 'amount' => '3000000.00', 'transaction_date' => '2026-08-11']);
        $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $expense->id, 'type' => 'expense', 'amount' => '250000.00', 'transaction_date' => '2026-08-11']);
        $user->transfers()->create(['from_account_id' => $bank->id, 'to_account_id' => $cash->id, 'amount' => '250000.00', 'fee' => '2500.00', 'transfer_date' => '2026-08-11']);

        $balances = app(AccountBalanceService::class)->calculateMany(new Collection([$bank, $cash]));

        $this->assertSame('7497500.00', $balances[$bank->id]);
        $this->assertSame('1250000.00', $balances[$cash->id]);
        $this->assertSame('8747500.00', bcadd($balances[$bank->id], $balances[$cash->id], 2));
        $this->assertSame('2747500.00', bcsub(bcsub('3000000.00', '250000.00', 2), '2500.00', 2));
    }
}
