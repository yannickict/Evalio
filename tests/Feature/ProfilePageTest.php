<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_changes_only_personal_details_of_the_signed_in_user(): void
    {
        $user = User::factory()->approved()->create();
        $other = User::factory()->approved()->create();
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role_id' => 999,
            'password' => 'unexpected-password',
        ])->assertRedirect(route('profile.show'))->assertSessionHasNoErrors();

        $updated = $user->fresh();
        $this->assertSame('Updated Name', $updated->name);
        $this->assertSame('updated@example.com', $updated->email);
        $this->assertSame($user->role_id, $updated->role_id);
        $this->assertSame($user->password, $updated->password);
        $this->assertSame($other->email, $other->fresh()->email);
    }

    public function test_duplicate_email_is_rejected_and_draft_is_shown_again(): void
    {
        $this->withoutVite();
        $user = User::factory()->approved()->create();
        $other = User::factory()->approved()->create();
        $this->actingAs($user)->from(route('profile.show'))->followingRedirects()
            ->patch(route('profile.update'), ['name' => 'Draft Name', 'email' => $other->email])
            ->assertOk()->assertSee('Draft Name')->assertSee('aria-expanded="true"', false);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_unchanged_email_is_allowed_and_guests_cannot_update(): void
    {
        $this->patch(route('profile.update'), [])->assertRedirect(route('login'));
        $user = User::factory()->approved()->create();
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email,
        ])->assertSessionHasNoErrors();
    }

    public function test_guest_cannot_view_profile(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_profile_shows_only_the_signed_in_users_details_and_navigation_link(): void
    {
        $this->withoutVite();
        $user = User::factory()->approved()->create();
        $other = User::factory()->approved()->create();
        $this->actingAs($user)->get(route('profile.show'))->assertOk()
            ->assertSee($user->name)->assertSee($user->email)
            ->assertDontSee($other->email)->assertSee(route('profile.show'), false);
    }
}
