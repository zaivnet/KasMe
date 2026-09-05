<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function user(string $email = 'report@example.test'): User
    {
        return User::create(['name' => 'Report User', 'email' => $email, 'password' => 'SecurePassword123!']);
    }

    private function account(User $user, string $name = 'Bank'): Account
    {
        return $user->accounts()->create(['name' => $name, 'type' => 'bank', 'opening_balance' => '0.00', 'currency' => 'IDR', 'is_active' => true]);
    }

    public function test_monthly_report_reconciles_ledger_and_transfer_fees(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'Asia/Jakarta'));
        $user = $this->user();
        $bank = $this->account($user);
        $cash = $this->account($user, 'Tunai');
        $salary = $user->categories()->create(['name' => 'Gaji', 'type' => 'income', 'is_active' => true]);
        $food = $user->categories()->create(['name' => 'Makanan', 'type' => 'expense', 'is_active' => true]);

        $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $salary->id, 'type' => 'income', 'amount' => '1000.00', 'transaction_date' => '2026-08-01']);
        $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $food->id, 'type' => 'expense', 'amount' => '250.00', 'transaction_date' => '2026-08-02']);
        $user->transactions()->create(['account_id' => $bank->id, 'type' => 'adjustment', 'adjustment_direction' => 'increase', 'amount' => '20.00', 'transaction_date' => '2026-08-03']);
        $deleted = $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $food->id, 'type' => 'expense', 'amount' => '999.00', 'transaction_date' => '2026-08-04']);
        $deleted->delete();
        $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $salary->id, 'type' => 'income', 'amount' => '500.00', 'transaction_date' => '2026-07-31']);
        $user->transfers()->create(['from_account_id' => $bank->id, 'to_account_id' => $cash->id, 'amount' => '300.00', 'fee' => '5.00', 'transfer_date' => '2026-08-05']);

        $this->actingAs($user)->get('/reports')->assertOk()
            ->assertViewHas('incomeTotal', '1000.00')
            ->assertViewHas('expenseTotal', '250.00')
            ->assertViewHas('transferFees', '5.00')
            ->assertViewHas('netCashFlow', '745.00')
            ->assertViewHas('adjustmentIncrease', '20.00')
            ->assertViewHas('categoryBreakdown', fn ($rows) => count($rows) === 2)
            ->assertViewHas('accountBreakdown', fn ($rows) => count($rows) === 1)
            ->assertSee('Makanan')->assertDontSee('999.00');
    }

    public function test_custom_range_and_account_category_type_filters_are_applied(): void
    {
        $user = $this->user();
        $bank = $this->account($user);
        $cash = $this->account($user, 'Tunai');
        $food = $user->categories()->create(['name' => 'Makanan', 'type' => 'expense', 'is_active' => true]);
        $travel = $user->categories()->create(['name' => 'Perjalanan', 'type' => 'expense', 'is_active' => true]);
        $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $food->id, 'type' => 'expense', 'amount' => '100.00', 'transaction_date' => '2026-01-10']);
        $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $travel->id, 'type' => 'expense', 'amount' => '200.00', 'transaction_date' => '2026-01-10']);
        $user->transactions()->create(['account_id' => $cash->id, 'category_id' => $food->id, 'type' => 'expense', 'amount' => '300.00', 'transaction_date' => '2026-01-10']);
        $user->transactions()->create(['account_id' => $bank->id, 'category_id' => $food->id, 'type' => 'expense', 'amount' => '400.00', 'transaction_date' => '2026-02-01']);

        $url = "/reports?period=custom&date_from=2026-01-01&date_to=2026-01-31&account_id={$bank->id}&category_id={$food->id}&type=expense";
        $this->actingAs($user)->get($url)->assertOk()
            ->assertViewHas('incomeTotal', '0.00')->assertViewHas('expenseTotal', '100.00')
            ->assertViewHas('transferFees', '0.00')
            ->assertViewHas('transactions', fn ($transactions) => $transactions->total() === 1);
    }

    public function test_report_is_authenticated_user_scoped_and_rejects_foreign_filters(): void
    {
        $user = $this->user();
        $other = $this->user('other-report@example.test');
        $foreignAccount = $this->account($other, 'Rahasia');
        $foreignCategory = $other->categories()->create(['name' => 'Pribadi', 'type' => 'expense', 'is_active' => true]);
        $other->transactions()->create(['account_id' => $foreignAccount->id, 'category_id' => $foreignCategory->id, 'type' => 'expense', 'amount' => '9999.00', 'transaction_date' => now()->toDateString()]);

        $this->get('/reports')->assertRedirect('/login');
        $this->actingAs($user)->get('/reports')->assertOk()->assertDontSee('Rahasia')->assertDontSee('9,999.00');
        $this->actingAs($user)->get("/reports?account_id={$foreignAccount->id}")->assertSessionHasErrors('account_id');
        $this->actingAs($user)->get("/reports?category_id={$foreignCategory->id}")->assertSessionHasErrors('category_id');
    }

    public function test_custom_range_validation_and_ledger_pagination_work(): void
    {
        $user = $this->user();
        $account = $this->account($user);
        $category = $user->categories()->create(['name' => 'Makanan', 'type' => 'expense', 'is_active' => true]);
        foreach (range(1, 21) as $day) {
            $user->transactions()->create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => '1.00', 'transaction_date' => sprintf('2026-01-%02d', $day)]);
        }

        $this->actingAs($user)->get('/reports?period=custom')->assertSessionHasErrors(['date_from', 'date_to']);
        $this->actingAs($user)->get('/reports?period=custom&date_from=2026-02-01&date_to=2026-01-01')->assertSessionHasErrors('date_to');
        $this->actingAs($user)->get('/reports?period=custom&date_from=2026-01-01&date_to=2026-01-31')->assertOk()
            ->assertViewHas('transactions', fn ($transactions) => $transactions->total() === 21 && $transactions->perPage() === 20);
    }
}
