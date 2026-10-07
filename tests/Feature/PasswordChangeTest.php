<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_current_password_changes_only_the_authenticated_users_password(): void
    {
        $user = User::factory()->approved()->create(['password' => 'old-password']);
        $other = User::factory()->approved()->create();
        $otherHash = $other->password;
        $this->actingAs($user)->patch(route('profile.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'role_id' => 999,
        ])->assertRedirect(route('profile.show'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertSame($otherHash, $other->fresh()->password);
        $this->assertSame($user->role_id, $user->fresh()->role_id);
    }

    public function test_invalid_password_changes_preserve_existing_password_and_do_not_flash_passwords(): void
    {
        $user = User::factory()->approved()->create(['password' => 'old-password']);
        $originalHash = $user->password;
        $this->actingAs($user);
        foreach ([['wrong', 'new-password', 'new-password', 'current_password'],
            ['old-password', 'short', 'short', 'password'],
            ['old-password', 'new-password', 'mismatch', 'password']] as [$current, $new, $confirmation, $error]) {
            $this->patch(route('profile.password.update'), [
                'current_password' => $current,
                'password' => $new,
                'password_confirmation' => $confirmation,
            ])->assertSessionHasErrors($error)
                ->assertSessionMissing('_old_input.current_password')
                ->assertSessionMissing('_old_input.password')
                ->assertSessionMissing('_old_input.password_confirmation');
            $this->assertSame($originalHash, $user->fresh()->password);
        }
    }

    public function test_guests_cannot_change_password(): void
    {
        $this->patch(route('profile.password.update'), [])->assertRedirect(route('login'));
    }
}
