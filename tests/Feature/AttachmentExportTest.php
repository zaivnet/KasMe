<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentExportTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        return User::create(['name' => 'Pengguna', 'email' => $email, 'password' => 'RahasiaKuat123!']);
    }

    private function account(User $user): Account
    {
        return $user->accounts()->create(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => '0.00', 'currency' => 'IDR', 'is_active' => true]);
    }

    private function payload(Account $account, array $overrides = []): array
    {
        return array_merge(['account_id' => $account->id, 'type' => 'adjustment', 'adjustment_direction' => 'increase', 'amount' => '10.00', 'transaction_date' => '2026-08-11', 'description' => 'Bukti aman'], $overrides);
    }

    public function test_attachment_can_be_uploaded_replaced_removed_and_is_privately_named(): void
    {
        Storage::fake('local');
        $user = $this->user('owner@example.test');
        $account = $this->account($user);

        $this->actingAs($user)->post(route('transactions.store'), $this->payload($account, [
            'attachment' => UploadedFile::fake()->create('bukti pribadi.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        $transaction = Transaction::firstOrFail();
        $firstPath = $transaction->attachment;
        $this->assertStringStartsWith("transactions/{$user->id}/", $firstPath);
        $this->assertStringNotContainsString('bukti pribadi', $firstPath);
        Storage::disk('local')->assertExists($firstPath);

        $this->actingAs($user)->put(route('transactions.update', $transaction), $this->payload($account, [
            'attachment' => UploadedFile::fake()->image('baru.png'),
        ]))->assertSessionHasNoErrors();
        $newPath = $transaction->fresh()->attachment;
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($newPath);

        $this->actingAs($user)->put(route('transactions.update', $transaction), $this->payload($account, ['remove_attachment' => '1']))->assertSessionHasNoErrors();
        $this->assertNull($transaction->fresh()->attachment);
        Storage::disk('local')->assertMissing($newPath);
    }

    public function test_invalid_attachment_is_rejected_and_cross_user_download_is_forbidden(): void
    {
        Storage::fake('local');
        $owner = $this->user('owner@example.test');
        $account = $this->account($owner);
        $this->actingAs($owner)->post(route('transactions.store'), $this->payload($account, [
            'attachment' => UploadedFile::fake()->create('script.php', 2, 'application/x-php'),
        ]))->assertSessionHasErrors('attachment');

        $path = UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf')->storeAs("transactions/{$owner->id}", 'acak.pdf', 'local');
        $transaction = $owner->transactions()->create($this->payload($account, ['attachment' => $path]));
        $other = $this->user('other@example.test');

        $this->actingAs($other)->get(route('transactions.attachment', $transaction))->assertForbidden();
        $this->actingAs($owner)->get(route('transactions.attachment', $transaction))->assertDownload();
    }

    public function test_csv_exports_respect_filters_and_only_contain_current_users_records(): void
    {
        $user = $this->user('owner@example.test');
        $account = $this->account($user);
        $user->transactions()->create($this->payload($account, ['description' => 'Milik sendiri']));
        $user->transactions()->create($this->payload($account, ['description' => 'Di luar filter', 'transaction_date' => '2026-07-01']));
        $other = $this->user('other@example.test');
        $other->transactions()->create($this->payload($this->account($other), ['description' => 'Rahasia pengguna lain']));

        $response = $this->actingAs($user)->get(route('transactions.export', ['date_from' => '2026-08-01', 'date_to' => '2026-08-31']));
        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Milik sendiri', $csv);
        $this->assertStringNotContainsString('Di luar filter', $csv);
        $this->assertStringNotContainsString('Rahasia pengguna lain', $csv);
    }

    public function test_json_backup_excludes_secrets_and_other_users_data(): void
    {
        $user = $this->user('owner@example.test');
        $account = $this->account($user);
        $user->transactions()->create($this->payload($account, ['description' => 'Data pemilik']));
        $other = $this->user('other@example.test');
        $other->transactions()->create($this->payload($this->account($other), ['description' => 'Data asing']));

        $response = $this->actingAs($user)->get(route('settings.export'));
        $response->assertOk()->assertDownload();
        $json = $response->streamedContent();
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('owner@example.test', $decoded['profile']['email']);
        $this->assertStringContainsString('Data pemilik', $json);
        $this->assertStringNotContainsString('Data asing', $json);
        $this->assertStringNotContainsString($user->password, $json);
        $this->assertStringNotContainsString('remember_token', $json);
        $this->assertStringNotContainsString('session', $json);
    }
}
