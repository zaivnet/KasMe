<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function user(string $email = 'bill@example.test'): User
    {
        return User::create(['name' => 'Bill Owner', 'email' => $email, 'password' => 'SecurePassword123!']);
    }

    private function category(User $user, string $name = 'Utilities'): Category
    {
        return $user->categories()->create(['name' => $name, 'type' => 'expense', 'is_active' => true]);
    }

    private function payload(?Category $category = null, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category?->id,
            'name' => 'Electricity',
            'amount' => '350000.00',
            'due_date' => CarbonImmutable::today('Asia/Jakarta')->addDays(10)->toDateString(),
            'recurrence' => 'monthly',
            'status' => 'unpaid',
            'notes' => 'August bill',
        ], $overrides);
    }

    public function test_user_can_create_edit_filter_and_archive_bill(): void
    {
        $user = $this->user();
        $category = $this->category($user);
        $this->actingAs($user)->post('/bills', $this->payload($category))->assertRedirect('/bills');
        $bill = Bill::firstOrFail();

        $formattedDueDate = $bill->due_date->locale('id')->translatedFormat('d M Y');

        $this->actingAs($user)->get('/bills?status=unpaid&recurrence=monthly')->assertOk()
            ->assertSee('Electricity')->assertSee($formattedDueDate)->assertSee('Bulanan');
        $this->actingAs($user)->put(route('bills.update', $bill), $this->payload($category, [
            'name' => 'Internet', 'status' => 'paid',
        ]))->assertRedirect('/bills');
        $this->assertSame('Internet', $bill->fresh()->name);
        $this->assertSame('paid', $bill->fresh()->status);

        $this->actingAs($user)->delete(route('bills.destroy', $bill))->assertRedirect('/bills');
        $this->assertSoftDeleted('bills', ['id' => $bill->id]);
    }

    public function test_overdue_state_is_derived_without_mutating_stored_status(): void
    {
        $today = CarbonImmutable::today('Asia/Jakarta');
        CarbonImmutable::setTestNow($today);
        $user = $this->user();
        $bill = $user->bills()->create($this->payload(null, ['due_date' => $today->subDays(2)->toDateString()]));

        $this->actingAs($user)->get('/bills?status=overdue')->assertOk()
            ->assertSee('Electricity')->assertSee('Terlambat');
        $this->assertSame('unpaid', $bill->fresh()->status);

        $bill->update(['status' => 'paid']);
        $this->actingAs($user)->get('/bills?status=overdue')->assertOk()->assertDontSee('Electricity');
    }

    public function test_recurrence_status_amount_and_category_are_validated(): void
    {
        $user = $this->user();
        $other = $this->user('other-category@example.test');
        $foreignCategory = $this->category($other);

        $this->actingAs($user)->post('/bills', $this->payload($foreignCategory, [
            'amount' => '-1', 'recurrence' => 'daily', 'status' => 'pending',
        ]))->assertSessionHasErrors(['category_id', 'amount', 'recurrence', 'status']);
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_cross_user_access_and_list_are_blocked(): void
    {
        $owner = $this->user();
        $other = $this->user('other-bill@example.test');
        $bill = $owner->bills()->create($this->payload());

        $this->actingAs($other)->get(route('bills.edit', $bill))->assertForbidden();
        $this->actingAs($other)->put(route('bills.update', $bill), $this->payload())->assertForbidden();
        $this->actingAs($other)->delete(route('bills.destroy', $bill))->assertForbidden();
        $this->actingAs($other)->get('/bills')->assertOk()->assertDontSee('Electricity');
    }

    public function test_paid_status_does_not_create_transaction_and_dashboard_uses_real_bills(): void
    {
        $today = CarbonImmutable::today('Asia/Jakarta');
        CarbonImmutable::setTestNow($today);
        $user = $this->user();
        $bill = $user->bills()->create($this->payload(null, ['due_date' => $today->addDays(9)->toDateString()]));

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('upcomingBills', fn ($bills) => $bills->contains($bill))
            ->assertSee('Electricity');
        $this->actingAs($user)->put(route('bills.update', $bill), $this->payload(null, ['status' => 'paid']))
            ->assertRedirect('/bills');

        $this->assertDatabaseCount('transactions', 0);
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('upcomingBills', fn ($bills) => $bills->isEmpty());
    }

    public function test_empty_state_works(): void
    {
        $this->actingAs($this->user())->get('/bills')->assertOk()->assertSee('Tagihan tidak ditemukan');
    }
}
