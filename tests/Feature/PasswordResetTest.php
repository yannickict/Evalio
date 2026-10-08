<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_forms_include_reset_fields_and_login_navigation(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false);
        $this->get(route('password.request'))->assertOk()
            ->assertSee(route('password.email'), false)->assertSee('name="_token"', false);
        $this->get(route('password.reset', ['token' => 'sample-token', 'email' => 'user@example.com']))
            ->assertOk()->assertSee(route('password.update'), false)
            ->assertSee('value="sample-token"', false)->assertSee('value="user@example.com"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_existing_and_unknown_emails_receive_the_same_confirmation(): void
    {
        Notification::fake();
        Log::spy();
        $user = User::factory()->approved()->create();
        $message = 'If an account exists, we have sent a password reset link.';

        foreach ([$user->email, 'missing@example.com'] as $email) {
            $this->from(route('password.request'))->post(route('password.email'), ['email' => $email])
                ->assertRedirect(route('password.request'))->assertSessionHasNoErrors()
                ->assertSessionHas('status', $message);
        }

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertCount(1);
        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->get(route('password.request'))->assertSee($message);
        Log::shouldHaveReceived('info')->with('Password reset link request processed.', ['status' => Password::ResetLinkSent])->once();
        Log::shouldHaveReceived('info')->with('Password reset link request processed.', ['status' => Password::InvalidUser])->once();
    }

    public function test_repeated_link_requests_are_throttled_without_resending(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        foreach ([1, 2] as $attempt) {
            $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
        }
        Notification::assertCount(1);
    }

    public function test_successful_reset_changes_password_revokes_token_and_returns_to_login(): void
    {
        Event::fake([PasswordReset::class]);
        Log::spy();
        $user = User::factory()->approved()->create(['password' => 'old-password', 'remember_token' => 'old-token']);
        $token = Password::createToken($user);

        $this->post(route('password.update'), $this->resetData($user, $token))
            ->assertRedirect(route('login'))->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Your password has been reset. You can now log in.');

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('new-password', $fresh->password));
        $this->assertNotSame('old-token', $fresh->remember_token);
        $this->assertTrue($fresh->is_approved);
        $this->assertSame($user->role_id, $fresh->role_id);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertGuest();
        Event::assertDispatched(PasswordReset::class, fn ($event) => $event->user->is($user));
        Log::shouldHaveReceived('info')->with('Password reset attempt processed.', ['status' => Password::PasswordReset])->once();
        $this->get(route('login'))->assertSee('Your password has been reset.');

        $this->post(route('password.update'), $this->resetData($user, $token))->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'old-password'])->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_does_not_approve_a_pending_account(): void
    {
        $user = User::factory()->create();
        $this->post(route('password.update'), $this->resetData($user, Password::createToken($user)))
            ->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_approved);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_invalid_expired_and_wrong_account_tokens_do_not_change_passwords(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $hash = $user->password;
        $otherHash = $other->password;
        $token = Password::createToken($user);
        $this->post(route('password.update'), $this->resetData($user, 'invalid-token'))
            ->assertSessionHasErrors('email');
        $this->post(route('password.update'), $this->resetData($other, $token))
            ->assertSessionHasErrors('email');
        $this->travel(61)->minutes();
        $this->post(route('password.update'), $this->resetData($user, $token))
            ->assertSessionHasErrors('email');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame($otherHash, $other->fresh()->password);
    }

    /** @return array<string, array{string, string, string}> */
    public static function invalidResetFields(): array
    {
        return [
            'missing token' => ['token', '', 'token'],
            'invalid email' => ['email', 'invalid', 'email'],
            'short password' => ['password', 'short', 'password'],
            'mismatched confirmation' => ['password_confirmation', 'different', 'password'],
        ];
    }

    #[DataProvider('invalidResetFields')]
    public function test_invalid_input_preserves_password_and_does_not_flash_passwords(string $field, string $value, string $error): void
    {
        $user = User::factory()->create();
        $data = $this->resetData($user, Password::createToken($user));
        $data[$field] = $value;
        $this->post(route('password.update'), $data)->assertSessionHasErrors($error)
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $this->assertSame($user->password, $user->fresh()->password);
    }

    public function test_request_email_is_validated_and_both_post_routes_are_rate_limited(): void
    {
        $this->post(route('password.email'), ['email' => 'invalid'])->assertSessionHasErrors('email');
        foreach (['password.email', 'password.update'] as $route) {
            $this->app['cache']->flush();
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $this->post(route($route), [])->assertSessionHasErrors();
            }
            $this->post(route($route), [])->assertStatus(429);
        }
    }

    public function test_authenticated_users_are_redirected_from_reset_routes(): void
    {
        $this->actingAs(User::factory()->approved()->create());
        $this->get(route('password.request'))->assertRedirect();
        $this->get(route('password.reset', ['token' => 'sample']))->assertRedirect();
        $this->post(route('password.email'), [])->assertRedirect();
        $this->post(route('password.update'), [])->assertRedirect();
    }

    /** @return array<string, string> */
    private function resetData(User $user, string $token): array
    {
        return ['email' => $user->email, 'token' => $token, 'password' => 'new-password', 'password_confirmation' => 'new-password'];
    }
}
