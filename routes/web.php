<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DebtPaymentController;
use App\Http\Controllers\PersonalDataExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingGoalController;
use App\Http\Controllers\SavingGoalTransactionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionExportController;
use App\Http\Controllers\TransferController;
use App\Http\Middleware\ApplyUserSettings;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.store');
});

Route::middleware(['auth', ApplyUserSettings::class])->group(function (): void {
    Route::redirect('/app', '/dashboard')->name('app.home');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/transactions/export', TransactionExportController::class)->name('transactions.export');
    Route::get('/transactions/{transaction}/attachment', [TransactionController::class, 'attachment'])->name('transactions.attachment');
    Route::resource('accounts', AccountController::class);
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('transactions', TransactionController::class);
    Route::resource('transfers', TransferController::class);
    Route::resource('budgets', BudgetController::class)->except('show');
    Route::resource('bills', BillController::class)->except('show');
    Route::resource('debts', DebtController::class);
    Route::resource('debts.payments', DebtPaymentController::class)->only(['store', 'edit', 'update', 'destroy'])->scoped();
    Route::resource('saving-goals', SavingGoalController::class);
    Route::resource('saving-goals.transactions', SavingGoalTransactionController::class)->only(['store', 'edit', 'update', 'destroy'])->scoped();
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/settings/export', PersonalDataExportController::class)->name('settings.export');
    Route::get('/settings/backups', [\App\Http\Controllers\BackupController::class, 'index'])->name('backups.index');
    Route::post('/settings/backups', [\App\Http\Controllers\BackupController::class, 'store'])->name('backups.store');
    Route::put('/settings/backups/schedule', [\App\Http\Controllers\BackupController::class, 'updateSchedule'])->name('backups.updateSchedule');
    Route::post('/settings/backups/upload', [\App\Http\Controllers\BackupController::class, 'upload'])->name('backups.upload');
    Route::get('/settings/backups/{filename}/download', [\App\Http\Controllers\BackupController::class, 'download'])->name('backups.download');
    Route::delete('/settings/backups/{filename}', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('backups.destroy');
    Route::get('/settings/backups/{filename}/restore', [\App\Http\Controllers\BackupController::class, 'restorePreview'])->name('backups.restorePreview');
    Route::post('/settings/backups/{filename}/restore', [\App\Http\Controllers\BackupController::class, 'restore'])->name('backups.restore');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
