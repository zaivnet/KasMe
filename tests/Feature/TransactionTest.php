<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $incomeCategory;

    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Ledger Owner', 'email' => 'ledger@example.test', 'password' => 'SecurePassword123!']);
        $this->account = $this->user->accounts()->create(['name' => 'Main', 'type' => 'bank', 'opening_balance' => '1000000.00', 'currency' => 'IDR', 'is_active' => true]);
        $this->incomeCategory = $this->user->categories()->create(['name' => 'Income', 'type' => 'income', 'is_active' => true]);
        $this->expenseCategory = $this->user->categories()->create(['name' => 'Expense', 'type' => 'expense', 'is_active' => true]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['account_id' => $this->account->id, 'category_id' => $this->expenseCategory->id, 'type' => 'expense', 'adjustment_direction' => null, 'amount' => '200000.00', 'transaction_date' => '2026-08-11', 'description' => 'Real ledger entry'], $overrides);
    }

    public function test_required_balance_reconciliation_equals_1325000(): void
    {
        $this->user->transactions()->create($this->payload(['type' => 'income', 'category_id' => $this->incomeCategory->id, 'amount' => '500000.00']));
        $this->user->transactions()->create($this->payload(['amount' => '200000.00']));
        $this->user->transactions()->create($this->payload(['type' => 'adjustment', 'category_id' => null, 'adjustment_direction' => 'increase', 'amount' => '50000.00']));
        $this->user->transactions()->create($this->payload(['type' => 'adjustment', 'category_id' => null, 'adjustment_direction' => 'decrease', 'amount' => '25000.00']));

        $this->assertSame('1325000.00', app(AccountBalanceService::class)->calculate($this->account));
    }

    public function test_user_can_create_edit_move_and_soft_delete_transaction(): void
    {
        $otherAccount = $this->user->accounts()->create(['name' => 'Second', 'type' => 'cash', 'opening_balance' => '0.00', 'currency' => 'IDR', 'is_active' => true]);
        $this->actingAs($this->user)->post('/transactions', $this->payload(['type' => 'income', 'category_id' => $this->incomeCategory->id, 'amount' => '500000.00']))->assertSessionHasNoErrors();
        $transaction = Transaction::firstOrFail();
        $this->assertSame('1500000.00', app(AccountBalanceService::class)->calculate($this->account));

        $this->actingAs($this->user)->put(route('transactions.update', $transaction), $this->payload(['account_id' => $otherAccount->id, 'type' => 'income', 'category_id' => $this->incomeCategory->id, 'amount' => '300000.00']))->assertSessionHasNoErrors();
        $this->assertSame('1000000.00', app(AccountBalanceService::class)->calculate($this->account));
        $this->assertSame('300000.00', app(AccountBalanceService::class)->calculate($otherAccount));

        $this->actingAs($this->user)->delete(route('transactions.destroy', $transaction))->assertRedirect('/transactions');
        $this->assertSoftDeleted($transaction);
        $this->assertSame('0.00', app(AccountBalanceService::class)->calculate($otherAccount));
    }

    public function test_invalid_category_type_and_cross_user_relations_are_rejected(): void
    {
        $this->actingAs($this->user)->post('/transactions', $this->payload(['type' => 'income', 'category_id' => $this->expenseCategory->id]))->assertSessionHasErrors('category_id');
        $other = User::create(['name' => 'Other', 'email' => 'other-ledger@example.test', 'password' => 'SecurePassword123!']);
        $otherAccount = $other->accounts()->create(['name' => 'Private', 'type' => 'cash', 'opening_balance' => 0, 'currency' => 'IDR', 'is_active' => true]);
        $this->actingAs($this->user)->post('/transactions', $this->payload(['account_id' => $otherAccount->id]))->assertSessionHasErrors('account_id');
        $this->actingAs($this->user)->post('/transactions', $this->payload(['amount' => '-1']))->assertSessionHasErrors('amount');
    }

    public function test_cross_user_transaction_access_is_forbidden(): void
    {
        $transaction = $this->user->transactions()->create($this->payload());
        $other = User::create(['name' => 'Other', 'email' => 'other-ledger@example.test', 'password' => 'SecurePassword123!']);

        $this->actingAs($other)->get(route('transactions.show', $transaction))->assertForbidden();
        $this->actingAs($other)->get(route('transactions.edit', $transaction))->assertForbidden();
        $this->actingAs($other)->put(route('transactions.update', $transaction), $this->payload())->assertForbidden();
        $this->actingAs($other)->delete(route('transactions.destroy', $transaction))->assertForbidden();
    }

    public function test_filters_and_user_scoped_listing_work(): void
    {
        $this->user->transactions()->create($this->payload(['description' => 'Visible expense']));
        $this->user->transactions()->create($this->payload(['type' => 'income', 'category_id' => $this->incomeCategory->id, 'description' => 'Hidden income']));

        $this->actingAs($this->user)->get('/transactions?type=expense&date_from=2026-08-01&date_to=2026-08-31')
            ->assertOk()->assertSee('Visible expense')->assertDontSee('Hidden income');
    }
}
