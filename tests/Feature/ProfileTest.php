<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Profile User',
            'email' => 'profile@example.test',
            'password' => 'CurrentPassword123!',
        ]);
    }

    public function test_profile_requires_authentication_and_can_be_updated(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $user = $this->user();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.test',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('Updated Name', $user->fresh()->name);
        $this->assertSame('updated@example.test', $user->fresh()->email);
    }

    public function test_password_update_requires_the_current_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'incorrect',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasErrors('current_password', errorBag: 'updatePassword');

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
