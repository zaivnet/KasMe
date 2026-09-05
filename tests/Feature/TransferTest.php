<?php

namespace Tests\Feature;

use App\Actions\Transfers\CreateTransfer;
use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use App\Services\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $source;

    private Account $destination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Transfer Owner', 'email' => 'transfer@example.test', 'password' => 'SecurePassword123!']);
        $this->source = $this->account($this->user, 'Source', '1000000.00');
        $this->destination = $this->account($this->user, 'Destination', '200000.00');
    }

    private function account(User $user, string $name, string $opening): Account
    {
        return $user->accounts()->create(['name' => $name, 'type' => 'bank', 'opening_balance' => $opening, 'currency' => 'IDR', 'is_active' => true]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['from_account_id' => $this->source->id, 'to_account_id' => $this->destination->id, 'amount' => '300000.00', 'fee' => '5000.00', 'transfer_date' => '2026-08-11', 'description' => 'Account movement'], $overrides);
    }

    public function test_required_transfer_reconciliation_is_exact(): void
    {
        $this->actingAs($this->user)->post('/transfers', $this->payload())->assertSessionHasNoErrors();
        $service = app(AccountBalanceService::class);

        $this->assertSame('695000.00', $service->calculate($this->source));
        $this->assertSame('500000.00', $service->calculate($this->destination));
        $this->assertCount(1, Transfer::all());
        $this->assertCount(0, $this->user->transactions);
    }

    public function test_edit_and_reversal_keep_both_accounts_balanced(): void
    {
        $transfer = app(CreateTransfer::class)->handle($this->user, $this->payload());
        $this->actingAs($this->user)->put(route('transfers.update', $transfer), $this->payload(['amount' => '100000.00', 'fee' => '2500.00']))->assertSessionHasNoErrors();
        $service = app(AccountBalanceService::class);
        $this->assertSame('897500.00', $service->calculate($this->source));
        $this->assertSame('300000.00', $service->calculate($this->destination));

        $this->actingAs($this->user)->delete(route('transfers.destroy', $transfer))->assertRedirect('/transfers');
        $this->assertSoftDeleted($transfer);
        $this->assertSame('1000000.00', $service->calculate($this->source));
        $this->assertSame('200000.00', $service->calculate($this->destination));
    }

    public function test_same_account_cross_user_and_invalid_amounts_are_rejected(): void
    {
        $this->actingAs($this->user)->post('/transfers', $this->payload(['to_account_id' => $this->source->id]))->assertSessionHasErrors(['from_account_id', 'to_account_id']);
        $other = User::create(['name' => 'Other', 'email' => 'other-transfer@example.test', 'password' => 'SecurePassword123!']);
        $otherAccount = $this->account($other, 'Private', '0.00');
        $this->actingAs($this->user)->post('/transfers', $this->payload(['to_account_id' => $otherAccount->id]))->assertSessionHasErrors('to_account_id');
        $this->actingAs($this->user)->post('/transfers', $this->payload(['amount' => '0']))->assertSessionHasErrors('amount');
        $this->actingAs($this->user)->post('/transfers', $this->payload(['fee' => '-1']))->assertSessionHasErrors('fee');
        $this->assertSame(0, Transfer::count());
    }

    public function test_cross_user_transfer_access_is_forbidden(): void
    {
        $transfer = app(CreateTransfer::class)->handle($this->user, $this->payload());
        $other = User::create(['name' => 'Other', 'email' => 'other-transfer@example.test', 'password' => 'SecurePassword123!']);

        $this->actingAs($other)->get(route('transfers.show', $transfer))->assertForbidden();
        $this->actingAs($other)->get(route('transfers.edit', $transfer))->assertForbidden();
        $this->actingAs($other)->put(route('transfers.update', $transfer), $this->payload())->assertForbidden();
        $this->actingAs($other)->delete(route('transfers.destroy', $transfer))->assertForbidden();
    }

    public function test_failed_atomic_action_rolls_back_without_financial_effect(): void
    {
        try {
            app(CreateTransfer::class)->handle($this->user, $this->payload(['to_account_id' => 999999]));
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException) {
            $this->assertSame(0, Transfer::count());
            $service = app(AccountBalanceService::class);
            $this->assertSame('1000000.00', $service->calculate($this->source));
            $this->assertSame('200000.00', $service->calculate($this->destination));
        }
    }
}
