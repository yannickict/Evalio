<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->approved()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
        ]);
    }

    public function test_guests_cannot_list_approve_or_delete_users(): void
    {
        $pending = User::factory()->create();

        $this->get(route('approve'))->assertRedirect(route('login'));
        $this->patch(route('approve.update', $pending))->assertRedirect(route('login'));
        $this->delete(route('approve.destroy', $pending))->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['id' => $pending->id, 'is_approved' => false]);
    }

    public function test_instructors_and_editors_cannot_manage_registrations(): void
    {
        $pending = User::factory()->create();

        foreach (['instructor', 'editor'] as $role) {
            $actor = User::factory()->approved()->create([
                'role_id' => Role::where('name', $role)->firstOrFail()->id,
            ]);
            $this->actingAs($actor)->get(route('approve'))->assertForbidden();
            $this->patch(route('approve.update', $pending))->assertForbidden();
            $this->delete(route('approve.destroy', $pending))->assertForbidden();
        }

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'is_approved' => false]);
    }

    public function test_admin_sees_only_pending_users_in_registration_order(): void
    {
        $newer = User::factory()->create(['created_at' => now()]);
        $older = User::factory()->create(['created_at' => now()->subDay()]);
        $approved = User::factory()->approved()->create();

        $this->actingAs($this->admin())->get(route('approve'))
            ->assertOk()
            ->assertSeeInOrder([$older->email, $newer->email])
            ->assertDontSee($approved->email)
            ->assertSee(route('approve.update', $older), false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_admin_sees_empty_state(): void
    {
        $this->actingAs($this->admin())->get(route('approve'))
            ->assertOk()->assertSee('No users waiting for approval.');
    }

    public function test_admin_can_approve_user_and_the_user_can_then_log_in(): void
    {
        $pending = User::factory()->create();
        $originalRole = $pending->role_id;

        $this->actingAs($this->admin())->patch(route('approve.update', $pending))
            ->assertRedirect(route('approve'))->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'id' => $pending->id,
            'is_approved' => true,
            'role_id' => $originalRole,
        ]);
        $this->get(route('approve'))->assertDontSee($pending->email);

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $pending->email, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($pending);
    }

    public function test_admin_can_delete_pending_user(): void
    {
        $pending = User::factory()->create();

        $this->actingAs($this->admin())->delete(route('approve.destroy', $pending))
            ->assertRedirect(route('approve'))->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['id' => $pending->id]);
    }

    public function test_already_approved_users_and_admin_cannot_be_modified(): void
    {
        $admin = $this->admin();
        $approved = User::factory()->approved()->create();
        $this->actingAs($admin);

        foreach ([$admin, $approved] as $user) {
            $this->patch(route('approve.update', $user))->assertForbidden();
            $this->delete(route('approve.destroy', $user))->assertForbidden();
            $this->assertDatabaseHas('users', ['id' => $user->id, 'is_approved' => true]);
        }
    }

    public function test_missing_users_return_not_found(): void
    {
        $this->actingAs($this->admin());
        $this->patch(route('approve.update', 999999))->assertNotFound();
        $this->delete(route('approve.destroy', 999999))->assertNotFound();
    }

    public function test_get_requests_cannot_approve_or_delete_users(): void
    {
        $pending = User::factory()->create();
        $this->actingAs($this->admin())->get('/approve/'.$pending->id)->assertStatus(405);
        $this->post('/approve/'.$pending->id)->assertStatus(405);
        $this->assertDatabaseHas('users', ['id' => $pending->id, 'is_approved' => false]);
    }
}
