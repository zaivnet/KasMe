<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\SavingGoal;
use App\Models\User;
use App\Services\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingGoalTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'goal@example.test'): User
    {
        return User::create(['name' => 'Goal Owner', 'email' => $email, 'password' => 'SecurePassword123!']);
    }

    private function account(User $user, string $name = 'Bank', string $opening = '1000.00'): Account
    {
        return $user->accounts()->create(['name' => $name, 'type' => 'bank', 'opening_balance' => $opening, 'currency' => 'IDR', 'is_active' => true]);
    }

    private function goalPayload(array $overrides = []): array
    {
        return array_merge(['name' => 'Emergency Fund', 'target_amount' => '500.00', 'target_date' => '2026-12-31', 'description' => 'Reserve'], $overrides);
    }

    private function movementPayload(Account $account, array $overrides = []): array
    {
        return array_merge(['account_id' => $account->id, 'type' => 'contribution', 'amount' => '200.00', 'transaction_date' => '2026-08-11', 'notes' => 'Saved'], $overrides);
    }

    private function createGoal(User $user, array $overrides = []): SavingGoal
    {
        return $user->savingGoals()->create(array_merge($this->goalPayload($overrides), ['status' => 'active']));
    }

    public function test_goal_crud_filter_and_empty_state_work(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get('/saving-goals')->assertOk()->assertSee('Target tabungan tidak ditemukan');
        $this->actingAs($user)->post('/saving-goals', $this->goalPayload())->assertRedirect();
        $goal = SavingGoal::firstOrFail();
        $this->actingAs($user)->put(route('saving-goals.update', $goal), array_merge($this->goalPayload(['name' => 'Updated Goal']), ['status' => 'cancelled']))->assertRedirect();
        $this->assertSame('cancelled', $goal->fresh()->status);
        $this->actingAs($user)->get('/saving-goals?status=cancelled')->assertOk()->assertSee('Updated Goal');
        $this->actingAs($user)->delete(route('saving-goals.destroy', $goal))->assertRedirect('/saving-goals');
        $this->assertSoftDeleted('saving_goals', ['id' => $goal->id]);
    }

    public function test_contribution_increases_progress_and_reduces_available_account_balance(): void
    {
        $user = $this->user();
        $account = $this->account($user);
        $goal = $this->createGoal($user);
        $this->actingAs($user)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($account))->assertRedirect();

        $loaded = SavingGoal::withProgress()->findOrFail($goal->id);
        $this->assertSame('200.00', $loaded->progressAmount());
        $this->assertSame(40.0, $loaded->progressPercentage());
        $this->assertSame('800.00', app(AccountBalanceService::class)->calculate($account));
    }

    public function test_withdrawal_reduces_progress_returns_funds_and_rejects_excess(): void
    {
        $user = $this->user();
        $account = $this->account($user);
        $goal = $this->createGoal($user);
        $this->actingAs($user)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($account));

        $this->actingAs($user)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($account, ['type' => 'withdrawal', 'amount' => '75.00']))->assertRedirect();
        $this->assertSame('125.00', SavingGoal::withProgress()->findOrFail($goal->id)->progressAmount());
        $this->assertSame('875.00', app(AccountBalanceService::class)->calculate($account));

        $this->actingAs($user)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($account, ['type' => 'withdrawal', 'amount' => '125.01']))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('saving_goal_transactions', 2);
    }

    public function test_reaching_target_completes_goal_and_lower_progress_reactivates_it(): void
    {
        $user = $this->user();
        $account = $this->account($user);
        $goal = $this->createGoal($user);
        $this->actingAs($user)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($account, ['amount' => '500.00']));
        $this->assertSame('completed', $goal->fresh()->status);

        $this->actingAs($user)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($account, ['type' => 'withdrawal', 'amount' => '1.00']));
        $this->assertSame('active', $goal->fresh()->status);
    }

    public function test_movement_edit_and_reversal_reconcile_accounts_and_progress(): void
    {
        $user = $this->user();
        $first = $this->account($user);
        $second = $this->account($user, 'Cash', '100.00');
        $goal = $this->createGoal($user);
        $this->actingAs($user)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($first));
        $movement = $goal->transactions()->firstOrFail();

        $this->actingAs($user)->put(route('saving-goals.transactions.update', [$goal, $movement]), $this->movementPayload($second, ['amount' => '300.00']))->assertRedirect();
        $this->assertSame('300.00', SavingGoal::withProgress()->findOrFail($goal->id)->progressAmount());
        $this->assertSame('1000.00', app(AccountBalanceService::class)->calculate($first));
        $this->assertSame('-200.00', app(AccountBalanceService::class)->calculate($second));

        $this->actingAs($user)->delete(route('saving-goals.transactions.destroy', [$goal, $movement]))->assertRedirect();
        $this->assertSame('0.00', SavingGoal::withProgress()->findOrFail($goal->id)->progressAmount());
        $this->assertSame('100.00', app(AccountBalanceService::class)->calculate($second));
    }

    public function test_cross_user_goal_movement_and_account_access_are_blocked(): void
    {
        $owner = $this->user();
        $other = $this->user('other-goal@example.test');
        $goal = $this->createGoal($owner);
        $ownerAccount = $this->account($owner);
        $otherAccount = $this->account($other, 'Other Bank');
        $movement = $goal->transactions()->create($this->movementPayload($ownerAccount));

        $this->actingAs($other)->get(route('saving-goals.show', $goal))->assertForbidden();
        $this->actingAs($other)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($otherAccount))->assertForbidden();
        $this->actingAs($owner)->post(route('saving-goals.transactions.store', $goal), $this->movementPayload($otherAccount))->assertSessionHasErrors('account_id');
        $this->actingAs($other)->get(route('saving-goals.transactions.edit', [$goal, $movement]))->assertForbidden();
    }

    public function test_zero_target_saving_goal_progress_returns_zero_without_exception(): void
    {
        $user = $this->user();
        $goal = $user->savingGoals()->make([
            'name' => 'Zero Target',
            'target_amount' => '0.00',
            'status' => 'active',
        ]);
        $goal->contributions_sum = '100.00';
        $goal->withdrawals_sum = '0.00';

        $this->assertSame(0.0, $goal->progressPercentage());
    }
}
