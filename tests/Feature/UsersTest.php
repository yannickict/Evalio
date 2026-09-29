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

    #[DataProvider('sectionStates')]
    public function test_user_sections_have_independent_accessible_collapse_toggles(bool $hasPendingUsers): void
    {
        if ($hasPendingUsers) {
            User::factory()->create();
        }
        $response = $this->actingAs($this->admin())->get(route('users'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach (['pending-users', 'all-users'] as $id) {
            $target = $id.'-content';
            $this->assertCount(1, $xpath->query('//section[@id="'.$id.'"]//h2/button[@type="button" and @data-bs-toggle="collapse" and @data-bs-target="#'.$target.'" and @aria-controls="'.$target.'" and @aria-expanded="true"]'));
            $this->assertCount(1, $xpath->query('//*[@id="'.$target.'" and contains(concat(" ", normalize-space(@class), " "), " collapse ") and contains(concat(" ", normalize-space(@class), " "), " show ") and not(@data-bs-parent)]'));
            $this->assertCount(1, $xpath->query('//*[@id="'.$target.'"]//div[contains(concat(" ", normalize-space(@class), " "), " list-group ")]'));
            $this->assertCount(1, $xpath->query('//section[@id="'.$id.'"]/h2[contains(concat(" ", normalize-space(@class), " "), " mb-0 ")]/following-sibling::*[1][@id="'.$target.'"]'));
            $this->assertCount(1, $xpath->query('//*[@id="'.$target.'"]/*[1][contains(concat(" ", normalize-space(@class), " "), " card ")]'));
        }
    }

    public static function sectionStates(): array
    {
        return ['empty pending list' => [false], 'populated pending list' => [true]];
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

    public function test_admin_sees_pending_and_approved_users_in_separate_sections(): void
    {
        $newer = User::factory()->create(['created_at' => now()]);
        $older = User::factory()->create(['created_at' => now()->subDay()]);
        $approved = User::factory()->approved()->create();

        $response = $this->actingAs($this->admin())->get(route('users'))
            ->assertOk()
            ->assertViewIs('users')
            ->assertSee('<title>Users - Feedback</title>', false)
            ->assertSeeInOrder(['Users', 'Approve people', 'All users'])
            ->assertSeeInOrder([$older->email, $newer->email])
            ->assertSee($approved->email)
            ->assertSee(route('users.update', $older), false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_method" value="DELETE"', false);

        $pendingSection = $this->sectionHtml($response->getContent(), 'pending-users');
        $this->assertStringContainsString($older->email, $pendingSection);
        $this->assertStringContainsString($newer->email, $pendingSection);
        $this->assertStringNotContainsString($approved->email, $pendingSection);
        $this->assertStringContainsString('2 pending', $pendingSection);

        $allUsersSection = $this->sectionHtml($response->getContent(), 'all-users');
        foreach ([$older, $newer] as $user) {
            $this->assertStringNotContainsString($user->email, $allUsersSection);
        }
        $this->assertStringContainsString($approved->email, $allUsersSection);
        $this->assertStringContainsString('2 users', $allUsersSection);
    }

    public function test_admin_sees_empty_state(): void
    {
        $this->actingAs($this->admin())->get(route('users'))
            ->assertOk()->assertSee('No users waiting for approval.');
    }

    public function test_approved_users_are_ordered_by_registration_date(): void
    {
        $admin = $this->admin();
        $newer = User::factory()->approved()->create(['created_at' => now()->subDay()]);
        $older = User::factory()->approved()->create(['created_at' => now()->subDays(2)]);

        $this->actingAs($admin)->get(route('users'))->assertOk()
            ->assertSeeInOrder([$older->email, $newer->email, $admin->email]);
    }

    public function test_user_without_a_role_cannot_access_or_modify_users(): void
    {
        $actor = User::factory()->approved()->create();
        $actor->setRelation('role', null);
        $pending = User::factory()->create();

        $this->actingAs($actor)->get(route('users'))->assertForbidden();
        $this->patch(route('users.update', $pending), ['role_id' => $actor->role_id])->assertForbidden();
        $this->delete(route('users.destroy', $pending))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $pending->id, 'is_approved' => false]);
    }

    public function test_approval_ignores_unrelated_user_attributes(): void
    {
        $pending = User::factory()->create();
        $original = $pending->only(['first_name', 'last_name', 'email', 'password']);

        $this->actingAs($this->admin())->patch(route('users.update', $pending), [
            'role_id' => $pending->role_id,
            'first_name' => 'Changed',
            'last_name' => 'Name',
            'email' => 'changed@example.com',
            'password' => 'changed-password',
            'is_approved' => false,
        ])->assertRedirect(route('users'))->assertSessionHasNoErrors();

        $this->assertSame($original, $pending->fresh()->only(array_keys($original)));
        $this->assertTrue($pending->fresh()->is_approved);
        $this->get(route('users'))->assertSee('User approved and role assigned.');
    }

    public function test_invalid_role_error_is_visible_after_redirect_and_user_stays_pending(): void
    {
        $pending = User::factory()->create();
        $this->actingAs($this->admin())->from(route('users'))
            ->patch(route('users.update', $pending), ['role_id' => 999999])
            ->assertRedirect(route('users'));

        $response = $this->get(route('users'))->assertOk();
        $response->assertSee('role="alert"', false)->assertSee('Please check your changes.');
        $this->assertStringContainsString($pending->email, $this->sectionHtml($response->getContent(), 'pending-users'));
        $this->assertFalse($pending->fresh()->is_approved);
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
        $response = $this->get(route('users'))->assertOk()->assertSee($pending->email);
        $this->assertStringNotContainsString($pending->email, $this->sectionHtml($response->getContent(), 'pending-users'));

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $pending->email, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($pending);
    }

    public function test_approved_users_show_roles_without_unavailable_delete_actions(): void
    {
        $pending = User::factory()->create();
        $approved = User::factory()->approved()->create([
            'role_id' => Role::where('name', 'editor')->firstOrFail()->id,
        ]);
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('users'))->assertOk();
        $html = $this->sectionHtml($response->getContent(), 'all-users');
        $this->assertStringNotContainsString($pending->email, $html);
        $this->assertStringContainsString('Editor', $html);
        $this->assertStringContainsString('Admin', $html);
        $pendingHtml = $this->sectionHtml($response->getContent(), 'pending-users');
        $this->assertStringContainsString('action="'.route('users.destroy', $pending).'"', $pendingHtml);
        foreach ([$approved, $admin] as $user) {
            $this->assertStringNotContainsString('action="'.route('users.destroy', $user).'"', $html);
        }
    }

    public function test_empty_pending_section_still_shows_registered_users(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('users'))->assertOk()
            ->assertSee('No users waiting for approval.')->assertDontSee('No users yet.');

        $html = $this->sectionHtml($response->getContent(), 'all-users');
        $this->assertStringContainsString($admin->email, $html);
        $this->assertStringContainsString('1 user', $html);
    }

    public function test_both_user_sections_escape_user_content(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);
        $approved = User::factory()->approved()->create(['name' => '<script>alert(2)</script>']);

        $this->actingAs($this->admin())->get(route('users'))->assertOk()
            ->assertSee($user->name)
            ->assertDontSee($user->name, false)
            ->assertSee($approved->name)
            ->assertDontSee($approved->name, false);
    }

    private function sectionHtml(string $html, string $id): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $section = $document->getElementById($id);
        if (! $section instanceof \DOMElement) {
            $this->fail('Expected the users section '.$id.' to exist.');
        }

        return (string) $document->saveHTML($section);
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
            if (! $form instanceof \DOMElement) {
                $this->fail('Expected the approval form to be a DOM element.');
            }
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
            $selectedOption = $selected->item(0);
            if (! $selectedOption instanceof \DOMElement) {
                $this->fail('Expected the selected role option to be a DOM element.');
            }
            $this->assertSame((string) $user->role_id, $selectedOption->getAttribute('value'));
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
        $this->get(route('users'))->assertOk()
            ->assertSee('Pending registration deleted.')->assertDontSee($pending->email);
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
