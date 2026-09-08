<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->instanceOwner()->create([
            'name' => 'Instance Owner',
            'email' => 'owner@example.test',
        ]);

        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.test',
        ]);
    }

    public function test_only_instance_owner_can_access_user_management_index(): void
    {
        // Unauthenticated
        $this->get(route('settings.users.index'))->assertRedirect('/login');

        // Regular user forbidden
        $this->actingAs($this->regularUser)
            ->get(route('settings.users.index'))
            ->assertForbidden();

        // Instance Owner authorized
        $this->actingAs($this->owner)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee($this->regularUser->email);
    }

    public function test_non_instance_owner_cannot_access_create_user_page(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('settings.users.create'))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->get(route('settings.users.create'))
            ->assertOk()
            ->assertSee('Tambah Pengguna Baru');
    }

    public function test_non_instance_owner_cannot_store_new_user(): void
    {
        $this->actingAs($this->regularUser)
            ->post(route('settings.users.store'), [
                'name' => 'Unauthorized New User',
                'email' => 'unauth@example.test',
                'password' => 'Password123!',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauth@example.test']);
    }

    public function test_non_instance_owner_cannot_access_edit_user_page(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('settings.users.edit', $this->owner))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->get(route('settings.users.edit', $this->regularUser))
            ->assertOk()
            ->assertSee('Edit Data Pengguna');
    }

    public function test_non_instance_owner_cannot_update_user(): void
    {
        $this->actingAs($this->regularUser)
            ->put(route('settings.users.update', $this->owner), [
                'name' => 'Hacked Name',
                'email' => 'owner@example.test',
            ])
            ->assertForbidden();

        $this->assertEquals('Instance Owner', $this->owner->fresh()->name);
    }

    public function test_non_instance_owner_cannot_delete_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->regularUser)
            ->delete(route('settings.users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_non_instance_owner_cannot_reset_password(): void
    {
        $this->actingAs($this->regularUser)
            ->post(route('settings.users.reset-password', $this->owner), [
                'password' => 'NewPassword123!',
            ])
            ->assertForbidden();
    }

    public function test_instance_owner_can_create_new_user(): void
    {
        $this->actingAs($this->owner)
            ->post(route('settings.users.store'), [
                'name' => 'Sub User',
                'email' => 'subuser@example.test',
                'password' => 'SecurePass123!',
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('status');

        $created = User::where('email', 'subuser@example.test')->firstOrFail();
        $this->assertEquals('Sub User', $created->name);
        $this->assertTrue(Hash::check('SecurePass123!', $created->password));
        $this->assertFalse($created->is_instance_owner);
        $this->assertTrue($created->is_active);
    }

    public function test_new_user_creation_strictly_prevents_privilege_escalation(): void
    {
        $this->actingAs($this->owner)
            ->post(route('settings.users.store'), [
                'name' => 'Attempted Owner',
                'email' => 'fakeowner@example.test',
                'password' => 'SecurePass123!',
                'is_instance_owner' => '1',
                'is_instance_owner' => 1,
            ])
            ->assertRedirect(route('settings.users.index'));

        $created = User::where('email', 'fakeowner@example.test')->firstOrFail();
        $this->assertFalse($created->is_instance_owner);
    }

    public function test_instance_owner_can_update_user_details(): void
    {
        $this->actingAs($this->owner)
            ->put(route('settings.users.update', $this->regularUser), [
                'name' => 'Updated Regular Name',
                'email' => 'updated_regular@example.test',
                'is_active' => '0',
            ])
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('status');

        $refreshed = $this->regularUser->fresh();
        $this->assertEquals('Updated Regular Name', $refreshed->name);
        $this->assertEquals('updated_regular@example.test', $refreshed->email);
        $this->assertFalse($refreshed->is_active);
    }

    public function test_instance_owner_account_status_cannot_be_deactivated(): void
    {
        $this->actingAs($this->owner)
            ->put(route('settings.users.update', $this->owner), [
                'name' => 'Instance Owner Updated',
                'email' => 'owner@example.test',
                'is_active' => '0',
            ])
            ->assertRedirect(route('settings.users.index'));

        $this->assertTrue($this->owner->fresh()->is_active);
        $this->assertTrue($this->owner->fresh()->is_instance_owner);
    }

    public function test_user_with_financial_data_is_deactivated_instead_of_deleted(): void
    {
        // Give regular user financial data (an account)
        $this->regularUser->accounts()->create([
            'name' => 'Bank BCA',
            'type' => 'bank',
            'opening_balance' => 100000,
            'current_balance' => 100000,
            'currency' => 'IDR',
            'is_active' => true,
        ]);

        $this->assertTrue($this->regularUser->hasFinancialData());

        $this->actingAs($this->owner)
            ->delete(route('settings.users.destroy', $this->regularUser))
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('status');

        // User MUST NOT be deleted from database
        $this->assertDatabaseHas('users', ['id' => $this->regularUser->id]);

        // User MUST be deactivated
        $this->assertFalse($this->regularUser->fresh()->is_active);
    }

    public function test_user_without_financial_data_is_permanently_deleted(): void
    {
        $freshUser = User::factory()->create([
            'name' => 'No Data User',
            'email' => 'nodata@example.test',
        ]);

        $this->assertFalse($freshUser->hasFinancialData());

        $this->actingAs($this->owner)
            ->delete(route('settings.users.destroy', $freshUser))
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $freshUser->id]);
    }

    public function test_instance_owner_cannot_delete_self(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('settings.users.destroy', $this->owner))
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
        $this->assertTrue($this->owner->fresh()->is_active);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $inactiveUser = User::factory()->inactive()->create([
            'email' => 'inactive@example.test',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->post('/login', [
            'email' => 'inactive@example.test',
            'password' => 'Secret123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_user_can_login_normally(): void
    {
        $activeUser = User::factory()->create([
            'email' => 'active@example.test',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => 'active@example.test',
            'password' => 'Secret123!',
        ])->assertRedirect('/app');

        $this->assertAuthenticatedAs($activeUser);
    }

    public function test_active_session_is_terminated_if_user_is_deactivated_mid_session(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // First request when active succeeds
        $this->get(route('dashboard'))->assertOk();

        // User is deactivated in database
        $user->is_active = false;
        $user->save();

        // Next request through ApplyUserSettings middleware evicts session
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_instance_owner_can_reset_user_password(): void
    {
        $this->actingAs($this->owner)
            ->post(route('settings.users.reset-password', $this->regularUser), [
                'password' => 'BrandNewPassword123!',
            ])
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('BrandNewPassword123!', $this->regularUser->fresh()->password));

        // User can now log in with the new password
        $this->post('/logout');
        $this->post('/login', [
            'email' => $this->regularUser->email,
            'password' => 'BrandNewPassword123!',
        ])->assertRedirect('/app');

        $this->assertAuthenticatedAs($this->regularUser);
    }

    public function test_strict_user_data_isolation_between_accounts(): void
    {
        // Account for Owner
        $ownerAccount = $this->owner->accounts()->create([
            'name' => 'Owner Private Vault',
            'type' => 'bank',
            'opening_balance' => 50000000,
            'current_balance' => 50000000,
            'currency' => 'IDR',
            'is_active' => true,
        ]);

        // Account for Regular User
        $userAccount = $this->regularUser->accounts()->create([
            'name' => 'User Personal Wallet',
            'type' => 'cash',
            'opening_balance' => 200000,
            'current_balance' => 200000,
            'currency' => 'IDR',
            'is_active' => true,
        ]);

        // Regular user views /accounts
        $this->actingAs($this->regularUser)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('User Personal Wallet')
            ->assertDontSee('Owner Private Vault');

        // Regular user cannot view or edit Owner's account
        $this->actingAs($this->regularUser)
            ->get(route('accounts.edit', $ownerAccount))
            ->assertForbidden();

        // Regular user cannot delete Owner's account
        $this->actingAs($this->regularUser)
            ->delete(route('accounts.destroy', $ownerAccount))
            ->assertForbidden();

        // Owner's account balance and record remain untouched
        $this->assertDatabaseHas('accounts', [
            'id' => $ownerAccount->id,
            'name' => 'Owner Private Vault',
        ]);
    }
}
