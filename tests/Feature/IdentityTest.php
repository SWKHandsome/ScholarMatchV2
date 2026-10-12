<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_hashes_password_and_ignores_privilege_injection(): void
    {
        $this->post('/register', ['name' => 'Aina', 'email' => 'aina@example.test', 'password' => 'Student!1234', 'password_confirmation' => 'Student!1234', 'role' => 'admin', 'is_active' => false])->assertRedirect('/dashboard');
        $user = User::firstOrFail();
        $this->assertSame('student', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('Student!1234', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('student_profiles', ['user_id' => $user->id]);
    }

    public function test_registration_validates_email_confirmation_and_uniqueness(): void
    {
        $this->post('/register', ['name' => '', 'email' => 'bad', 'password' => 'short', 'password_confirmation' => 'wrong'])->assertSessionHasErrors(['name', 'email', 'password']);
        $user = User::factory()->create();
        $this->post('/register', ['name' => 'Duplicate', 'email' => $user->email, 'password' => 'Student!1234', 'password_confirmation' => 'Student!1234'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_logout_and_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'Student!1234']);
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'Student!1234'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_login_or_continue_existing_sessions(): void
    {
        $user = User::factory()->create(['is_active' => false, 'password' => 'Student!1234']);
        $this->post('/login', ['email' => $user->email, 'password' => 'Student!1234'])->assertSessionHasErrors('email');
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guests_and_students_cannot_access_administrator_functions(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertOk();
    }

    public function test_authentication_forms_are_accessible_and_login_is_rate_limited(): void
    {
        $this->get('/login')->assertOk()->assertSee('for="email"', false);
        $this->get('/register')->assertOk()->assertSee('password_confirmation');
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/login', ['email' => 'no@example.test', 'password' => 'invalid']);
        }
        $this->post('/login', ['email' => 'no@example.test', 'password' => 'invalid'])->assertStatus(429);
    }
}
