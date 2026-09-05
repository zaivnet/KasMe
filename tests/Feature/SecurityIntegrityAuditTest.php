<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountBalanceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_combined_financial_ledger_reconciles_without_discrepancy(): void
    {
        $user = User::create(['name' => 'Audit', 'email' => 'audit@example.test', 'password' => 'SecurePassword123!']);
        $main = $user->accounts()->create(['name' => 'Utama', 'type' => 'bank', 'opening_balance' => '1000.00', 'currency' => 'IDR', 'is_active' => true]);
        $other = $user->accounts()->create(['name' => 'Tujuan', 'type' => 'cash', 'opening_balance' => '0.00', 'currency' => 'IDR', 'is_active' => true]);
        $income = $user->categories()->create(['name' => 'Gaji', 'type' => 'income', 'is_active' => true]);
        $expense = $user->categories()->create(['name' => 'Makan', 'type' => 'expense', 'is_active' => true]);

        $user->transactions()->create(['account_id' => $main->id, 'category_id' => $income->id, 'type' => 'income', 'amount' => '500.00', 'transaction_date' => '2026-08-11']);
        $user->transactions()->create(['account_id' => $main->id, 'category_id' => $expense->id, 'type' => 'expense', 'amount' => '100.00', 'transaction_date' => '2026-08-11']);
        $user->transactions()->create(['account_id' => $main->id, 'type' => 'adjustment', 'adjustment_direction' => 'increase', 'amount' => '20.00', 'transaction_date' => '2026-08-11']);
        $user->transactions()->create(['account_id' => $main->id, 'type' => 'adjustment', 'adjustment_direction' => 'decrease', 'amount' => '10.00', 'transaction_date' => '2026-08-11']);
        $user->transfers()->create(['from_account_id' => $main->id, 'to_account_id' => $other->id, 'amount' => '200.00', 'fee' => '5.00', 'transfer_date' => '2026-08-11']);

        $debt = $user->debts()->create(['type' => 'debt', 'person_name' => 'Pemberi pinjaman', 'original_amount' => '100.00', 'remaining_amount' => '50.00', 'start_date' => '2026-08-01', 'status' => 'active']);
        $debt->payments()->create(['account_id' => $main->id, 'amount' => '50.00', 'payment_date' => '2026-08-11']);
        $receivable = $user->debts()->create(['type' => 'receivable', 'person_name' => 'Peminjam', 'original_amount' => '100.00', 'remaining_amount' => '70.00', 'start_date' => '2026-08-01', 'status' => 'active']);
        $receivable->payments()->create(['account_id' => $main->id, 'amount' => '30.00', 'payment_date' => '2026-08-11']);

        $goal = $user->savingGoals()->create(['name' => 'Dana aman', 'target_amount' => '1000.00', 'status' => 'active']);
        $goal->transactions()->create(['account_id' => $main->id, 'type' => 'contribution', 'amount' => '80.00', 'transaction_date' => '2026-08-11']);
        $goal->transactions()->create(['account_id' => $main->id, 'type' => 'withdrawal', 'amount' => '20.00', 'transaction_date' => '2026-08-11']);

        $balances = app(AccountBalanceService::class)->calculateMany(new Collection([$main, $other]));

        $this->assertSame('1125.00', $balances[$main->id]);
        $this->assertSame('200.00', $balances[$other->id]);
    }

    public function test_private_storage_has_no_framework_file_serving_routes(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $this->assertFalse($routes->contains(fn ($route) => str_starts_with($route->uri(), 'storage/{path}')));
    }

    public function test_sprint_17_financial_regression_fixture_remains_exact(): void
    {
        $user = User::create(['name' => 'Regresi UI', 'email' => 'ui-regression@example.test', 'password' => 'SecurePassword123!']);
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
