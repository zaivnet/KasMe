<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function user(string $email = 'settings@example.test'): User
    {
        return User::create(['name' => 'Settings User', 'email' => $email, 'password' => 'SecurePassword123!']);
    }

    public function test_settings_are_lazily_created_with_safe_defaults(): void
    {
        $user = $this->user();

        $this->get('/settings')->assertRedirect('/login');
        $this->actingAs($user)->get('/settings')->assertOk()->assertSee('Asia/Jakarta')->assertSee('Ikuti sistem');

        $this->assertDatabaseHas('settings', [
            'user_id' => $user->id,
            'currency' => 'IDR',
            'date_format' => 'd M Y',
            'timezone' => 'Asia/Jakarta',
            'theme' => 'system',
        ]);
    }

    public function test_user_can_update_only_their_own_preferences_without_mutating_financial_data(): void
    {
        $user = $this->user();
        $other = $this->user('other-settings@example.test');
        $account = $user->accounts()->create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => '1250.00', 'currency' => 'IDR', 'is_active' => true]);
        $other->setting()->create(['currency' => 'EUR', 'date_format' => 'Y-m-d', 'timezone' => 'Europe/Paris', 'theme' => 'dark']);

        $this->actingAs($user)->put('/settings', [
            'currency' => 'usd',
            'date_format' => 'd/m/Y',
            'timezone' => 'Asia/Makassar',
            'theme' => 'dark',
        ])->assertRedirect('/settings')->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['user_id' => $user->id, 'currency' => 'USD', 'date_format' => 'd/m/Y', 'timezone' => 'Asia/Makassar', 'theme' => 'dark']);
        $this->assertDatabaseHas('settings', ['user_id' => $other->id, 'currency' => 'EUR', 'timezone' => 'Europe/Paris']);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'opening_balance' => '1250.00', 'currency' => 'IDR']);

        $this->actingAs($user)->get('/settings')->assertOk()->assertSee('class="scheme-light dark:scheme-dark dark"', false);
        $this->actingAs($user)->get('/accounts/create')->assertOk()->assertSee('value="USD"', false);
    }

    public function test_date_format_is_applied_to_financial_views(): void
    {
        $user = $this->user();
        $user->setting()->create(['currency' => 'IDR', 'date_format' => 'Y-m-d', 'timezone' => 'Asia/Jakarta', 'theme' => 'light']);
        $account = $user->accounts()->create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => '0.00', 'currency' => 'IDR', 'is_active' => true]);
        $category = $user->categories()->create(['name' => 'Makanan', 'type' => 'expense', 'is_active' => true]);
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => '10.00', 'transaction_date' => '2026-08-11']);

        $this->actingAs($user)->get('/transactions')->assertOk()->assertSee('2026-08-11');
    }

    public function test_timezone_controls_dashboard_month_boundary(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 00:30:00', 'UTC'));
        $user = $this->user();
        $user->setting()->create(['currency' => 'IDR', 'date_format' => 'd M Y', 'timezone' => 'Pacific/Honolulu', 'theme' => 'system']);
        $account = $user->accounts()->create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => '0.00', 'currency' => 'IDR', 'is_active' => true]);
        $category = $user->categories()->create(['name' => 'Gaji', 'type' => 'income', 'is_active' => true]);
        $user->transactions()->create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'income', 'amount' => '500.00', 'transaction_date' => '2026-08-31']);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('periodLabel', 'Agustus 2026')->assertViewHas('income', '500.00');
    }

    public function test_invalid_preferences_are_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/settings', [
            'currency' => 'INVALID',
            'date_format' => 'unsafe',
            'timezone' => 'Invalid/Zone',
            'theme' => 'neon',
        ])->assertSessionHasErrors(['currency', 'date_format', 'timezone', 'theme']);
    }
}
