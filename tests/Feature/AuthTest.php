<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_can_view_both_auth_forms(): void
    {
        $this->get('/login')->assertOk()->assertSee('Remember me');
        $this->get('/register')->assertOk()->assertSee('Confirm password');
    }

    public function test_registration_creates_a_pending_instructor_with_a_hashed_password(): void
    {
        $this->post('/register', $this->registrationData())
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'alex@example.com')->firstOrFail();

        $this->assertSame('Alex', $user->first_name);
        $this->assertSame('Example', $user->last_name);
        $this->assertSame('instructor', $user->role->name);
        $this->assertFalse($user->is_approved);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertNotSame('secure-password', $user->password);
        $this->assertGuest();
    }

    public function test_registration_cannot_set_approval_or_admin_role(): void
    {
        $this->post('/register', array_merge($this->registrationData(), [
            'is_approved' => true,
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
        ]))->assertSessionHasNoErrors();

        $user = User::where('email', 'alex@example.com')->firstOrFail();

        $this->assertFalse($user->is_approved);
        $this->assertSame('instructor', $user->role->name);
    }

    #[DataProvider('invalidRegistrationData')]
    public function test_registration_rejects_invalid_input(string $field, string $value, string $error): void
    {
        $this->from('/register')->post('/register', array_replace(
            $this->registrationData(),
            [$field => $value],
        ))->assertRedirect('/register')->assertSessionHasErrors($error);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    /** @return array<string, array{string, string, string}> */
    public static function invalidRegistrationData(): array
    {
        return [
            'missing first name' => ['first_name', '', 'first_name'],
            'missing last name' => ['last_name', '', 'last_name'],
            'invalid email' => ['email', 'not-an-email', 'email'],
            'short password' => ['password', 'short', 'password'],
            'password mismatch' => ['password_confirmation', 'different-password', 'password'],
        ];
    }

    public function test_registration_rejects_duplicate_emails(): void
    {
        User::factory()->create(['email' => 'alex@example.com']);

        $this->post('/register', $this->registrationData())->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_approved_user_can_log_in_without_remembering(): void
    {
        $user = User::factory()->approved()->create(['remember_token' => null]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/')
            ->assertSessionHasNoErrors()
            ->assertCookieMissing($this->webGuard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_login_returns_user_to_the_requested_protected_page(): void
    {
        $user = User::factory()->approved()->create();

        $this->get('/feedback')->assertRedirect(route('login'));

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/feedback');

        $this->assertAuthenticatedAs($user);
        $this->get('/feedback')->assertOk();
    }

    public function test_wrong_password_is_rejected_and_not_flashed_to_session(): void
    {
        $user = User::factory()->approved()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionHas('_old_input.email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->post('/login', ['email' => 'unknown@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_pending_user_cannot_log_in_even_with_correct_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertSessionHasErrors('email')
            ->assertCookieMissing($this->webGuard()->getRecallerName());

        $this->assertGuest();
    }

    public function test_login_requires_valid_email_and_password(): void
    {
        $this->post('/login', ['email' => 'invalid', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_remember_me_creates_a_token_and_restores_login_without_a_session(): void
    {
        $user = User::factory()->approved()->create(['remember_token' => null]);
        $cookieName = $this->webGuard()->getRecallerName();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertRedirect('/')->assertCookie($cookieName);
        $this->assertNotEmpty($user->fresh()->remember_token);

        $cookie = $response->getCookie($cookieName);
        $this->assertNotNull($cookie);
        $this->app['session']->flush();
        Auth::forgetGuards();

        $this->withCookie($cookieName, $cookie->getValue())->get('/feedback')->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($this->webGuard()->viaRemember());
    }

    public function test_login_requests_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => 'unknown@example.com', 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => 'unknown@example.com', 'password' => 'wrong'])
            ->assertStatus(429);

        $this->assertGuest();
    }

    public function test_logout_clears_session_and_invalidates_remember_cookie(): void
    {
        $user = User::factory()->approved()->create();
        $cookieName = $this->webGuard()->getRecallerName();
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);
        $cookie = $response->getCookie($cookieName);
        $this->assertNotNull($cookie);
        $oldToken = $user->fresh()->remember_token;

        $this->withSession(['private_data' => 'example'])->post('/logout')
            ->assertRedirect(route('login'))
            ->assertSessionMissing('private_data');

        $this->assertGuest();
        $this->assertNotSame($oldToken, $user->fresh()->remember_token);

        Auth::forgetGuards();
        $this->withCookie($cookieName, $cookie->getValue())->get('/feedback')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guests_cannot_access_protected_routes(): void
    {
        $this->get('/feedback')->assertRedirect(route('login'));
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_logout_requires_post(): void
    {
        $user = User::factory()->approved()->create();

        $this->actingAs($user)->get('/logout')->assertStatus(405);

        $this->assertAuthenticatedAs($user);
    }

    private function webGuard(): SessionGuard
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');

        return $guard;
    }

    /** @return array<string, string> */
    private function registrationData(): array
    {
        return [
            'first_name' => 'Alex',
            'last_name' => 'Example',
            'email' => 'alex@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ];
    }
}
