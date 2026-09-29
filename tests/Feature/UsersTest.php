<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UsersTest extends TestCase
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

        $this->get(route('users'))->assertRedirect(route('login'));
        $this->patch(route('users.update', $pending))->assertRedirect(route('login'));
        $this->delete(route('users.destroy', $pending))->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['id' => $pending->id, 'is_approved' => false]);
    }

    public function test_instructors_and_editors_cannot_manage_registrations(): void
    {
        $pending = User::factory()->create();

        foreach (['instructor', 'editor'] as $role) {
            $actor = User::factory()->approved()->create([
                'role_id' => Role::where('name', $role)->firstOrFail()->id,
            ]);
            $this->actingAs($actor)->get(route('users'))->assertForbidden();
            $this->patch(route('users.update', $pending), [
                'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            ])->assertForbidden();
            $this->delete(route('users.destroy', $pending))->assertForbidden();
        }

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'is_approved' => false]);
    }

    public function test_admin_sees_only_pending_users_in_registration_order(): void
    {
        $newer = User::factory()->create(['created_at' => now()]);
        $older = User::factory()->create(['created_at' => now()->subDay()]);
        $approved = User::factory()->approved()->create();

        $this->actingAs($this->admin())->get(route('users'))
            ->assertOk()
            ->assertViewIs('users')
            ->assertSee('<title>Users - Feedback</title>', false)
            ->assertSeeInOrder(['Users', 'Approve people'])
            ->assertSeeInOrder([$older->email, $newer->email])
            ->assertDontSee($approved->email)
            ->assertSee(route('users.update', $older), false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_admin_sees_empty_state(): void
    {
        $this->actingAs($this->admin())->get(route('users'))
            ->assertOk()->assertSee('No users waiting for approval.');
    }

    #[DataProvider('assignableRoles')]
    public function test_admin_can_approve_user_and_the_user_can_then_log_in(string $roleName): void
    {
        $pending = User::factory()->create();
        $role = Role::where('name', $roleName)->firstOrFail();

        $this->actingAs($this->admin())->patch(route('users.update', $pending), ['role_id' => $role->id])
            ->assertRedirect(route('users'))->assertSessionHasNoErrors()->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'id' => $pending->id,
            'is_approved' => true,
            'role_id' => $role->id,
        ]);
        $this->get(route('users'))->assertDontSee($pending->email);

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $pending->email, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($pending);
    }

    /** @return array<string, array{string}> */
    public static function assignableRoles(): array
    {
        return [
            'instructor' => ['instructor'],
            'editor' => ['editor'],
            'admin' => ['admin'],
        ];
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidRoles(): array
    {
        return [
            'missing' => [[]],
            'empty' => [['role_id' => '']],
            'unknown' => [['role_id' => 999999]],
            'name instead of id' => [['role_id' => 'admin']],
            'array' => [['role_id' => [1]]],
        ];
    }

    #[DataProvider('invalidRoles')]
    public function test_invalid_role_does_not_approve_or_modify_user(array $data): void
    {
        $pending = User::factory()->create();

        $this->actingAs($this->admin())->from(route('users'))
            ->patch(route('users.update', $pending), $data)
            ->assertRedirect(route('users'))->assertSessionHasErrors('role_id');

        $this->assertDatabaseHas('users', [
            'id' => $pending->id,
            'is_approved' => false,
            'role_id' => $pending->role_id,
        ]);
    }

    public function test_each_pending_user_has_a_matching_modal_and_role_form(): void
    {
        $pending = User::factory()->count(2)->create();
        $response = $this->actingAs($this->admin())->get(route('users'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach ($pending as $user) {
            $id = 'approve-user-'.$user->id;
            $this->assertCount(1, $xpath->query('//button[@data-bs-toggle="modal" and @data-bs-target="#'.$id.'"]'));
            $this->assertCount(1, $xpath->query('//*[@id="'.$id.'"]'));
            $form = $xpath->query('//*[@id="'.$id.'"]//form')->item(0);
            $this->assertNotNull($form);
            $this->assertSame(route('users.update', $user), $form->getAttribute('action'));
            $this->assertSame('POST', $form->getAttribute('method'));
            $this->assertCount(1, $xpath->query('.//input[@name="_method" and @value="PATCH"]', $form));
            $this->assertCount(1, $xpath->query('.//input[@name="_token"]', $form));
            $this->assertCount(0, $xpath->query('.//form', $form));
            $select = $xpath->query('.//select[@name="role_id"]', $form)->item(0);
            $this->assertNotNull($select);
            $this->assertCount(Role::count(), $xpath->query('./option', $select));
            $selected = $xpath->query('./option[@selected]', $select);
            $this->assertCount(1, $selected);
            $this->assertSame((string) $user->role_id, $selected->item(0)->getAttribute('value'));
            foreach (Role::all() as $role) {
                $this->assertCount(1, $xpath->query('./option[@value="'.$role->id.'"]', $select));
            }
        }
    }

    public function test_admin_can_delete_pending_user(): void
    {
        $pending = User::factory()->create();

        $this->actingAs($this->admin())->delete(route('users.destroy', $pending))
            ->assertRedirect(route('users'))->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['id' => $pending->id]);
    }

    public function test_already_approved_users_and_admin_cannot_be_modified(): void
    {
        $admin = $this->admin();
        $approved = User::factory()->approved()->create();
        $this->actingAs($admin);

        foreach ([$admin, $approved] as $user) {
            $this->patch(route('users.update', $user), [
                'role_id' => Role::where('name', 'editor')->firstOrFail()->id,
            ])->assertForbidden();
            $this->delete(route('users.destroy', $user))->assertForbidden();
            $this->assertDatabaseHas('users', ['id' => $user->id, 'is_approved' => true, 'role_id' => $user->role_id]);
        }
    }

    public function test_missing_users_return_not_found(): void
    {
        $this->actingAs($this->admin());
        $this->patch(route('users.update', 999999))->assertNotFound();
        $this->delete(route('users.destroy', 999999))->assertNotFound();
    }

    public function test_get_requests_cannot_approve_or_delete_users(): void
    {
        $pending = User::factory()->create();
        $this->actingAs($this->admin())->get('/users/'.$pending->id)->assertStatus(405);
        $this->post('/users/'.$pending->id)->assertStatus(405);
        $this->assertDatabaseHas('users', ['id' => $pending->id, 'is_approved' => false]);
    }
}
