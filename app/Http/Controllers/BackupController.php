<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Backup\BackupService;
use App\Services\Backup\RestoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected RestoreService $restoreService
    ) {}

    /**
     * Display backup dashboard, history, and configuration.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-system-backups');

        $user = $request->user();
        $setting = $user->setting ?? new Setting();

        $backups = $this->backupService->listBackups();
        $storageBytes = $this->backupService->getStorageUsage();
        $formattedUsage = $this->backupService->formatBytes($storageBytes);

        return view('settings.backups.index', [
            'backups' => $backups,
            'storageUsage' => $formattedUsage,
            'storageBytes' => $storageBytes,
            'setting' => $setting,
            'frequencies' => Setting::BACKUP_FREQUENCIES,
            'retentions' => Setting::BACKUP_RETENTIONS,
        ]);
    }

    /**
     * Create a manual backup.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-system-backups');

        try {
            $filename = $this->backupService->createBackup('manual', $request->user());

            return redirect()->route('backups.index')->with('success', "Cadangan data berhasil dibuat: {$filename}");
        } catch (Throwable $e) {
            return redirect()->route('backups.index')->with('error', 'Gagal membuat cadangan data: ' . $e->getMessage());
        }
    }

    /**
     * Securely download a backup file.
     */
    public function download(Request $request, string $filename): BinaryFileResponse
    {
        Gate::authorize('manage-system-backups');

        $this->backupService->validateFilename($filename);
        $path = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        abort_unless(file_exists($path), 404, 'Berkas cadangan tidak ditemukan.');

        $this->backupService->logAudit('download', $request->user()->id, 'success', $filename);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Delete an existing backup file.
     */
    public function destroy(Request $request, string $filename): RedirectResponse
    {
        Gate::authorize('manage-system-backups');

        try {
            $this->backupService->validateFilename($filename);
            $deleted = $this->backupService->deleteBackup($filename, $request->user());

            if ($deleted) {
                return redirect()->route('backups.index')->with('success', 'Berkas cadangan berhasil dihapus.');
            }

            return redirect()->route('backups.index')->with('error', 'Berkas cadangan tidak ditemukan.');
        } catch (Throwable $e) {
            return redirect()->route('backups.index')->with('error', 'Gagal menghapus berkas cadangan: ' . $e->getMessage());
        }
    }

    /**
     * Update scheduled backup preferences.
     */
    public function updateSchedule(Request $request): RedirectResponse
    {
        Gate::authorize('manage-system-backups');

        $validated = $request->validate([
            'backup_schedule_enabled' => ['required', 'boolean'],
            'backup_schedule_frequency' => ['required', Rule::in(array_keys(Setting::BACKUP_FREQUENCIES))],
            'backup_schedule_time' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'backup_retention' => ['required', 'integer', Rule::in(array_keys(Setting::BACKUP_RETENTIONS))],
        ], [
            'backup_schedule_time.regex' => 'Format waktu harus berupa JJ:MM (contoh: 02:00).',
        ]);

        $request->user()->setting()->updateOrCreate([], $validated);

        return redirect()->route('backups.index')->with('success', 'Pengaturan pencadangan otomatis berhasil diperbarui.');
    }

    /**
     * Upload an existing backup archive.
     */
    public function upload(Request $request): RedirectResponse
    {
        Gate::authorize('manage-system-backups');

        $request->validate([
            'backup_file' => ['required', 'file', 'max:102400', 'mimes:zip'],
        ], [
            'backup_file.required' => 'Pilih berkas arsip backup yang ingin diunggah.',
            'backup_file.mimes' => 'Berkas harus berupa arsip ZIP yang valid.',
            'backup_file.max' => 'Ukuran berkas tidak boleh melebihi 100 MB.',
        ]);

        $uploadedFile = $request->file('backup_file');
        $tempPath = $uploadedFile->getRealPath();

        $preview = $this->restoreService->validateAndPreview($tempPath);
        if (! $preview['is_valid']) {
            return redirect()->route('backups.index')
                ->withErrors(['backup_file' => 'Validasi arsip gagal: ' . implode(' ', $preview['errors'])]);
        }

        $timestamp = now()->format('Y-m-d-His');
        $filename = sprintf('kasme-backup-%s-upload-%s.zip', $timestamp, bin2hex(random_bytes(3)));
        $destination = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        $uploadedFile->move($this->backupService->getBackupDirectory(), $filename);

        $this->backupService->logAudit('upload', $request->user()->id, 'success', $filename);

        return redirect()->route('backups.restorePreview', $filename)
            ->with('success', 'Berkas cadangan berhasil diunggah dan terverifikasi. Silakan tinjau informasi sebelum melakukan pemulihan.');
    }

    /**
     * Display the restore preview and explicit confirmation screen.
     */
    public function restorePreview(Request $request, string $filename): View|RedirectResponse
    {
        Gate::authorize('manage-system-backups');

        try {
            $this->backupService->validateFilename($filename);
        } catch (Throwable) {
            abort(404, 'Nama berkas cadangan tidak valid.');
        }

        $path = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
        if (! file_exists($path)) {
            abort(404, 'Berkas cadangan tidak ditemukan.');
        }

        $preview = $this->restoreService->validateAndPreview($path);

        return view('settings.backups.restore-preview', [
            'filename' => $filename,
            'preview' => $preview,
        ]);
    }

    /**
     * Perform destructive restore with explicit confirmation and pre-restore backup.
     */
    public function restore(Request $request, string $filename): RedirectResponse
    {
        Gate::authorize('manage-system-backups');

        $this->backupService->validateFilename($filename);
        $path = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (! file_exists($path)) {
            return redirect()->route('backups.index')->with('error', 'Berkas cadangan tidak ditemukan.');
        }

        $request->validate([
            'confirm_restore' => ['required', 'string', 'in:RESTORE'],
        ], [
            'confirm_restore.in' => 'Konfirmasi tidak sesuai. Anda harus mengetik RESTORE dengan huruf kapital.',
            'confirm_restore.required' => 'Ketik RESTORE untuk mengonfirmasi pemulihan data.',
        ]);

        try {
            $this->restoreService->restore($path, $request->user());

            return redirect()->route('backups.index')->with('success', 'Pemulihan data berhasil diselesaikan! Seluruh data keuangan dan lampiran telah dipulihkan dari cadangan.');
        } catch (Throwable $e) {
            return redirect()->route('backups.restorePreview', $filename)
                ->with('error', 'Pemulihan data gagal: ' . $e->getMessage() . ' (Sistem telah mempertahankan data dan mencatat status kegagalan).');
        }
    }
}
