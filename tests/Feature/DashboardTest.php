<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function user(string $email = 'dashboard@example.test'): User
    {
        return User::create(['name' => 'Dashboard User', 'email' => $email, 'password' => 'SecurePassword123!']);
    }

    private function account(User $user, string $name, string $opening): Account
    {
        return $user->accounts()->create(['name' => $name, 'type' => 'bank', 'opening_balance' => $opening, 'currency' => 'IDR', 'is_active' => true]);
    }

    public function test_empty_dashboard_shows_zero_and_empty_chart_data(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('totalBalance', '0.00')->assertViewHas('income', '0.00')
            ->assertViewHas('expense', '0.00')->assertViewHas('netCashFlow', '0.00')
            ->assertViewHas('cashFlowChart', fn ($chart) => $chart['labels'] === [])
            ->assertSee('Belum ada akun.')->assertSee('Belum ada transaksi.');
    }

    public function test_dashboard_metrics_reconcile_current_month_real_ledger(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15 12:00:00', 'Asia/Jakarta'));
        $user = $this->user();
        $source = $this->account($user, 'Source', '1000.00');
        $destination = $this->account($user, 'Destination', '200.00');
        $incomeCategory = $user->categories()->create(['name' => 'Salary', 'type' => 'income', 'is_active' => true]);
        $expenseCategory = $user->categories()->create(['name' => 'Food', 'type' => 'expense', 'is_active' => true]);

        $user->transactions()->create(['account_id' => $source->id, 'category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => '500.00', 'transaction_date' => '2026-08-02']);
        $user->transactions()->create(['account_id' => $source->id, 'category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => '200.00', 'transaction_date' => '2026-08-03']);
        $user->transactions()->create(['account_id' => $source->id, 'category_id' => null, 'type' => 'adjustment', 'adjustment_direction' => 'increase', 'amount' => '50.00', 'transaction_date' => '2026-08-04']);
        $user->transactions()->create(['account_id' => $source->id, 'category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => '999.00', 'transaction_date' => '2026-07-31']);
        $user->transfers()->create(['from_account_id' => $source->id, 'to_account_id' => $destination->id, 'amount' => '300.00', 'fee' => '5.00', 'transfer_date' => '2026-08-05']);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('totalBalance', '2544.00')
            ->assertViewHas('income', '500.00')
            ->assertViewHas('expense', '200.00')
            ->assertViewHas('fees', '5.00')
            ->assertViewHas('netCashFlow', '295.00')
            ->assertViewHas('cashFlowChart', fn ($chart) => $chart['income'] === [500.0, 0.0] && $chart['expense'] === [0.0, 200.0])
            ->assertViewHas('categoryChart', fn ($chart) => $chart['labels'] === ['Food'] && $chart['values'] === [200.0]);
    }

    public function test_dashboard_is_scoped_to_authenticated_user(): void
    {
        $user = $this->user();
        $other = $this->user('other-dashboard@example.test');
        $this->account($other, 'Private Account', '999999.00');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('totalBalance', '0.00')->assertDontSee('Private Account');
    }
}
