<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::create(['name' => 'Category Owner', 'email' => $email, 'password' => 'SecurePassword123!']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'parent_id' => null,
            'name' => 'Category Name',
            'type' => 'expense',
            'icon' => null,
            'color' => '#047857',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_user_can_create_income_expense_and_child_categories(): void
    {
        $user = $this->user('owner@example.test');
        $this->actingAs($user)->post('/categories', $this->payload(['name' => 'Salary', 'type' => 'income']))->assertRedirect('/categories');
        $this->actingAs($user)->post('/categories', $this->payload(['name' => 'Food']))->assertRedirect('/categories');
        $parent = Category::where('name', 'Food')->firstOrFail();
        $this->actingAs($user)->post('/categories', $this->payload(['name' => 'Dining', 'parent_id' => $parent->id]))->assertRedirect('/categories');

        $child = Category::where('name', 'Dining')->firstOrFail();
        $this->assertTrue($child->parent->is($parent));
        $this->assertCount(3, $user->categories);
    }

    public function test_user_can_delete_unused_category(): void
    {
        $user = $this->user('owner@example.test');
        $category = $user->categories()->create($this->payload(['name' => 'Unused Category']));

        $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $response->assertRedirect('/categories');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_deleting_category_does_not_affect_unrelated_data(): void
    {
        $user = $this->user('owner@example.test');
        $account = $user->accounts()->create(['name' => 'Main Account', 'type' => 'bank', 'opening_balance' => '1000.00', 'currency' => 'IDR', 'is_active' => true]);
        $usedCategory = $user->categories()->create($this->payload(['name' => 'Used Category']));
        $unusedCategory = $user->categories()->create($this->payload(['name' => 'Unused Category']));
        $transaction = $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $usedCategory->id,
            'type' => 'expense',
            'amount' => '50.00',
            'transaction_date' => '2026-08-20',
            'description' => 'Test Expense',
        ]);

        $this->actingAs($user)->delete(route('categories.destroy', $unusedCategory))->assertRedirect('/categories');

        $this->assertDatabaseMissing('categories', ['id' => $unusedCategory->id]);
        $this->assertDatabaseHas('categories', ['id' => $usedCategory->id]);
        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_used_category_cannot_be_hard_deleted_and_is_archived_instead(): void
    {
        $user = $this->user('owner@example.test');
        $account = $user->accounts()->create(['name' => 'Main Account', 'type' => 'bank', 'opening_balance' => '1000.00', 'currency' => 'IDR', 'is_active' => true]);
        $category = $user->categories()->create($this->payload(['name' => 'Active Used Category']));
        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => '100.00',
            'transaction_date' => '2026-08-20',
            'description' => 'Grocery Expense',
        ]);

        $this->actingAs($user)->delete(route('categories.destroy', $category))->assertRedirect('/categories');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_used_category_history_remains_visible_and_renders_correctly(): void
    {
        $user = $this->user('owner@example.test');
        $account = $user->accounts()->create(['name' => 'Main Account', 'type' => 'bank', 'opening_balance' => '1000.00', 'currency' => 'IDR', 'is_active' => true]);
        $category = $user->categories()->create($this->payload(['name' => 'Groceries']));
        $transaction = $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => '150.00',
            'transaction_date' => '2026-08-20',
            'description' => 'Weekly Supermarket',
        ]);

        $category->update(['is_active' => false]);

        $this->actingAs($user)->get('/transactions')
            ->assertOk()
            ->assertSee('Groceries')
            ->assertSee('Weekly Supermarket');

        $this->actingAs($user)->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Groceries');
    }

    public function test_archived_category_does_not_appear_in_new_transaction_selector(): void
    {
        $user = $this->user('owner@example.test');
        $user->accounts()->create(['name' => 'Main Account', 'type' => 'bank', 'opening_balance' => '1000.00', 'currency' => 'IDR', 'is_active' => true]);
        $activeCat = $user->categories()->create($this->payload(['name' => 'Active Category']));
        $archivedCat = $user->categories()->create($this->payload(['name' => 'Archived Category', 'is_active' => false]));

        $this->actingAs($user)->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('Active Category')
            ->assertDontSee('Archived Category');
    }

    public function test_category_can_be_edited_and_reactivated(): void
    {
        $user = $this->user('owner@example.test');
        $category = $user->categories()->create($this->payload(['name' => 'Deactivated Category', 'is_active' => false]));

        $this->actingAs($user)->put(route('categories.update', $category), $this->payload([
            'name' => 'Reactivated Category',
            'is_active' => true,
        ]))->assertRedirect('/categories');

        $this->assertTrue($category->fresh()->is_active);
        $this->assertSame('Reactivated Category', $category->fresh()->name);
    }

    public function test_parent_must_have_same_owner_and_type(): void
    {
        $owner = $this->user('owner@example.test');
        $other = $this->user('other@example.test');
        $otherParent = $other->categories()->create($this->payload(['name' => 'Private Parent']));
        $incomeParent = $owner->categories()->create($this->payload(['name' => 'Income Parent', 'type' => 'income']));

        $this->actingAs($owner)->post('/categories', $this->payload(['parent_id' => $otherParent->id]))
            ->assertSessionHasErrors('parent_id');
        $this->actingAs($owner)->post('/categories', $this->payload(['parent_id' => $incomeParent->id]))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_circular_parent_relationship_is_blocked(): void
    {
        $user = $this->user('owner@example.test');
        $parent = $user->categories()->create($this->payload(['name' => 'Parent']));
        $child = $user->categories()->create($this->payload(['name' => 'Child', 'parent_id' => $parent->id]));

        $this->actingAs($user)->put(route('categories.update', $parent), $this->payload([
            'name' => 'Parent', 'parent_id' => $child->id,
        ]))->assertSessionHasErrors('parent_id');

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_cross_user_access_is_blocked_and_list_is_scoped(): void
    {
        $owner = $this->user('owner@example.test');
        $other = $this->user('other@example.test');
        $category = $owner->categories()->create($this->payload(['name' => 'Private Category']));

        $this->actingAs($other)->get(route('categories.edit', $category))->assertForbidden();
        $this->actingAs($other)->put(route('categories.update', $category), $this->payload())->assertForbidden();
        $this->actingAs($other)->delete(route('categories.destroy', $category))->assertForbidden();
        $this->actingAs($other)->get('/categories')->assertOk()->assertDontSee('Private Category');
    }

    public function test_type_and_status_filters_work(): void
    {
        $user = $this->user('owner@example.test');
        $user->categories()->create($this->payload(['name' => 'Active Expense']));
        $user->categories()->create($this->payload(['name' => 'Inactive Income', 'type' => 'income', 'is_active' => false]));

        $this->actingAs($user)->get('/categories?type=income&status=inactive')
            ->assertOk()->assertSee('Inactive Income')->assertDontSee('Active Expense');
    }
}
