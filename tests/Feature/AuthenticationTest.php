<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_are_available_and_app_requires_authentication(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/app')->assertRedirect('/login');
    }

    public function test_user_can_register_login_and_logout(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'user@example.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertSessionHasNoErrors()->assertRedirect('/app');

        $user = User::where('email', 'user@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'user@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => 'user@example.test',
            'password' => 'SecurePass123!',
        ])->assertRedirect('/app');
        $this->assertAuthenticatedAs($user);
        $this->get('/login')->assertRedirect('/app');
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset@example.test',
            'password' => 'OldPassword123!',
        ]);
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::create([
            'name' => 'Mail User',
            'email' => 'mail@example.test',
            'password' => 'SecurePassword123!',
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_first_registered_user_automatically_becomes_instance_owner(): void
    {
        $this->post('/register', [
            'name' => 'Owner First',
            'email' => 'owner@example.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertRedirect('/app');

        $user = User::where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue($user->is_instance_owner);
    }

    public function test_registration_closed_by_default_after_first_user_when_allow_registration_is_false(): void
    {
        // First user exists
        User::factory()->instanceOwner()->create();
        config(['kasme.allow_registration' => false]);

        // GET register should be forbidden
        $this->get('/register')->assertForbidden();

        // POST register should be forbidden
        $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@example.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.test']);
    }

    public function test_registration_allowed_after_first_user_when_allow_registration_is_true(): void
    {
        User::factory()->instanceOwner()->create();
        config(['kasme.allow_registration' => true]);

        $this->get('/register')->assertOk();

        $this->post('/register', [
            'name' => 'Second User',
            'email' => 'second@example.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertRedirect('/app');

        $secondUser = User::where('email', 'second@example.test')->firstOrFail();
        $this->assertFalse($secondUser->is_instance_owner);
    }

    public function test_malicious_user_cannot_escalate_to_instance_owner_via_registration_mass_assignment(): void
    {
        // Owner already exists
        User::factory()->instanceOwner()->create();
        config(['kasme.allow_registration' => true]);

        $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'is_instance_owner' => 1,
            'is_instance_owner' => 'true',
        ])->assertRedirect('/app');

        $hacker = User::where('email', 'hacker@example.test')->firstOrFail();
        $this->assertFalse($hacker->is_instance_owner);
    }
}
