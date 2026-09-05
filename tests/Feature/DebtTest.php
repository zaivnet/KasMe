<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Debt;
use App\Models\User;
use App\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function user(string $email = 'debt@example.test'): User
    {
        return User::create(['name' => 'Debt Owner', 'email' => $email, 'password' => 'SecurePassword123!']);
    }

    private function account(User $user, string $name = 'Bank', string $opening = '1000.00'): Account
    {
        return $user->accounts()->create(['name' => $name, 'type' => 'bank', 'opening_balance' => $opening, 'currency' => 'IDR', 'is_active' => true]);
    }

    private function debtPayload(array $overrides = []): array
    {
        return array_merge(['type' => 'debt', 'person_name' => 'Lender', 'original_amount' => '500.00', 'start_date' => '2026-08-01', 'due_date' => '2026-09-01', 'notes' => 'Loan'], $overrides);
    }

    private function paymentPayload(Account $account, array $overrides = []): array
    {
        return array_merge(['account_id' => $account->id, 'amount' => '200.00', 'payment_date' => '2026-08-10', 'notes' => 'Installment'], $overrides);
    }

    private function createDebt(User $user, array $overrides = []): Debt
    {
        $data = $this->debtPayload($overrides);

        return $user->debts()->create(array_merge($data, ['remaining_amount' => $data['original_amount'], 'status' => 'active']));
    }

    public function test_debt_crud_and_empty_state_work(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get('/debts')->assertOk()->assertSee('Catatan utang atau piutang tidak ditemukan');
        $this->actingAs($user)->post('/debts', $this->debtPayload())->assertRedirect();
        $debt = Debt::firstOrFail();
        $this->assertSame('500.00', $debt->remaining_amount);
        $this->actingAs($user)->put(route('debts.update', $debt), $this->debtPayload(['person_name' => 'Updated Lender', 'original_amount' => '600.00']))->assertRedirect(route('debts.show', $debt));
        $this->assertSame('600.00', $debt->fresh()->remaining_amount);
        $this->actingAs($user)->delete(route('debts.destroy', $debt))->assertRedirect('/debts');
        $this->assertSoftDeleted('debts', ['id' => $debt->id]);
    }

    public function test_debt_payment_reduces_remaining_and_account_balance_atomically(): void
    {
        $user = $this->user();
        $account = $this->account($user);
        $debt = $this->createDebt($user);

        $this->actingAs($user)->post(route('debts.payments.store', $debt), $this->paymentPayload($account))->assertRedirect(route('debts.show', $debt));
        $this->assertSame('300.00', $debt->fresh()->remaining_amount);
        $this->assertSame('800.00', app(AccountBalanceService::class)->calculate($account));
        $this->assertDatabaseHas('debt_payments', ['debt_id' => $debt->id, 'amount' => 200]);
    }

    public function test_receivable_payment_increases_account_and_paid_status_is_managed(): void
    {
        $user = $this->user();
        $account = $this->account($user);
        $receivable = $this->createDebt($user, ['type' => 'receivable', 'original_amount' => '500.00']);

        $this->actingAs($user)->post(route('debts.payments.store', $receivable), $this->paymentPayload($account, ['amount' => '500.00']))->assertRedirect();
        $this->assertSame('0.00', $receivable->fresh()->remaining_amount);
        $this->assertSame('paid', $receivable->fresh()->status);
        $this->assertSame('1500.00', app(AccountBalanceService::class)->calculate($account));
    }

    public function test_overpayment_is_rejected_without_partial_changes(): void
    {
        $user = $this->user();
        $account = $this->account($user);
        $debt = $this->createDebt($user);

        $this->actingAs($user)->post(route('debts.payments.store', $debt), $this->paymentPayload($account, ['amount' => '500.01']))->assertSessionHasErrors('amount');
        $this->assertSame('500.00', $debt->fresh()->remaining_amount);
        $this->assertDatabaseCount('debt_payments', 0);
        $this->assertSame('1000.00', app(AccountBalanceService::class)->calculate($account));
    }

    public function test_payment_edit_and_reversal_reconcile_remaining_and_account(): void
    {
        $user = $this->user();
        $first = $this->account($user);
        $second = $this->account($user, 'Cash', '100.00');
        $debt = $this->createDebt($user);
        $this->actingAs($user)->post(route('debts.payments.store', $debt), $this->paymentPayload($first));
        $payment = $debt->payments()->firstOrFail();

        $this->actingAs($user)->put(route('debts.payments.update', [$debt, $payment]), $this->paymentPayload($second, ['amount' => '300.00']))->assertRedirect();
        $this->assertSame('200.00', $debt->fresh()->remaining_amount);
        $this->assertSame('1000.00', app(AccountBalanceService::class)->calculate($first));
        $this->assertSame('-200.00', app(AccountBalanceService::class)->calculate($second));

        $this->actingAs($user)->delete(route('debts.payments.destroy', [$debt, $payment]))->assertRedirect();
        $this->assertSame('500.00', $debt->fresh()->remaining_amount);
        $this->assertSame('100.00', app(AccountBalanceService::class)->calculate($second));
        $this->assertDatabaseCount('debt_payments', 0);
    }

    public function test_cross_user_debt_payment_and_account_access_are_blocked(): void
    {
        $owner = $this->user();
        $other = $this->user('other-debt@example.test');
        $debt = $this->createDebt($owner);
        $ownerAccount = $this->account($owner);
        $otherAccount = $this->account($other, 'Other Bank');
        $payment = $debt->payments()->create($this->paymentPayload($ownerAccount));

        $this->actingAs($other)->get(route('debts.show', $debt))->assertForbidden();
        $this->actingAs($other)->post(route('debts.payments.store', $debt), $this->paymentPayload($otherAccount))->assertForbidden();
        $this->actingAs($owner)->post(route('debts.payments.store', $debt), $this->paymentPayload($otherAccount))->assertSessionHasErrors('account_id');
        $this->actingAs($other)->get(route('debts.payments.edit', [$debt, $payment]))->assertForbidden();
    }

    public function test_overdue_status_is_derived_safely(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-02', 'Asia/Jakarta'));
        $user = $this->user();
        $debt = $this->createDebt($user);

        $this->assertSame('overdue', $debt->effectiveStatus());
        $this->actingAs($user)->get('/debts?status=overdue')->assertOk()->assertSee('Lender')->assertSee('Terlambat');
        $this->assertSame('active', $debt->fresh()->status);
    }
}
