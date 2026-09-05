<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::create([
            'name' => 'Account Owner',
            'email' => $email,
            'password' => 'SecurePassword123!',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Primary Wallet',
            'type' => 'ewallet',
            'opening_balance' => '125000.50',
            'currency' => 'idr',
            'icon' => null,
            'color' => '#047857',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_guest_is_redirected_and_user_can_create_account(): void
    {
        $this->get('/accounts')->assertRedirect('/login');
        $user = $this->user('owner@example.test');

        $response = $this->actingAs($user)->post('/accounts', $this->payload());
        $account = Account::firstOrFail();

        $response->assertRedirect(route('accounts.show', $account));
        $this->assertSame($user->id, $account->user_id);
        $this->assertSame('125000.50', $account->opening_balance);
        $this->assertSame('IDR', $account->currency);
    }

    public function test_account_list_is_scoped_to_authenticated_user(): void
    {
        $owner = $this->user('owner@example.test');
        $other = $this->user('other@example.test');
        $owner->accounts()->create($this->payload(['name' => 'Visible Account']));
        $other->accounts()->create($this->payload(['name' => 'Private Account']));

        $this->actingAs($owner)->get('/accounts')
            ->assertOk()
            ->assertSee('Visible Account')
            ->assertDontSee('Private Account');
    }

    public function test_user_can_update_metadata_but_not_opening_balance(): void
    {
        $user = $this->user('owner@example.test');
        $account = $user->accounts()->create($this->payload());

        $this->actingAs($user)->put(route('accounts.update', $account), [
            'name' => 'Renamed Wallet',
            'type' => 'bank',
            'currency' => 'usd',
            'icon' => 'bank',
            'color' => '#123ABC',
            'is_active' => '0',
        ])->assertRedirect(route('accounts.show', $account));

        $account->refresh();
        $this->assertSame('Renamed Wallet', $account->name);
        $this->assertSame('125000.50', $account->opening_balance);
        $this->assertFalse($account->is_active);

        $this->actingAs($user)->put(route('accounts.update', $account), [
            'name' => 'Unsafe Change',
            'type' => 'bank',
            'currency' => 'USD',
            'is_active' => '1',
            'opening_balance' => '999999.00',
        ])->assertSessionHasErrors('opening_balance');

        $this->assertSame('125000.50', $account->fresh()->opening_balance);
    }

    public function test_user_cannot_access_or_modify_another_users_account(): void
    {
        $owner = $this->user('owner@example.test');
        $other = $this->user('other@example.test');
        $account = $owner->accounts()->create($this->payload());

        $this->actingAs($other)->get(route('accounts.show', $account))->assertForbidden();
        $this->actingAs($other)->get(route('accounts.edit', $account))->assertForbidden();
        $this->actingAs($other)->put(route('accounts.update', $account), [
            'name' => 'Stolen', 'type' => 'cash', 'currency' => 'IDR', 'is_active' => '1',
        ])->assertForbidden();
        $this->actingAs($other)->delete(route('accounts.destroy', $account))->assertForbidden();
    }

    public function test_show_uses_opening_balance_and_archive_soft_deletes_account(): void
    {
        $user = $this->user('owner@example.test');
        $account = $user->accounts()->create($this->payload());

        $this->actingAs($user)->get(route('accounts.show', $account))
            ->assertOk()
            ->assertSee('125,000.50');

        $this->actingAs($user)->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'));

        $this->assertSoftDeleted($account);
        $this->assertNull($user->accounts()->find($account->id));
    }

    public function test_account_icon_picker_has_eight_distinct_account_choices_only(): void
    {
        $this->withViewErrors([]);
        $html = Blade::render('<x-icon-picker variant="account" label="Ikon akun" />');

        $expected = [
            'wallet' => 'Dompet',
            'bank' => 'Bank',
            'card' => 'Kartu',
            'cash' => 'Tunai',
            'savings' => 'Tabungan',
            'ewallet' => 'E-Wallet',
            'investment' => 'Investasi',
            'other' => 'Lainnya',
        ];

        $this->assertSame(8, substr_count($html, 'data-icon-value='));
        foreach ($expected as $identifier => $label) {
            $this->assertStringContainsString('data-icon-value="'.$identifier.'"', $html);
            $this->assertStringContainsString($label, $html);
        }

        foreach (['category', 'bill', 'goal'] as $removedIdentifier) {
            $this->assertStringNotContainsString('data-icon-value="'.$removedIdentifier.'"', $html);
        }
    }

    public function test_category_icon_picker_keeps_its_existing_choices(): void
    {
        $this->withViewErrors([]);
        $html = Blade::render('<x-icon-picker label="Ikon kategori" />');

        $this->assertSame(8, substr_count($html, 'data-icon-value='));
        foreach (['wallet', 'bank', 'card', 'cash', 'savings', 'category', 'bill', 'goal'] as $identifier) {
            $this->assertStringContainsString('data-icon-value="'.$identifier.'"', $html);
        }
    }

    public function test_account_icon_resolver_uses_aliases_and_safe_type_fallbacks(): void
    {
        $cases = [
            ['account_balance_wallet', 'other', 'wallet'],
            ['account_balance', 'cash', 'bank'],
            ['credit_card', 'cash', 'card'],
            ['payments', 'bank', 'cash'],
            ['phone_android', 'bank', 'ewallet'],
            ['monitoring', 'bank', 'investment'],
            ['more_horiz', 'bank', 'other'],
            ['legacy-provider-icon', 'bank', 'bank'],
            ['category', 'credit_card', 'card'],
            ['0', 'ewallet', 'ewallet'],
            ['', 'cash', 'cash'],
            ['unknown', 'unknown', 'wallet'],
        ];

        foreach ($cases as [$storedIcon, $type, $expected]) {
            $html = Blade::render(
                '<x-account-icon :icon="$storedIcon" :type="$type" />',
                compact('storedIcon', 'type'),
            );

            $this->assertStringContainsString('data-account-icon="'.$expected.'"', $html);
        }
    }

    public function test_legacy_account_icon_renders_by_type_without_rewriting_stored_value(): void
    {
        $user = $this->user('legacy@example.test');
        $account = $user->accounts()->create($this->payload([
            'name' => 'Legacy Bank',
            'type' => 'bank',
            'icon' => 'legacy-provider-icon',
        ]));

        $this->actingAs($user)->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('data-account-icon="bank"', false);

        $this->assertSame('legacy-provider-icon', $account->fresh()->icon);
    }
}
