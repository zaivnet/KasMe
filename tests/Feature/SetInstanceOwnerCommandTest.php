<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetInstanceOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_no_users_exist(): void
    {
        $this->artisan('kasme:set-owner', ['--email' => 'nobody@example.test'])
            ->expectsOutput("Pengguna dengan email 'nobody@example.test' tidak ditemukan.")
            ->assertExitCode(1);
    }

    public function test_can_set_instance_owner_by_email(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.test', 'is_instance_owner' => false]);
        $user2 = User::factory()->create(['email' => 'user2@example.test', 'is_instance_owner' => true]);

        $this->artisan('kasme:set-owner', ['--email' => 'user1@example.test', '--force' => true])
            ->expectsOutput("Berhasil! Pengguna {$user1->name} <{$user1->email}> telah ditetapkan sebagai Instance Owner KasMe.")
            ->assertExitCode(0);

        $this->assertTrue($user1->fresh()->is_instance_owner);
        $this->assertFalse($user2->fresh()->is_instance_owner);
    }

    public function test_notifies_if_user_is_already_owner(): void
    {
        $owner = User::factory()->instanceOwner()->create(['email' => 'owner@example.test']);

        $this->artisan('kasme:set-owner', ['--email' => 'owner@example.test'])
            ->expectsOutput("Pengguna {$owner->name} <{$owner->email}> sudah menjadi Instance Owner.")
            ->assertExitCode(0);
    }
}
