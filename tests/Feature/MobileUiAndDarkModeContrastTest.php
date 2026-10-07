<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileUiAndDarkModeContrastTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Mobile UI Test User',
            'email' => 'mobileui@example.test',
            'password' => 'SecurePassword123!',
        ]);
    }

    public function test_fab_is_rendered_on_browsing_routes(): void
    {
        $user = $this->user();

        // Dashboard
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('id="mobile-fab"', false);
        $response->assertSee('title="Tambah transaksi (T)"', false);

        // Transactions index
        $response = $this->actingAs($user)->get('/transactions');
        $response->assertOk();
        $response->assertSee('id="mobile-fab"', false);
    }

    public function test_fab_is_hidden_on_transaction_create_and_edit(): void
    {
        $user = $this->user();
        $account = $user->accounts()->create([
            'name' => 'Main Account',
            'type' => 'bank',
            'opening_balance' => '1000000.00',
            'currency' => 'IDR',
            'is_active' => true,
        ]);
        $category = $user->categories()->create([
            'name' => 'General',
            'type' => 'expense',
            'color' => '#ef4444',
            'icon' => 'tag',
            'is_active' => true,
        ]);
        $transaction = $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => '50000.00',
            'transaction_date' => '2026-08-15',
            'description' => 'Test Transaction',
        ]);

        // Transactions Create
        $response = $this->actingAs($user)->get('/transactions/create');
        $response->assertOk();
        $response->assertDontSee('id="mobile-fab"', false);

        // Transactions Edit
        $response = $this->actingAs($user)->get("/transactions/{$transaction->id}/edit");
        $response->assertOk();
        $response->assertDontSee('id="mobile-fab"', false);

        // Transfers Create
        $response = $this->actingAs($user)->get('/transfers/create');
        $response->assertOk();
        $response->assertDontSee('id="mobile-fab"', false);
    }

    public function test_main_content_has_mobile_safe_clearance_layout(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->get('/transactions/create');
        $response->assertOk();

        // Main content must use mobile-safe without p-4/sm:p-6 overriding padding-bottom
        $response->assertSee('id="main-content"', false);
        $response->assertSee('class="mobile-safe px-4 pt-4 sm:px-6 sm:pt-6 lg:p-8"', false);
    }

    public function test_form_actions_have_responsive_full_width_touch_targets(): void
    {
        $user = $this->user();
        $account = $user->accounts()->create([
            'name' => 'Main Account',
            'type' => 'bank',
            'opening_balance' => '1000000.00',
            'currency' => 'IDR',
            'is_active' => true,
        ]);
        $category = $user->categories()->create([
            'name' => 'General',
            'type' => 'expense',
            'color' => '#ef4444',
            'icon' => 'tag',
            'is_active' => true,
        ]);
        $transaction = $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => '50000.00',
            'transaction_date' => '2026-08-15',
            'description' => 'Test Transaction',
        ]);

        // Create form submit button
        $response = $this->actingAs($user)->get('/transactions/create');
        $response->assertSee('btn-primary mt-6 w-full sm:w-auto');

        // Edit form submit button
        $response = $this->actingAs($user)->get("/transactions/{$transaction->id}/edit");
        $response->assertSee('btn-primary mt-6 w-full sm:w-auto');
    }

    public function test_app_css_contains_mobile_safe_variables_and_dark_contrast_tokens(): void
    {
        $cssContent = file_get_contents(resource_path('css/app.css'));

        // Mobile clearance calculations
        $this->assertStringContainsString('--mobile-nav-height: 4.5rem;', $cssContent);
        $this->assertStringContainsString('var(--mobile-nav-height', $cssContent);
        $this->assertStringContainsString('env(safe-area-inset-bottom', $cssContent);

        // Dark mode gradient reset & deep tint badges
        $this->assertStringContainsString('.icon-badge-cyan', $cssContent);
        $this->assertStringContainsString('.icon-badge-emerald', $cssContent);
        $this->assertStringContainsString('.icon-badge-rose', $cssContent);
        $this->assertStringContainsString('.icon-badge-violet', $cssContent);
        $this->assertStringContainsString('dark:bg-none', $cssContent);
        $this->assertStringContainsString('dark:bg-emerald-950/60', $cssContent);
        $this->assertStringContainsString('dark:text-emerald-400', $cssContent);
        $this->assertStringContainsString('dark:bg-rose-950/60', $cssContent);
        $this->assertStringContainsString('dark:text-rose-400', $cssContent);
    }

    public function test_transaction_form_category_retention_and_user_isolation(): void
    {
        $user = $this->user();
        $stranger = User::create([
            'name' => 'Stranger User',
            'email' => 'stranger@example.test',
            'password' => 'SecurePassword123!',
        ]);

        $account = $user->accounts()->create([
            'name' => 'Main Account',
            'type' => 'bank',
            'opening_balance' => '1000000.00',
            'currency' => 'IDR',
            'is_active' => true,
        ]);

        $activeCat = $user->categories()->create([
            'name' => 'Active Food',
            'type' => 'expense',
            'color' => '#10b981',
            'icon' => 'tag',
            'is_active' => true,
        ]);

        $inactiveCat = $user->categories()->create([
            'name' => 'Old Inactive Vehicle',
            'type' => 'expense',
            'color' => '#64748b',
            'icon' => 'tag',
            'is_active' => false,
        ]);

        $strangerCat = $stranger->categories()->create([
            'name' => 'Stranger Secret Category',
            'type' => 'expense',
            'color' => '#ef4444',
            'icon' => 'tag',
            'is_active' => true,
        ]);

        // 1. Create transaction form: shows active category, hides inactive and stranger categories
        $createResponse = $this->actingAs($user)->get('/transactions/create');
        $createResponse->assertOk();
        $createResponse->assertSee('Active Food');
        $createResponse->assertDontSee('Old Inactive Vehicle');
        $createResponse->assertDontSee('Stranger Secret Category');

        // 2. Edit transaction with active category: shows active, hides stranger category
        $txWithActive = $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $activeCat->id,
            'type' => 'expense',
            'amount' => '25000.00',
            'transaction_date' => '2026-08-15',
            'description' => 'Active Category Tx',
        ]);

        $editActiveResponse = $this->actingAs($user)->get("/transactions/{$txWithActive->id}/edit");
        $editActiveResponse->assertOk();
        $editActiveResponse->assertSee('Active Food');
        $editActiveResponse->assertDontSee('Old Inactive Vehicle');
        $editActiveResponse->assertDontSee('Stranger Secret Category');

        // 3. Edit transaction with old inactive category: retains old inactive category, hides stranger category
        $txWithInactive = $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $inactiveCat->id,
            'type' => 'expense',
            'amount' => '75000.00',
            'transaction_date' => '2026-08-10',
            'description' => 'Inactive Category Tx',
        ]);

        $editInactiveResponse = $this->actingAs($user)->get("/transactions/{$txWithInactive->id}/edit");
        $editInactiveResponse->assertOk();
        $editInactiveResponse->assertSee('Old Inactive Vehicle');
        $editInactiveResponse->assertSee('Active Food');
        $editInactiveResponse->assertDontSee('Stranger Secret Category');
        $editInactiveResponse->assertViewHas('categories', fn ($categories) =>
            $categories->contains('id', $inactiveCat->id) &&
            $categories->contains('id', $activeCat->id) &&
            ! $categories->contains('id', $strangerCat->id)
        );
    }
}
