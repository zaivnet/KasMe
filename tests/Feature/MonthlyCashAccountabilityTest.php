<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Debt;
use App\Models\MonthlyCashSettlement;
use App\Models\SavingGoal;
use App\Models\User;
use App\Services\MonthlyCashService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyCashAccountabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function createUser(string $email = 'user@example.test', ?string $baseline = '2020-01-01'): User
    {
        $user = User::create([
            'name' => 'Accountability User',
            'email' => $email,
            'password' => 'SecurePassword123!',
        ]);

        if ($baseline !== null) {
            $user->setting()->updateOrCreate([], [
                'cash_accountability_start_period' => $baseline,
            ]);
        }

        return $user->fresh('setting');
    }

    private function createAccount(User $user, string $name = 'Main Cash', string $opening = '0.00'): Account
    {
        return $user->accounts()->create([
            'name' => $name,
            'type' => 'cash',
            'opening_balance' => $opening,
            'currency' => 'IDR',
            'is_active' => true,
        ]);
    }

    public function test_01_current_month_period_cash_calculation(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user, 'Kas Utama', '1000000.00'); // historical opening
        $incomeCat = $user->categories()->create(['name' => 'Gaji', 'type' => 'income', 'is_active' => true]);
        $expenseCat = $user->categories()->create(['name' => 'Makan', 'type' => 'expense', 'is_active' => true]);

        // Current month mutations
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $incomeCat->id,
            'type' => 'income',
            'amount' => '500000.00',
            'transaction_date' => '2026-10-05',
        ]);
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCat->id,
            'type' => 'expense',
            'amount' => '200000.00',
            'transaction_date' => '2026-10-10',
        ]);

        /** @var MonthlyCashService $service */
        $service = app(MonthlyCashService::class);
        $currentCash = $service->calculatePeriodCash($user, '2026-10');

        // Opening balance must NOT be included in period cash
        $this->assertSame('300000.00', $currentCash);
    }

    public function test_02_previous_month_cash_calculation(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user, 'Kas', '5000000.00');
        $incomeCat = $user->categories()->create(['name' => 'Proyek', 'type' => 'income', 'is_active' => true]);
        $expenseCat = $user->categories()->create(['name' => 'Operasional', 'type' => 'expense', 'is_active' => true]);

        // September transactions
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $incomeCat->id,
            'type' => 'income',
            'amount' => '8450000.00',
            'transaction_date' => '2026-09-10',
        ]);
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCat->id,
            'type' => 'expense',
            'amount' => '1450000.00',
            'transaction_date' => '2026-09-20',
        ]);

        /** @var MonthlyCashService $service */
        $service = app(MonthlyCashService::class);
        $prevCash = $service->calculatePeriodCash($user, '2026-09');

        $this->assertSame('7000000.00', $prevCash);
    }

    public function test_03_month_boundary_strictness(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-01 00:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'General', 'type' => 'income', 'is_active' => true]);

        // Transaction on 2026-08-31 23:59:59 (outside Sept)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '100.00',
            'transaction_date' => '2026-08-31',
        ]);
        // Transaction on 2026-09-01 (inside Sept)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '200.00',
            'transaction_date' => '2026-09-01',
        ]);
        // Transaction on 2026-09-30 (inside Sept)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '300.00',
            'transaction_date' => '2026-09-30',
        ]);
        // Transaction on 2026-10-01 (outside Sept)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '400.00',
            'transaction_date' => '2026-10-01',
        ]);

        $service = app(MonthlyCashService::class);
        $septCash = $service->calculatePeriodCash($user, '2026-09');

        // Only 200 + 300 = 500
        $this->assertSame('500.00', $septCash);
    }

    public function test_04_no_previous_data_returns_zero(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $service = app(MonthlyCashService::class);

        $summary = $service->summaryForDashboard($user);

        $this->assertSame('0.00', $summary['previousPeriodCash']);
        $this->assertFalse($summary['hasPreviousPeriodData']);
        $this->assertNull($summary['previousSettlement']);
        $this->assertFalse($summary['isPreviousPeriodSettled']);
    }

    public function test_05_and_06_income_and_expense_semantics(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $inCat = $user->categories()->create(['name' => 'Income Cat', 'type' => 'income', 'is_active' => true]);
        $exCat = $user->categories()->create(['name' => 'Expense Cat', 'type' => 'expense', 'is_active' => true]);

        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '1000.00',
            'transaction_date' => '2026-10-02',
        ]);
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $exCat->id,
            'type' => 'expense',
            'amount' => '350.00',
            'transaction_date' => '2026-10-03',
        ]);

        $service = app(MonthlyCashService::class);
        $details = $service->calculatePeriodDetails($user, '2026-10');

        $this->assertSame('1000.00', $details['income']);
        $this->assertSame('350.00', $details['expense']);
        $this->assertSame('650.00', $details['period_cash']);
    }

    public function test_07_adjustment_increase_and_decrease(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $user->transactions()->create([
            'account_id' => $account->id,
            'type' => 'adjustment',
            'adjustment_direction' => 'increase',
            'amount' => '150.00',
            'transaction_date' => '2026-10-05',
        ]);
        $user->transactions()->create([
            'account_id' => $account->id,
            'type' => 'adjustment',
            'adjustment_direction' => 'decrease',
            'amount' => '50.00',
            'transaction_date' => '2026-10-06',
        ]);

        $service = app(MonthlyCashService::class);
        $details = $service->calculatePeriodDetails($user, '2026-10');

        $this->assertSame('150.00', $details['adjustments_increase']);
        $this->assertSame('50.00', $details['adjustments_decrease']);
        // +150 - 50 = +100
        $this->assertSame('100.00', $details['period_cash']);
    }

    public function test_08_and_09_transfer_principal_cancels_and_fee_deducted(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $accA = $this->createAccount($user, 'Dompet A', '1000.00');
        $accB = $this->createAccount($user, 'Dompet B', '1000.00');

        $user->transfers()->create([
            'from_account_id' => $accA->id,
            'to_account_id' => $accB->id,
            'amount' => '500.00',
            'fee' => '6.50',
            'transfer_date' => '2026-10-08',
        ]);

        $service = app(MonthlyCashService::class);
        $details = $service->calculatePeriodDetails($user, '2026-10');

        // Transfer principal cancels out; only fee affects period cash
        $this->assertSame('6.50', $details['transfer_fees']);
        $this->assertSame('-6.50', $details['period_cash']);
    }

    public function test_10_debt_and_receivable_payments(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);

        // Debt (we owe someone money) -> payment means cash outflow
        $debt = $user->debts()->create([
            'type' => 'debt',
            'person_name' => 'Lender',
            'original_amount' => '1000.00',
            'remaining_amount' => '700.00',
            'start_date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'status' => 'active',
        ]);
        $debt->payments()->create([
            'account_id' => $account->id,
            'amount' => '300.00',
            'payment_date' => '2026-10-09',
        ]);

        // Receivable (someone owes us money) -> payment received means cash inflow
        $receivable = $user->debts()->create([
            'type' => 'receivable',
            'person_name' => 'Borrower',
            'original_amount' => '500.00',
            'remaining_amount' => '100.00',
            'start_date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'status' => 'active',
        ]);
        $receivable->payments()->create([
            'account_id' => $account->id,
            'amount' => '400.00',
            'payment_date' => '2026-10-10',
        ]);

        $service = app(MonthlyCashService::class);
        $details = $service->calculatePeriodDetails($user, '2026-10');

        // Cash net: -300 + 400 = +100
        $this->assertSame('300.00', $details['debt_payments']);
        $this->assertSame('400.00', $details['receivable_payments']);
        $this->assertSame('100.00', $details['period_cash']);
    }

    public function test_11_saving_goal_contributions_and_withdrawals(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $goal = $user->savingGoals()->create([
            'name' => 'Emergency Fund',
            'target_amount' => '10000.00',
            'target_date' => '2027-01-01',
            'status' => 'active',
        ]);

        // Contribution: money moved from account to goal (outflow from liquid cash)
        $goal->transactions()->create([
            'account_id' => $account->id,
            'type' => 'contribution',
            'amount' => '250.00',
            'transaction_date' => '2026-10-04',
        ]);
        // Withdrawal: money moved back to account (inflow to liquid cash)
        $goal->transactions()->create([
            'account_id' => $account->id,
            'type' => 'withdrawal',
            'amount' => '100.00',
            'transaction_date' => '2026-10-12',
        ]);

        $service = app(MonthlyCashService::class);
        $details = $service->calculatePeriodDetails($user, '2026-10');

        $this->assertSame('250.00', $details['saving_contributions']);
        $this->assertSame('100.00', $details['saving_withdrawals']);
        // -250 + 100 = -150
        $this->assertSame('-150.00', $details['period_cash']);
    }

    public function test_12_and_13_settlement_creation_and_snapshot_preservation(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 09:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $inCat = $user->categories()->create(['name' => 'Salary', 'type' => 'income', 'is_active' => true]);

        // September income
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '8450000.00',
            'transaction_date' => '2026-09-25',
        ]);

        $response = $this->actingAs($user)->post('/settlements', [
            'period' => '2026-09',
            'settled_amount' => '8450000.00',
            'settled_at' => '2026-10-01',
            'notes' => 'Disetor tunai ke kantor',
        ]);

        $response->assertRedirect('/settlements');
        $settlement = MonthlyCashSettlement::where('user_id', $user->id)->first();
        $this->assertNotNull($settlement);
        $this->assertSame('2026-09-01', $settlement->period->toDateString());
        $this->assertSame('8450000.00', (string) $settlement->period_balance_snapshot);
        $this->assertSame('8450000.00', (string) $settlement->settled_amount);
        $this->assertSame('2026-10-01', $settlement->settled_at->toDateString());
        $this->assertSame('Disetor tunai ke kantor', $settlement->notes);
    }

    public function test_14_duplicate_settlement_rejected(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 09:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();

        MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-09',
            'period_balance_snapshot' => '500.00',
            'settled_amount' => '500.00',
            'settled_at' => '2026-10-01',
        ]);

        $response = $this->actingAs($user)->post('/settlements', [
            'period' => '2026-09',
            'settled_amount' => '500.00',
            'settled_at' => '2026-10-01',
        ]);

        $response->assertSessionHasErrors('period');
    }

    public function test_15_future_or_current_period_settlement_rejected(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 09:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();

        // Attempting to settle current month (2026-10)
        $resCurrent = $this->actingAs($user)->post('/settlements', [
            'period' => '2026-10',
            'settled_amount' => '100.00',
            'settled_at' => '2026-10-05',
        ]);
        $resCurrent->assertSessionHasErrors('period');

        // Attempting to settle future month (2026-11)
        $resFuture = $this->actingAs($user)->post('/settlements', [
            'period' => '2026-11',
            'settled_amount' => '100.00',
            'settled_at' => '2026-10-05',
        ]);
        $resFuture->assertSessionHasErrors('period');
    }

    public function test_16_different_settled_amount_produces_discrepancy(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 09:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $inCat = $user->categories()->create(['name' => 'Salary', 'type' => 'income', 'is_active' => true]);

        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '8450000.00',
            'transaction_date' => '2026-09-15',
        ]);

        $this->actingAs($user)->post('/settlements', [
            'period' => '2026-09',
            'settled_amount' => '8400000.00', // 50,000 less
            'settled_at' => '2026-10-01',
        ]);

        $settlement = MonthlyCashSettlement::where('user_id', $user->id)->first();
        $this->assertNotNull($settlement);
        $this->assertTrue($settlement->hasDiscrepancy());
        $this->assertSame('50000.00', (string) $settlement->discrepancy());
    }

    public function test_17_and_18_historical_modification_does_not_mutate_snapshot_and_detected(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 09:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $inCat = $user->categories()->create(['name' => 'Salary', 'type' => 'income', 'is_active' => true]);

        $tx = $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '5000.00',
            'transaction_date' => '2026-09-15',
        ]);

        // Settle September
        $this->actingAs($user)->post('/settlements', [
            'period' => '2026-09',
            'settled_amount' => '5000.00',
            'settled_at' => '2026-10-01',
        ]);

        $settlement = MonthlyCashSettlement::where('user_id', $user->id)->first();
        $this->assertSame('5000.00', $settlement->period_balance_snapshot);

        // Later, someone edits the historical transaction
        $tx->update(['amount' => '6000.00']);

        // Check again
        $settlement->refresh();
        // Snapshot MUST NOT change
        $this->assertSame('5000.00', $settlement->period_balance_snapshot);

        // Discrepancy detector must catch it
        $service = app(MonthlyCashService::class);
        $disc = $service->detectDiscrepancy($settlement);
        $this->assertTrue($disc['is_discrepant']);
        $this->assertSame('5000.00', $disc['snapshot_balance']);
        $this->assertSame('6000.00', $disc['current_recalculated_balance']);
        $this->assertSame('1000.00', $disc['difference']);
    }

    public function test_19_and_20_user_isolation_and_authorization(): void
    {
        $userA = $this->createUser('usera@example.test');
        $userB = $this->createUser('userb@example.test');

        $settlementA = MonthlyCashSettlement::create([
            'user_id' => $userA->id,
            'period' => '2026-08',
            'period_balance_snapshot' => '1000.00',
            'settled_amount' => '1000.00',
            'settled_at' => '2026-09-01',
        ]);

        // User B cannot view User A's settlement
        $this->actingAs($userB)->get("/settlements/{$settlementA->id}")->assertForbidden();

        // User B cannot delete User A's settlement
        $this->actingAs($userB)->delete("/settlements/{$settlementA->id}")->assertForbidden();

        // User B's dashboard does not see User A's settlement
        $this->actingAs($userB)->get('/dashboard')->assertOk()->assertDontSee('2026-08');
    }

    public function test_21_monetary_calculations_exactness_no_float_artifacts(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Fractional', 'type' => 'income', 'is_active' => true]);

        // Classical floating point problem: 0.1 + 0.2 = 0.30000000000000004
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '0.10',
            'transaction_date' => '2026-10-02',
        ]);
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '0.20',
            'transaction_date' => '2026-10-03',
        ]);

        $service = app(MonthlyCashService::class);
        $periodCash = $service->calculatePeriodCash($user, '2026-10');

        $this->assertSame('0.30', $periodCash);
    }

    public function test_24_dashboard_renders_cards_and_modal_smoke(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user, 'Kas Kantor', '500000.00');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Kas belum disetor');
        $response->assertSee('PEMASUKAN BULAN INI');
        $response->assertSee('PENGELUARAN BULAN INI');
        $response->assertSee('Saldo kas');
        $response->assertSee('Total kumulatif akuntansi');
    }

    public function test_outstanding_scenario_a_previous_period_full_settlement(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September cash = 8,450,000
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '8450000.00',
            'transaction_date' => '2026-09-10',
        ]);
        // October cash = 1,195,670
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '1195670.00',
            'transaction_date' => '2026-10-05',
        ]);

        // September settled fully (8,450,000)
        MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-09-01',
            'period_balance_snapshot' => '8450000.00',
            'settled_amount' => '8450000.00',
            'settled_at' => '2026-10-01',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('1195670.00', $result['currentPeriodCash']);
        $this->assertSame('0.00', $result['historicalCarryOver']);
        $this->assertFalse($result['hasCarryOver']);
        $this->assertSame('1195670.00', $result['outstandingCash']);
    }

    public function test_outstanding_scenario_b_previous_period_partial_settlement(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September cash = 8,450,000
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '8450000.00',
            'transaction_date' => '2026-09-10',
        ]);
        // October cash = 1,195,670
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '1195670.00',
            'transaction_date' => '2026-10-05',
        ]);

        // September settled partially: 8,400,000 -> remaining 50,000
        MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-09-01',
            'period_balance_snapshot' => '8450000.00',
            'settled_amount' => '8400000.00',
            'settled_at' => '2026-10-01',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('1195670.00', $result['currentPeriodCash']);
        $this->assertSame('50000.00', $result['historicalCarryOver']);
        $this->assertTrue($result['hasCarryOver']);
        // 1,195,670 + 50,000 = 1,245,670
        $this->assertSame('1245670.00', $result['outstandingCash']);
    }

    public function test_outstanding_scenario_c_previous_period_not_settled(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September cash = 8,450,000 (no settlement)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '8450000.00',
            'transaction_date' => '2026-09-10',
        ]);
        // October cash = 1,195,670
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '1195670.00',
            'transaction_date' => '2026-10-05',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('1195670.00', $result['currentPeriodCash']);
        $this->assertSame('8450000.00', $result['historicalCarryOver']);
        $this->assertTrue($result['hasCarryOver']);
        // 1,195,670 + 8,450,000 = 9,645,670
        $this->assertSame('9645670.00', $result['outstandingCash']);
    }

    public function test_outstanding_scenario_d_multiple_historical_unsettled_periods(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // July cash = 1,000,000 (unsettled)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '1000000.00',
            'transaction_date' => '2026-07-15',
        ]);
        // August cash = 2,000,000 (settled 1,800,000 -> 200,000 remaining)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '2000000.00',
            'transaction_date' => '2026-08-15',
        ]);
        MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-08-01',
            'period_balance_snapshot' => '2000000.00',
            'settled_amount' => '1800000.00',
            'settled_at' => '2026-09-01',
        ]);
        // September cash = 500,000 (unsettled)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '500000.00',
            'transaction_date' => '2026-09-15',
        ]);
        // October cash = 300,000
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '300000.00',
            'transaction_date' => '2026-10-10',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('300000.00', $result['currentPeriodCash']);
        // Historical: July (1,000,000) + August (200,000) + September (500,000) = 1,700,000
        $this->assertSame('1700000.00', $result['historicalCarryOver']);
        // Total Outstanding = 300,000 + 1,700,000 = 2,000,000
        $this->assertSame('2000000.00', $result['outstandingCash']);
    }

    public function test_outstanding_scenario_g_current_month_only_no_historical_data(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $cat->id,
            'type' => 'income',
            'amount' => '750000.00',
            'transaction_date' => '2026-10-05',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('750000.00', $result['currentPeriodCash']);
        $this->assertSame('0.00', $result['historicalCarryOver']);
        $this->assertFalse($result['hasCarryOver']);
        $this->assertSame('750000.00', $result['outstandingCash']);
    }

    public function test_outstanding_scenario_h_zero_balance_period(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $inCat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);
        $exCat = $user->categories()->create(['name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        // September net = 0 (100 income, 100 expense)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '100.00',
            'transaction_date' => '2026-09-02',
        ]);
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $exCat->id,
            'type' => 'expense',
            'amount' => '100.00',
            'transaction_date' => '2026-09-03',
        ]);

        // October cash = 500
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '500.00',
            'transaction_date' => '2026-10-05',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('500.00', $result['currentPeriodCash']);
        $this->assertSame('0.00', $result['historicalCarryOver']);
        $this->assertSame('500.00', $result['outstandingCash']);
    }

    public function test_outstanding_scenario_i_negative_period_balance(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $inCat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);
        $exCat = $user->categories()->create(['name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        // September net = -200.00 (expense 300, income 100, unsettled deficit)
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '100.00',
            'transaction_date' => '2026-09-02',
        ]);
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $exCat->id,
            'type' => 'expense',
            'amount' => '300.00',
            'transaction_date' => '2026-09-03',
        ]);

        // October net = +500.00
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '500.00',
            'transaction_date' => '2026-10-05',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('500.00', $result['currentPeriodCash']);
        $this->assertSame('-200.00', $result['historicalCarryOver']);
        $this->assertTrue($result['hasCarryOver']);
        // 500.00 + (-200.00) = 300.00
        $this->assertSame('300.00', $result['outstandingCash']);
    }

    public function test_outstanding_scenario_j_historical_edit_after_settlement_preserves_snapshot_in_outstanding(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $inCat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September income = 5000
        $tx = $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '5000.00',
            'transaction_date' => '2026-09-10',
        ]);

        // Settle September: snapshot 5000, settled 4500 -> remaining 500
        MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-09-01',
            'period_balance_snapshot' => '5000.00',
            'settled_amount' => '4500.00',
            'settled_at' => '2026-10-01',
        ]);

        // October cash = 1000
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inCat->id,
            'type' => 'income',
            'amount' => '1000.00',
            'transaction_date' => '2026-10-05',
        ]);

        // Later, someone edits September transaction to 6000
        $tx->update(['amount' => '6000.00']);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        // Snapshot is preserved in outstanding: 5000 - 4500 = 500 (NOT 6000 - 4500)
        $this->assertSame('500.00', $result['historicalCarryOver']);
        $this->assertSame('1500.00', $result['outstandingCash']);
    }

    // =========================================================================
    // SPRINT 19.5.1 MANDATORY TESTS
    // =========================================================================

    public function test_sprint_19_5_1_01_existing_historical_data_with_no_baseline_is_not_outstanding(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('nobaseline@test.com', baseline: null);
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // July, August, September historical transactions (worth 27,000,000)
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '10000000.00', 'transaction_date' => '2026-07-10']);
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '9000000.00', 'transaction_date' => '2026-08-10']);
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '8000000.00', 'transaction_date' => '2026-09-10']);

        // October cash = 1,200,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '1200000.00', 'transaction_date' => '2026-10-05']);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        // Without baseline, historical months MUST NOT be considered outstanding
        $this->assertFalse($result['isBaselineSet']);
        $this->assertSame('0.00', $result['historicalCarryOver']);
        $this->assertFalse($result['hasCarryOver']);
        $this->assertSame('1200000.00', $result['currentPeriodCash']);
        $this->assertSame('1200000.00', $result['outstandingCash']);
    }

    public function test_sprint_19_5_1_02_baseline_current_month_excludes_previous_historical_months(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('octbaseline@test.com', baseline: '2026-10-01');
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // Historical months before baseline
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '10000000.00', 'transaction_date' => '2026-07-10']);
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '9000000.00', 'transaction_date' => '2026-08-10']);
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '8000000.00', 'transaction_date' => '2026-09-10']);

        // October cash = 1,195,670
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '1195670.00', 'transaction_date' => '2026-10-05']);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertTrue($result['isBaselineSet']);
        $this->assertSame('2026-10-01', $result['baselineDate']);
        $this->assertSame('0.00', $result['historicalCarryOver']);
        $this->assertFalse($result['hasCarryOver']);
        $this->assertSame('1195670.00', $result['outstandingCash']);
    }

    public function test_sprint_19_5_1_03_current_month_period_cash_included_in_outstanding(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-20 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('octcash@test.com', baseline: '2026-10-01');
        $account = $this->createAccount($user);
        $inCat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);
        $exCat = $user->categories()->create(['name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $inCat->id, 'type' => 'income', 'amount' => '2000000.00', 'transaction_date' => '2026-10-05']);
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $exCat->id, 'type' => 'expense', 'amount' => '804330.00', 'transaction_date' => '2026-10-10']);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        // Net = 2,000,000 - 804,330 = 1,195,670
        $this->assertSame('1195670.00', $result['currentPeriodCash']);
        $this->assertSame('1195670.00', $result['outstandingCash']);
    }

    public function test_sprint_19_5_1_04_period_immediately_before_baseline_strictly_excluded(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('septexclude@test.com', baseline: '2026-10-01');
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September (immediately before baseline) has 8,450,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '8450000.00', 'transaction_date' => '2026-09-30']);

        // October has 500,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '500000.00', 'transaction_date' => '2026-10-05']);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertArrayNotHasKey('2026-09-01', $result['breakdown']);
        $this->assertSame('0.00', $result['historicalCarryOver']);
        $this->assertSame('500000.00', $result['outstandingCash']);
    }

    public function test_sprint_19_5_1_05_period_after_or_equal_baseline_without_settlement_included(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('octnov@test.com', baseline: '2026-10-01');
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September < baseline (5,000,000)
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '5000000.00', 'transaction_date' => '2026-09-15']);
        // October == baseline (1,200,000, unsettled)
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '1200000.00', 'transaction_date' => '2026-10-15']);
        // November current period (300,000)
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '300000.00', 'transaction_date' => '2026-11-05']);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        // September excluded, October included
        $this->assertSame('1200000.00', $result['historicalCarryOver']);
        $this->assertSame('300000.00', $result['currentPeriodCash']);
        $this->assertSame('1500000.00', $result['outstandingCash']);
        $this->assertArrayHasKey('2026-10-01', $result['breakdown']);
        $this->assertArrayNotHasKey('2026-09-01', $result['breakdown']);
    }

    public function test_sprint_19_5_1_06_full_settlement_after_baseline_yields_zero_carryover(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('fullsettle@test.com', baseline: '2026-10-01');
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // October cash = 1,200,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '1200000.00', 'transaction_date' => '2026-10-15']);
        // November cash = 400,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '400000.00', 'transaction_date' => '2026-11-05']);

        // Settle October fully
        MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-10-01',
            'period_balance_snapshot' => '1200000.00',
            'settled_amount' => '1200000.00',
            'settled_at' => '2026-11-01',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('0.00', $result['historicalCarryOver']);
        $this->assertFalse($result['hasCarryOver']);
        $this->assertSame('400000.00', $result['outstandingCash']);
    }

    public function test_sprint_19_5_1_07_partial_settlement_after_baseline_carries_remaining_amount(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('partialsettle@test.com', baseline: '2026-10-01');
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // October cash = 1,200,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '1200000.00', 'transaction_date' => '2026-10-15']);
        // November cash = 400,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '400000.00', 'transaction_date' => '2026-11-05']);

        // Settle October partially: 1,150,000 -> remaining 50,000
        MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-10-01',
            'period_balance_snapshot' => '1200000.00',
            'settled_amount' => '1150000.00',
            'settled_at' => '2026-11-01',
        ]);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        $this->assertSame('50000.00', $result['historicalCarryOver']);
        $this->assertSame('450000.00', $result['outstandingCash']);
    }

    public function test_sprint_19_5_1_08_multiple_unsettled_periods_after_baseline_accumulated(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('multiunsettled@test.com', baseline: '2026-10-01');
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September < baseline (8,000,000) -> ignored
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '8000000.00', 'transaction_date' => '2026-09-10']);
        // October >= baseline (1,000,000, unsettled)
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '1000000.00', 'transaction_date' => '2026-10-10']);
        // November >= baseline (700,000, unsettled)
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '700000.00', 'transaction_date' => '2026-11-10']);
        // December current (200,000)
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '200000.00', 'transaction_date' => '2026-12-05']);

        $service = app(MonthlyCashService::class);
        $result = $service->calculateOutstandingCash($user);

        // 1,000,000 + 700,000 = 1,700,000 carryover
        $this->assertSame('1700000.00', $result['historicalCarryOver']);
        // 1,700,000 + 200,000 = 1,900,000 total
        $this->assertSame('1900000.00', $result['outstandingCash']);
    }

    public function test_sprint_19_5_1_09_previous_month_card_before_baseline_uses_neutral_status(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('neutralcard@test.com', baseline: '2026-10-01');
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September (before baseline) has activity
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '8450000.00', 'transaction_date' => '2026-09-10']);

        $service = app(MonthlyCashService::class);
        $summary = $service->summaryForDashboard($user);

        $this->assertTrue($summary['isPreviousBeforeBaseline']);

        // Check dashboard UI
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Sebelum pelacakan');
        $response->assertSee('Periode sebelum pelacakan setoran');
        $response->assertDontSee('Belum disetor');
    }

    public function test_sprint_19_5_1_10_baseline_user_isolation(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $userA = $this->createUser('usera@test.com', baseline: '2026-10-01');
        $userB = $this->createUser('userb@test.com', baseline: '2026-08-01');

        $this->assertSame('2026-10-01', $userA->fresh('setting')->setting->cash_accountability_start_period->toDateString());
        $this->assertSame('2026-08-01', $userB->fresh('setting')->setting->cash_accountability_start_period->toDateString());

        // User A cannot affect User B's baseline
        $this->actingAs($userA)->post('/settlements/baseline', [
            'cash_accountability_start_period' => '2026-09-01',
        ])->assertRedirect();

        $this->assertSame('2026-09-01', $userA->fresh('setting')->setting->cash_accountability_start_period->toDateString());
        $this->assertSame('2026-08-01', $userB->fresh('setting')->setting->cash_accountability_start_period->toDateString());
    }

    public function test_sprint_19_5_1_11_invalid_or_future_baseline_validation(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('validationuser@test.com', baseline: null);

        // Future baseline (relative to current month: November 2026 > October 2026)
        $response = $this->actingAs($user)->post('/settlements/baseline', [
            'cash_accountability_start_period' => '2026-11-01',
        ]);
        $response->assertSessionHasErrors('cash_accountability_start_period');

        // Invalid date format
        $response2 = $this->actingAs($user)->post('/settlements/baseline', [
            'cash_accountability_start_period' => 'not-a-date',
        ]);
        $response2->assertSessionHasErrors('cash_accountability_start_period');
    }

    public function test_sprint_19_5_1_12_baseline_modification_safety_with_existing_settlement(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('safetyuser@test.com', baseline: '2026-08-01');

        // Active settlement in September 2026
        MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-09-01',
            'period_balance_snapshot' => '1000000.00',
            'settled_amount' => '1000000.00',
            'settled_at' => '2026-10-01',
        ]);

        // Attempting to move baseline to October 2026 (after earliest settlement September) must be rejected
        $response = $this->actingAs($user)->post('/settlements/baseline', [
            'cash_accountability_start_period' => '2026-10-01',
        ]);
        $response->assertSessionHasErrors('cash_accountability_start_period');

        // Moving baseline earlier (e.g. July 2026) is allowed
        $responseOk = $this->actingAs($user)->post('/settlements/baseline', [
            'cash_accountability_start_period' => '2026-07-01',
        ]);
        $responseOk->assertSessionHasNoErrors();
        $this->assertSame('2026-07-01', $user->fresh('setting')->setting->cash_accountability_start_period->toDateString());
    }

    public function test_sprint_19_5_1_13_void_settlement_keeps_db_record(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('voidrecord@test.com', baseline: '2026-08-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount' => '5000000.00',
            'settled_at' => '2026-10-01',
        ]);

        $response = $this->actingAs($user)->delete("/settlements/{$settlement->id}", [
            'void_reason' => 'Salah hitung tunai',
        ]);
        $response->assertRedirect('/settlements');

        // DB record must NOT be deleted
        $this->assertDatabaseHas('monthly_cash_settlements', [
            'id' => $settlement->id,
            'void_reason' => 'Salah hitung tunai',
        ]);

        $refreshed = $settlement->fresh();
        $this->assertNotNull($refreshed->voided_at);
        $this->assertTrue($refreshed->isVoided());
        $this->assertFalse($refreshed->isActive());
    }

    public function test_sprint_19_5_1_14_and_15_voided_settlement_becomes_outstanding_again(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('voidoutstanding@test.com', baseline: '2026-09-01');
        $account = $this->createAccount($user);
        $cat = $user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);

        // September income = 5,000,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '5000000.00', 'transaction_date' => '2026-09-10']);
        // October income = 1,000,000
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $cat->id, 'type' => 'income', 'amount' => '1000000.00', 'transaction_date' => '2026-10-05']);

        // Create active settlement for September
        $settlement = MonthlyCashSettlement::create([
            'user_id' => $user->id,
            'period' => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount' => '5000000.00',
            'settled_at' => '2026-10-01',
        ]);

        $service = app(MonthlyCashService::class);
        $beforeVoid = $service->calculateOutstandingCash($user);
        $this->assertSame('0.00', $beforeVoid['historicalCarryOver']);
        $this->assertSame('1000000.00', $beforeVoid['outstandingCash']);

        // Now void the settlement
        $this->actingAs($user)->delete("/settlements/{$settlement->id}");

        // Recalculate
        $afterVoid = $service->calculateOutstandingCash($user);
        // September is now unsettled again!
        $this->assertSame('5000000.00', $afterVoid['historicalCarryOver']);
        $this->assertSame('6000000.00', $afterVoid['outstandingCash']);
    }

    public function test_sprint_19_5_1_16_user_cannot_void_another_users_settlement(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $owner = $this->createUser('owner@test.com', baseline: '2026-08-01');
        $attacker = $this->createUser('attacker@test.com', baseline: '2026-08-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id' => $owner->id,
            'period' => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount' => '5000000.00',
            'settled_at' => '2026-10-01',
        ]);

        $response = $this->actingAs($attacker)->delete("/settlements/{$settlement->id}");
        $response->assertForbidden();

        // Settlement remains active
        $this->assertNull($settlement->fresh()->voided_at);
        $this->assertTrue($settlement->fresh()->isActive());
    }
    // =========================================================================
    // SPRINT 19.5.2 — Settlement Audit Integrity Tests
    // =========================================================================

    /**
     * Test 1: Settlement dibuat → harus tersimpan sebagai active record.
     */
    public function test_sprint_19_5_2_01_settlement_created(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_01@test.com', baseline: '2026-09-01');

        $response = $this->actingAs($user)->post('/settlements', [
            'period'         => '2026-09-01',
            'settled_amount' => '5000000',
            'settled_at'     => '2026-10-01',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify the active settlement was created (period stored as datetime in SQLite,
        // so we check via model query rather than assertDatabaseHas with raw date string)
        $this->assertSame(1, $user->monthlyCashSettlements()->whereNull('voided_at')->count());
        $this->assertSame(1, $user->monthlyCashSettlements()->count());
    }

    /**
     * Test 2: Settlement di-void → voided record tersimpan.
     */
    public function test_sprint_19_5_2_02_settlement_voided(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_02@test.com', baseline: '2026-09-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $this->actingAs($user)->delete("/settlements/{$settlement->id}", [
            'void_reason' => 'Nominal salah',
        ])->assertRedirect();

        $refreshed = $settlement->fresh();
        $this->assertNotNull($refreshed->voided_at);
        $this->assertTrue($refreshed->isVoided());
    }

    /**
     * Test 3: Voided row masih ada di DB (tidak dihapus).
     */
    public function test_sprint_19_5_2_03_voided_row_still_exists_in_db(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_03@test.com', baseline: '2026-09-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $this->actingAs($user)->delete("/settlements/{$settlement->id}", [
            'void_reason' => 'Nominal salah',
        ]);

        $this->assertDatabaseHas('monthly_cash_settlements', ['id' => $settlement->id]);
        $this->assertSame(1, MonthlyCashSettlement::where('user_id', $user->id)->count());
    }

    /**
     * Test 4: void_reason tetap ada setelah void (tidak ditimpa).
     */
    public function test_sprint_19_5_2_04_void_reason_persisted(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_04@test.com', baseline: '2026-09-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $this->actingAs($user)->delete("/settlements/{$settlement->id}", [
            'void_reason' => 'Nominal salah',
        ]);

        $this->assertDatabaseHas('monthly_cash_settlements', [
            'id'          => $settlement->id,
            'void_reason' => 'Nominal salah',
        ]);
    }

    /**
     * Test 5: Original settled_amount tetap ada di voided row (tidak ditimpa).
     */
    public function test_sprint_19_5_2_05_original_settled_amount_preserved_in_voided_row(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_05@test.com', baseline: '2026-09-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $this->actingAs($user)->delete("/settlements/{$settlement->id}", [
            'void_reason' => 'Nominal salah',
        ]);

        $voided = $settlement->fresh();
        $this->assertSame('5000000.00', (string) $voided->settled_amount);
    }

    /**
     * Test 6: Original settled_at tetap ada di voided row (tidak ditimpa).
     */
    public function test_sprint_19_5_2_06_original_settled_at_preserved_in_voided_row(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_06@test.com', baseline: '2026-09-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $this->actingAs($user)->delete("/settlements/{$settlement->id}", [
            'void_reason' => 'Nominal salah',
        ]);

        $voided = $settlement->fresh();
        $this->assertSame('2026-10-01', $voided->settled_at->toDateString());
    }

    /**
     * Test 7: Replacement settlement dapat dibuat setelah void.
     */
    public function test_sprint_19_5_2_07_replacement_settlement_can_be_created_after_void(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_07@test.com', baseline: '2026-09-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $this->actingAs($user)->delete("/settlements/{$settlement->id}", [
            'void_reason' => 'Nominal salah',
        ]);

        $response = $this->actingAs($user)->post('/settlements', [
            'period'         => '2026-09-01',
            'settled_amount' => '4800000',
            'settled_at'     => '2026-10-03',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /**
     * Test 8: Replacement adalah record BERBEDA (ID berbeda, total rows = 2).
     */
    public function test_sprint_19_5_2_08_replacement_is_a_distinct_record(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_08@test.com', baseline: '2026-09-01');

        $original = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $this->actingAs($user)->delete("/settlements/{$original->id}", [
            'void_reason' => 'Nominal salah',
        ]);

        $this->actingAs($user)->post('/settlements', [
            'period'         => '2026-09-01',
            'settled_amount' => '4800000',
            'settled_at'     => '2026-10-03',
        ]);

        $allRows = MonthlyCashSettlement::where('user_id', $user->id)
            ->whereDate('period', '2026-09-01')
            ->get();

        // Exactly 2 rows: one voided original, one active replacement
        $this->assertCount(2, $allRows);

        $replacement = $allRows->firstWhere('voided_at', null);
        $this->assertNotNull($replacement);
        $this->assertNotSame($original->id, $replacement->id);
    }

    /**
     * Test 9: Original voided row TIDAK BERUBAH setelah replacement dibuat.
     */
    public function test_sprint_19_5_2_09_original_voided_row_unchanged_after_replacement(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_09@test.com', baseline: '2026-09-01');

        $original = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $this->actingAs($user)->delete("/settlements/{$original->id}", [
            'void_reason' => 'Nominal salah',
        ]);

        $this->actingAs($user)->post('/settlements', [
            'period'         => '2026-09-01',
            'settled_amount' => '4800000',
            'settled_at'     => '2026-10-03',
        ]);

        $voided = $original->fresh();

        // Original settled_amount must remain 5,000,000 — not overwritten with 4,800,000
        $this->assertSame('5000000.00', (string) $voided->settled_amount);
        $this->assertSame('2026-10-01', $voided->settled_at->toDateString());
        $this->assertSame('Nominal salah', $voided->void_reason);
        $this->assertNotNull($voided->voided_at);
    }

    /**
     * Test 10: Outstanding cash hanya menggunakan ACTIVE (replacement) settlement.
     * Voided settlement tidak mengurangi outstanding.
     */
    public function test_sprint_19_5_2_10_outstanding_cash_uses_only_active_settlement(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user    = $this->createUser('s2_10@test.com', baseline: '2026-09-01');
        $account = $this->createAccount($user);
        $cat     = $user->categories()->create(['name' => 'Pemasukan', 'type' => 'income', 'is_active' => true]);

        // September income = 5,000,000
        $user->transactions()->create([
            'account_id'       => $account->id,
            'category_id'      => $cat->id,
            'type'             => 'income',
            'amount'           => '5000000.00',
            'transaction_date' => '2026-09-10',
        ]);

        $service = app(MonthlyCashService::class);

        // Create original settlement (fully settled, no outstanding)
        $original = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        $beforeVoid = $service->calculateOutstandingCash($user);
        $this->assertSame('0.00', $beforeVoid['historicalCarryOver']);

        // Void it
        $this->actingAs($user)->delete("/settlements/{$original->id}", ['void_reason' => 'Nominal salah']);

        // After void: September becomes outstanding again
        $afterVoid = $service->calculateOutstandingCash($user);
        $this->assertSame('5000000.00', $afterVoid['historicalCarryOver']);

        // Create replacement: settle 4,800,000 (200,000 remaining)
        $this->actingAs($user)->post('/settlements', [
            'period'         => '2026-09-01',
            'settled_amount' => '4800000',
            'settled_at'     => '2026-10-03',
        ]);

        $afterReplacement = $service->calculateOutstandingCash($user);
        // snapshot = 5,000,000 | settled = 4,800,000 | outstanding = 200,000
        $this->assertSame('200000.00', $afterReplacement['historicalCarryOver']);
    }

    /**
     * Test 11: Tidak boleh ada dua ACTIVE settlement untuk user+period yang sama.
     */
    public function test_sprint_19_5_2_11_cannot_create_two_active_settlements_for_same_period(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_11@test.com', baseline: '2026-09-01');

        MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        // Second settle attempt for same period must fail
        $response = $this->actingAs($user)->post('/settlements', [
            'period'         => '2026-09-01',
            'settled_amount' => '5000000',
            'settled_at'     => '2026-10-02',
        ]);

        $response->assertSessionHasErrors('period');

        // Still only one row
        $this->assertSame(1, MonthlyCashSettlement::where('user_id', $user->id)
            ->whereDate('period', '2026-09-01')
            ->whereNull('voided_at')
            ->count());
    }

    /**
     * Test 12: Race/concurrency — service layer rejects duplicate active on same period.
     * (Simulated synchronously: both calls to settle() for same user+period;
     *  the first succeeds, the second must throw ValidationException.)
     */
    public function test_sprint_19_5_2_12_concurrent_settle_protection(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user    = $this->createUser('s2_12@test.com', baseline: '2026-09-01');
        $service = app(MonthlyCashService::class);

        $validated = [
            'period'         => '2026-09-01',
            'settled_amount' => '5000000',
            'settled_at'     => '2026-10-01',
        ];

        // First call succeeds
        $settlement = $service->settle($user, $validated);
        $this->assertNotNull($settlement->id);

        // Second call for same period must throw
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->settle($user, array_merge($validated, ['settled_at' => '2026-10-02']));
    }

    /**
     * Test 13: User B tidak dapat void/replace settlement User A.
     */
    public function test_sprint_19_5_2_13_user_b_cannot_affect_user_a_settlement(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $userA = $this->createUser('s2_13a@test.com', baseline: '2026-09-01');
        $userB = $this->createUser('s2_13b@test.com', baseline: '2026-09-01');

        $settlement = MonthlyCashSettlement::create([
            'user_id'                 => $userA->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '5000000.00',
            'settled_amount'          => '5000000.00',
            'settled_at'              => '2026-10-01',
        ]);

        // User B attempts to void User A's settlement
        $this->actingAs($userB)->delete("/settlements/{$settlement->id}", [
            'void_reason' => 'Attempt',
        ])->assertForbidden();

        // User A's record untouched
        $this->assertNull($settlement->fresh()->voided_at);
        $this->assertTrue($settlement->fresh()->isActive());
    }

    /**
     * Test 14: Baseline regression — baseline semantics dari Sprint 19.5.1 tetap valid.
     */
    public function test_sprint_19_5_2_14_baseline_regression(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        // No baseline: historical carry-over = 0
        $userNoBaseline = $this->createUser('s2_14a@test.com', baseline: null);
        $service        = app(MonthlyCashService::class);

        $result = $service->calculateOutstandingCash($userNoBaseline);
        $this->assertFalse($result['isBaselineSet']);
        $this->assertSame('0.00', $result['historicalCarryOver']);

        // With baseline set to October 2026 (current month), no closed periods tracked
        $userWithBaseline = $this->createUser('s2_14b@test.com', baseline: '2026-10-01');
        $result2          = $service->calculateOutstandingCash($userWithBaseline);
        $this->assertTrue($result2['isBaselineSet']);
        $this->assertSame('0.00', $result2['historicalCarryOver']);
    }

    /**
     * Test 15: Outstanding cash regression — formula sesuai Sprint 19.5.1.
     */
    public function test_sprint_19_5_2_15_outstanding_cash_regression(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user    = $this->createUser('s2_15@test.com', baseline: '2026-09-01');
        $account = $this->createAccount($user);
        $cat     = $user->categories()->create(['name' => 'Gaji', 'type' => 'income', 'is_active' => true]);

        // September: 8,000,000 income; 1,000,000 settled
        $user->transactions()->create([
            'account_id' => $account->id, 'category_id' => $cat->id,
            'type' => 'income', 'amount' => '8000000.00', 'transaction_date' => '2026-09-10',
        ]);
        MonthlyCashSettlement::create([
            'user_id' => $user->id, 'period' => '2026-09-01',
            'period_balance_snapshot' => '8000000.00', 'settled_amount' => '1000000.00',
            'settled_at' => '2026-10-01',
        ]);

        // October (current): 500,000 income
        $user->transactions()->create([
            'account_id' => $account->id, 'category_id' => $cat->id,
            'type' => 'income', 'amount' => '500000.00', 'transaction_date' => '2026-10-05',
        ]);

        $service = app(MonthlyCashService::class);
        $result  = $service->calculateOutstandingCash($user);

        // historicalCarryOver = 8,000,000 - 1,000,000 = 7,000,000
        $this->assertSame('7000000.00', $result['historicalCarryOver']);
        // outstandingCash = 7,000,000 + 500,000 = 7,500,000
        $this->assertSame('7500000.00', $result['outstandingCash']);
        $this->assertSame('500000.00', $result['currentPeriodCash']);
    }

    /**
     * Test 16: AccountBalanceService / DashboardService regression.
     * Dashboard loads without error after all Sprint 19.5.2 changes.
     */
    public function test_sprint_19_5_2_16_dashboard_regression(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s2_16@test.com', baseline: '2026-09-01');

        // Void-then-replace scenario: September
        $settlement = MonthlyCashSettlement::create([
            'user_id'                 => $user->id,
            'period'                  => '2026-09-01',
            'period_balance_snapshot' => '3000000.00',
            'settled_amount'          => '3000000.00',
            'settled_at'              => '2026-10-01',
        ]);
        $this->actingAs($user)->delete("/settlements/{$settlement->id}", ['void_reason' => 'Error']);
        $this->actingAs($user)->post('/settlements', [
            'period'         => '2026-09-01',
            'settled_amount' => '2900000',
            'settled_at'     => '2026-10-02',
        ]);

        // Dashboard must render without exceptions
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
    }

    /**
     * SPRINT 19.5.3 — Test Suite
     * A. first settlement: no active row -> create active settlement.
     * B. second active settlement same user+period: rejected.
     * C. void active settlement: original row remains.
     * D. replacement: creates new row.
     * E. old voided row unchanged.
     * F. only one active settlement.
     */
    public function test_sprint_19_5_3_a_through_f_settlement_invariants(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user    = $this->createUser('s3_af@test.com', baseline: '2026-09-01');
        $account = $this->createAccount($user);
        $cat     = $user->categories()->create(['name' => 'Gaji', 'type' => 'income', 'is_active' => true]);

        $user->transactions()->create([
            'account_id' => $account->id, 'category_id' => $cat->id,
            'type' => 'income', 'amount' => '5000000.00', 'transaction_date' => '2026-09-10',
        ]);

        $service = app(MonthlyCashService::class);

        // A: First settlement creates active settlement
        $first = $service->settle($user, [
            'period' => '2026-09-01',
            'settled_amount' => '5000000.00',
            'settled_at' => '2026-10-01',
            'notes' => 'Original settlement',
        ]);
        $this->assertNotNull($first->id);
        $this->assertTrue($first->isActive());
        $this->assertNull($first->voided_at);

        // B: Second active settlement for same user + period is rejected
        try {
            $service->settle($user, [
                'period' => '2026-09-01',
                'settled_amount' => '5000000.00',
                'settled_at' => '2026-10-02',
            ]);
            $this->fail('Expected ValidationException was not thrown on duplicate active settlement.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('period', $e->errors());
        }

        // C: Void active settlement — original row remains
        $service->voidSettlement($user, $first, 'Nominal salah hitung');
        $firstFresh = $first->fresh();
        $this->assertTrue($firstFresh->isVoided());
        $this->assertNotNull($firstFresh->voided_at);
        $this->assertSame('Nominal salah hitung', $firstFresh->void_reason);

        // D: Replacement creates new row
        $second = $service->settle($user, [
            'period' => '2026-09-01',
            'settled_amount' => '4800000.00',
            'settled_at' => '2026-10-03',
            'notes' => 'Replacement settlement',
        ]);
        $this->assertNotNull($second->id);
        $this->assertNotSame($first->id, $second->id);
        $this->assertTrue($second->isActive());
        $this->assertNull($second->voided_at);
        $this->assertSame('4800000.00', (string) $second->settled_amount);

        // E: Old voided row remains completely unchanged
        $firstCheck = $first->fresh();
        $this->assertSame($firstFresh->voided_at->toDateTimeString(), $firstCheck->voided_at->toDateTimeString());
        $this->assertSame('Nominal salah hitung', $firstCheck->void_reason);
        $this->assertSame('5000000.00', (string) $firstCheck->settled_amount);
        $this->assertSame('Original settlement', $firstCheck->notes);

        // F: Invariant — exactly one active settlement exists for user + period
        $activeCount = MonthlyCashSettlement::query()
            ->where('user_id', $user->id)
            ->whereDate('period', '2026-09-01')
            ->whereNull('voided_at')
            ->count();
        $this->assertSame(1, $activeCount);

        // Total rows = 2 (1 voided, 1 active)
        $totalCount = MonthlyCashSettlement::query()
            ->where('user_id', $user->id)
            ->whereDate('period', '2026-09-01')
            ->count();
        $this->assertSame(2, $totalCount);

        // Outstanding calculation only uses active settlement
        $outstanding = $service->calculateOutstandingCash($user);
        // snapshot 5,000,000 - active settled 4,800,000 = carry-over 200,000
        $this->assertSame('200000.00', $outstanding['historicalCarryOver']);
    }

    /**
     * G. different users may settle same period independently.
     */
    public function test_sprint_19_5_3_g_different_users_settle_same_period_independently(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $userA = $this->createUser('s3_ga@test.com', baseline: '2026-09-01');
        $userB = $this->createUser('s3_gb@test.com', baseline: '2026-09-01');

        $service = app(MonthlyCashService::class);

        $settlementA = $service->settle($userA, [
            'period' => '2026-09-01',
            'settled_amount' => '1000000.00',
            'settled_at' => '2026-10-01',
        ]);
        $settlementB = $service->settle($userB, [
            'period' => '2026-09-01',
            'settled_amount' => '2000000.00',
            'settled_at' => '2026-10-01',
        ]);

        $this->assertNotNull($settlementA->id);
        $this->assertNotNull($settlementB->id);
        $this->assertNotSame($settlementA->id, $settlementB->id);
        $this->assertTrue($settlementA->isActive());
        $this->assertTrue($settlementB->isActive());
        $this->assertSame($userA->id, $settlementA->user_id);
        $this->assertSame($userB->id, $settlementB->user_id);
    }

    /**
     * H. settle and void service paths use deterministic locking on User row.
     */
    public function test_sprint_19_5_3_h_settle_and_void_use_deterministic_user_locking(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $user = $this->createUser('s3_h@test.com', baseline: '2026-09-01');
        $service = app(MonthlyCashService::class);

        // 1. Verify User lockForUpdate builder sets lock = true
        $userLockQuery = User::query()->whereKey($user->id)->lockForUpdate();
        $this->assertTrue($userLockQuery->getQuery()->lock, 'User query must have lockForUpdate enabled');

        // 2. Track queries during settle()
        $settleQueries = [];
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$settleQueries) {
            $settleQueries[] = strtolower($query->sql);
        });

        $settlement = $service->settle($user, [
            'period' => '2026-09-01',
            'settled_amount' => '1000000.00',
            'settled_at' => '2026-10-01',
        ]);

        // Find queries mentioning users and monthly_cash_settlements
        $userQueryIndex = null;
        $settlementCheckIndex = null;
        foreach ($settleQueries as $i => $sql) {
            if ($userQueryIndex === null && str_contains($sql, 'users') && (str_contains($sql, 'id = ?') || str_contains($sql, '"id" = ?') || str_contains($sql, '`id` = ?'))) {
                $userQueryIndex = $i;
            }
            if ($settlementCheckIndex === null && str_contains($sql, 'monthly_cash_settlements') && str_contains($sql, 'voided_at')) {
                $settlementCheckIndex = $i;
            }
        }

        $this->assertNotNull($userQueryIndex, 'User row must be queried for serialization inside transaction');
        $this->assertNotNull($settlementCheckIndex, 'Active settlement must be queried');
        $this->assertLessThan($settlementCheckIndex, $userQueryIndex, 'User row must be locked BEFORE checking active settlements');

        // On MySQL/Postgres drivers, verify FOR UPDATE is present
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            $this->assertTrue(collect($settleQueries)->contains(fn ($q) => str_contains($q, 'for update')));
        }

        // 3. Track queries during voidSettlement()
        $voidQueries = [];
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$voidQueries) {
            $voidQueries[] = strtolower($query->sql);
        });

        $service->voidSettlement($user, $settlement, 'Audit test');

        $userVoidQueryIndex = null;
        $settlementVoidLockIndex = null;
        foreach ($voidQueries as $i => $sql) {
            if ($userVoidQueryIndex === null && str_contains($sql, 'users') && (str_contains($sql, 'id = ?') || str_contains($sql, '"id" = ?') || str_contains($sql, '`id` = ?'))) {
                $userVoidQueryIndex = $i;
            }
            if ($settlementVoidLockIndex === null && str_contains($sql, 'monthly_cash_settlements')) {
                $settlementVoidLockIndex = $i;
            }
        }

        $this->assertNotNull($userVoidQueryIndex, 'User row must be locked first during voidSettlement()');
        $this->assertNotNull($settlementVoidLockIndex, 'Settlement row must be queried during voidSettlement()');
        $this->assertLessThanOrEqual($settlementVoidLockIndex, $userVoidQueryIndex, 'User row lock must precede settlement query in void');
    }

    /**
     * I. monetary discrepancy formatting does not convert value to float.
     */
    public function test_sprint_19_5_3_i_monetary_discrepancy_does_not_convert_to_float(): void
    {
        $settlement = new MonthlyCashSettlement([
            'period_balance_snapshot' => '1000000.00',
            'settled_amount'          => '950000.50',
        ]);

        $discrepancy = $settlement->discrepancy();
        $this->assertInstanceOf(\Brick\Math\BigDecimal::class, $discrepancy);
        $this->assertSame('49999.50', (string) $discrepancy);

        $setting = new \App\Models\Setting();
        $formatted = $setting->formatMoney($discrepancy);
        $this->assertSame('49,999.50', $formatted);
    }

    /**
     * J. very large DECIMAL(18,2) formatting remains exact (Example: 9999999999999999.99).
     */
    public function test_sprint_19_5_3_j_very_large_decimal_formatting_remains_exact(): void
    {
        $setting = new \App\Models\Setting();

        $largeValue = '9999999999999999.99';
        $formatted = $setting->formatMoney($largeValue);

        // IEEE 754 float precision loss would round 9999999999999999.99 to 10,000,000,000,000,000.00
        $this->assertNotSame('10,000,000,000,000,000.00', $formatted);
        $this->assertSame('9,999,999,999,999,999.99', $formatted);

        // Also test negative large value
        $negativeLarge = '-9999999999999999.99';
        $this->assertSame('-9,999,999,999,999,999.99', $setting->formatMoney($negativeLarge));

        // Test boundary values
        $this->assertSame('0.00', $setting->formatMoney('0'));
        $this->assertSame('0.00', $setting->formatMoney(null));
        $this->assertSame('1,234,567.89', $setting->formatMoney('1234567.89'));
        $this->assertSame('10,000,000.00', $setting->formatMoney(\Brick\Math\BigDecimal::of('10000000')));
    }
}
