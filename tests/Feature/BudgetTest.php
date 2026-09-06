<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function user(string $email = 'budget@example.test'): User
    {
        return User::create(['name' => 'Budget Owner', 'email' => $email, 'password' => 'SecurePassword123!']);
    }

    private function category(User $user, string $name = 'Food', string $type = 'expense'): Category
    {
        return $user->categories()->create(['name' => $name, 'type' => $type, 'is_active' => true]);
    }

    private function payload(Category $category, array $overrides = []): array
    {
        return array_merge(['category_id' => $category->id, 'amount' => '1000.00', 'month' => 8, 'year' => 2026], $overrides);
    }

    public function test_user_can_create_filter_edit_and_delete_budget(): void
    {
        $user = $this->user();
        $category = $this->category($user);

        $this->actingAs($user)->post('/budgets', $this->payload($category))->assertRedirect('/budgets?month=8&year=2026');
        $budget = Budget::firstOrFail();
        $this->actingAs($user)->get('/budgets?month=8&year=2026')->assertOk()->assertSee('Food');
        $this->actingAs($user)->get('/budgets?month=7&year=2026')->assertOk()->assertDontSee('Food');
        $this->actingAs($user)->put(route('budgets.update', $budget), $this->payload($category, ['amount' => '1500.00']))->assertRedirect('/budgets?month=8&year=2026');
        $this->assertSame('1500.00', $budget->fresh()->amount);
        $this->actingAs($user)->delete(route('budgets.destroy', $budget))->assertRedirect('/budgets?month=8&year=2026');
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_duplicate_period_and_income_category_are_rejected(): void
    {
        $user = $this->user();
        $expense = $this->category($user);
        $income = $this->category($user, 'Salary', 'income');
        $user->budgets()->create($this->payload($expense));

        $this->actingAs($user)->post('/budgets', $this->payload($expense))->assertSessionHasErrors('category_id');
        $this->actingAs($user)->post('/budgets', $this->payload($income))->assertSessionHasErrors('category_id');
        $this->assertCount(1, $user->budgets);
    }

    public function test_utilization_tracks_expense_edits_and_soft_deletes(): void
    {
        $user = $this->user();
        $category = $this->category($user);
        $account = $user->accounts()->create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => 0, 'currency' => 'IDR', 'is_active' => true]);
        $user->budgets()->create($this->payload($category, ['amount' => '100.00']));
        $transaction = $user->transactions()->create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => '125.00', 'transaction_date' => '2026-08-10']);
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => '999.00', 'transaction_date' => '2026-07-31']);

        $this->actingAs($user)->get('/budgets?month=8&year=2026')->assertOk()
            ->assertSee('125.00')->assertSee('125.0%')->assertSee('Melebihi anggaran');

        $transaction->update(['amount' => '40.00']);
        $this->actingAs($user)->get('/budgets?month=8&year=2026')->assertOk()
            ->assertSee('40.00')->assertSee('40.0%')->assertSee('Sesuai rencana');

        $transaction->delete();
        $this->actingAs($user)->get('/budgets?month=8&year=2026')->assertOk()
            ->assertSee('0.00')->assertSee('0.0%');
    }

    public function test_cross_user_access_and_categories_are_blocked(): void
    {
        $owner = $this->user();
        $other = $this->user('other-budget@example.test');
        $category = $this->category($owner, 'Private Food');
        $budget = $owner->budgets()->create($this->payload($category));

        $this->actingAs($other)->get(route('budgets.edit', $budget))->assertForbidden();
        $this->actingAs($other)->put(route('budgets.update', $budget), $this->payload($category))->assertForbidden();
        $this->actingAs($other)->delete(route('budgets.destroy', $budget))->assertForbidden();
        $this->actingAs($other)->post('/budgets', $this->payload($category))->assertSessionHasErrors('category_id');
        $this->actingAs($other)->get('/budgets?month=8&year=2026')->assertOk()->assertDontSee('Private Food');
    }

    public function test_empty_state_and_current_month_dashboard_summary_work(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-15', 'Asia/Jakarta'));
        $user = $this->user();
        $this->actingAs($user)->get('/budgets')->assertOk()->assertSee('Belum ada anggaran bulan ini');

        $category = $this->category($user);
        $account = $user->accounts()->create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => 0, 'currency' => 'IDR', 'is_active' => true]);
        $user->budgets()->create($this->payload($category));
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => '250.00', 'transaction_date' => '2026-08-10']);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('budgetAmount', '1000.00')->assertViewHas('budgetUsed', '250.00')
            ->assertViewHas('budgetPercentage', 25.0)->assertSee('Anggaran bulanan');
    }

    public function test_zero_amount_budget_utilization_returns_zero_without_exception(): void
    {
        $user = $this->user();
        $category = $this->category($user);
        $budget = $user->budgets()->make([
            'category_id' => $category->id,
            'amount' => '0.00',
            'month' => 8,
            'year' => 2026,
        ]);
        $budget->used_amount = '50.00';

        $this->assertSame(0.0, $budget->utilizationPercentage());
    }
}
