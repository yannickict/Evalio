<?php

namespace Tests\Feature;

use App\Models\CourseSession;
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
        $response = $this->actingAs($this->admin())->get(route('users.index'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach (['pending-users', 'all-users'] as $id) {
            $target = $id.'-content';
            $this->assertCount(1, $xpath->query('//section[@id="'.$id.'"]//h2/button[@type="button" and @data-bs-toggle="collapse" and @data-bs-target="#'.$target.'" and @aria-controls="'.$target.'" and @aria-expanded="true"]'));
            $this->assertCount(1, $xpath->query('//*[@id="'.$target.'" and contains(concat(" ", normalize-space(@class), " "), " collapse ") and contains(concat(" ", normalize-space(@class), " "), " show ") and not(@data-bs-parent)]'));
            $this->assertCount(1, $xpath->query('//*[@id="'.$target.'"]//div[contains(concat(" ", normalize-space(@class), " "), " list-group ")]'));
            $this->assertCount(1, $xpath->query('//section[@id="'.$id.'"]/div[contains(concat(" ", normalize-space(@class), " "), " accordion-item ")]/h2[contains(concat(" ", normalize-space(@class), " "), " accordion-header ")]/following-sibling::*[1][@id="'.$target.'"]'));
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

        $this->get(route('users.index'))->assertRedirect(route('login'));
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
            $this->actingAs($actor)->get(route('users.index'))->assertForbidden();
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

        $response = $this->actingAs($this->admin())->get(route('users.index'))
            ->assertOk()
            ->assertViewIs('pages.users.index')
            ->assertSee('<title>Users - Evalio</title>', false)
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
        $this->actingAs($this->admin())->get(route('users.index'))
            ->assertOk()->assertSee('No users waiting for approval.');
    }

    public function test_approved_users_are_ordered_by_registration_date(): void
    {
        $admin = $this->admin();
        $newer = User::factory()->approved()->create(['created_at' => now()->subDay()]);
        $older = User::factory()->approved()->create(['created_at' => now()->subDays(2)]);

        $this->actingAs($admin)->get(route('users.index'))->assertOk()
            ->assertSeeInOrder([$older->email, $newer->email, $admin->email]);
    }

    public function test_user_without_a_role_cannot_access_or_modify_users(): void
    {
        $actor = User::factory()->approved()->create();
        $actor->setRelation('role', null);
        $pending = User::factory()->create();

        $this->actingAs($actor)->get(route('users.index'))->assertForbidden();
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
        ])->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $this->assertSame($original, $pending->fresh()->only(array_keys($original)));
        $this->assertTrue($pending->fresh()->is_approved);
        $this->get(route('users.index'))->assertSee('User approved and role assigned.');
    }

    public function test_invalid_role_error_is_visible_after_redirect_and_user_stays_pending(): void
    {
        $pending = User::factory()->create();
        $this->actingAs($this->admin())->from(route('users.index'))
            ->patch(route('users.update', $pending), ['role_id' => 999999])
            ->assertRedirect(route('users.index'));

        $response = $this->get(route('users.index'))->assertOk();
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
            ->assertRedirect(route('users.index'))->assertSessionHasNoErrors()->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'id' => $pending->id,
            'is_approved' => true,
            'role_id' => $role->id,
        ]);
        $response = $this->get(route('users.index'))->assertOk()->assertSee($pending->email);
        $this->assertStringNotContainsString($pending->email, $this->sectionHtml($response->getContent(), 'pending-users'));

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $pending->email, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($pending);
    }

    public function test_approved_users_show_roles_and_delete_actions(): void
    {
        $pending = User::factory()->create();
        $approved = User::factory()->approved()->create([
            'role_id' => Role::where('name', 'editor')->firstOrFail()->id,
        ]);
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $html = $this->sectionHtml($response->getContent(), 'all-users');
        $this->assertStringNotContainsString($pending->email, $html);
        $this->assertStringContainsString('Editor', $html);
        $this->assertStringContainsString('Admin', $html);
        $pendingHtml = $this->sectionHtml($response->getContent(), 'pending-users');
        $this->assertStringContainsString('action="'.route('users.destroy', $pending).'"', $pendingHtml);
        foreach ([$approved, $admin] as $user) {
            $this->assertStringContainsString('action="'.route('users.destroy', $user).'"', $html);
        }
    }

    public function test_empty_pending_section_still_shows_registered_users(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('users.index'))->assertOk()
            ->assertSee('No users waiting for approval.')->assertDontSee('No users yet.');

        $html = $this->sectionHtml($response->getContent(), 'all-users');
        $this->assertStringContainsString($admin->email, $html);
        $this->assertStringContainsString('1 user', $html);
    }

    public function test_both_user_sections_escape_user_content(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);
        $approved = User::factory()->approved()->create(['name' => '<script>alert(2)</script>']);

        $this->actingAs($this->admin())->get(route('users.index'))->assertOk()
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

        $this->actingAs($this->admin())->from(route('users.index'))
            ->patch(route('users.update', $pending), $data)
            ->assertRedirect(route('users.index'))->assertSessionHasErrors('role_id');

        $this->assertDatabaseHas('users', [
            'id' => $pending->id,
            'is_approved' => false,
            'role_id' => $pending->role_id,
        ]);
    }

    public function test_each_pending_user_has_a_matching_modal_and_role_form(): void
    {
        $pending = User::factory()->count(2)->create();
        $response = $this->actingAs($this->admin())->get(route('users.index'))->assertOk();
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
            ->assertRedirect(route('users.index'))->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['id' => $pending->id]);
        $this->get(route('users.index'))->assertOk()
            ->assertSee('Pending registration deleted.')->assertDontSee($pending->email);
    }

    public function test_admin_can_update_an_approved_users_role(): void
    {
        $approved = User::factory()->approved()->create();
        $role = Role::where('name', 'editor')->firstOrFail();

        $this->actingAs($this->admin())->patch(route('users.update', $approved), [
            'role_id' => $role->id,
        ])->assertRedirect(route('users.index'))->assertSessionHas('status', 'User role updated.');

        $this->assertDatabaseHas('users', ['id' => $approved->id, 'is_approved' => true, 'role_id' => $role->id]);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->admin();
        $role = Role::where('name', 'editor')->firstOrFail();

        $this->actingAs($admin)->from(route('users.index'))->patch(route('users.update', $admin), [
            'role_id' => $role->id,
        ])->assertRedirect(route('users.index'))->assertSessionHasErrors('role_id');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role_id' => $admin->role_id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->delete(route('users.destroy', $admin))->assertForbidden();
        $this->assertModelExists($admin);
    }

    public function test_admin_can_delete_approved_user_without_course_sessions(): void
    {
        $user = User::factory()->approved()->create();

        $this->actingAs($this->admin())->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'))->assertSessionHas('status', 'User deleted.');

        $this->assertModelMissing($user);
    }

    public function test_deleting_an_instructor_with_sessions_shows_a_useful_error(): void
    {
        $user = User::factory()->approved()->create();
        $session = CourseSession::factory()->create(['instructor_id' => $user->id]);

        $this->actingAs($this->admin())->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'))->assertSessionHasErrors('user');

        $this->assertModelExists($user);
        $this->assertModelExists($session);

    }

    public function test_missing_users_return_not_found(): void
    {
        $this->actingAs($this->admin());
        $this->patch(route('users.update', 999999))->assertNotFound();
        $this->delete(route('users.destroy', 999999))->assertNotFound();
    }

    public function test_approved_role_forms_submit_on_change_and_self_role_and_delete_controls_are_disabled(): void
    {
        $admin = $this->admin();
        $user = User::factory()->approved()->create();
        $response = $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach ([$admin, $user] as $account) {
            $form = '//section[@id="all-users"]//form[@action="'.route('users.update', $account).'" and input[@name="_method" and @value="PATCH"]]';
            $this->assertCount(1, $xpath->query($form.'/input[@name="_token"]'));
            $this->assertCount(1, $xpath->query($form.'/select[@name="role_id" and @onchange="this.form.requestSubmit()"]/option[@selected and @value="'.$account->role_id.'"]'));
            $this->assertCount($account->is($admin) ? 1 : 0, $xpath->query($form.'/select[@disabled]'));
            $this->assertCount($account->is($admin) ? 1 : 0, $xpath->query($form.'/noscript/button[@disabled]'));
            $delete = '//form[@action="'.route('users.destroy', $account).'" and input[@value="DELETE"]]/button[@disabled]';
            $this->assertCount($account->is($admin) ? 1 : 0, $xpath->query($delete));
        }
        $response->assertSee('You cannot change your own role.');
    }

    public function test_non_admin_cannot_change_or_delete_an_approved_user(): void
    {
        $user = User::factory()->approved()->create();
        $this->actingAs(User::factory()->approved()->create());
        $this->patch(route('users.update', $user), ['role_id' => Role::where('name', 'admin')->firstOrFail()->id])->assertForbidden();
        $this->delete(route('users.destroy', $user))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => $user->role_id]);
    }

    public function test_invalid_role_does_not_change_an_approved_user(): void
    {
        $user = User::factory()->approved()->create();
        $this->actingAs($this->admin())->patch(route('users.update', $user), ['role_id' => 999999])
            ->assertSessionHasErrors('role_id');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => $user->role_id, 'is_approved' => true]);
    }

    public function test_get_requests_cannot_approve_or_delete_users(): void
    {
        $pending = User::factory()->create();
        $this->actingAs($this->admin())->get('/users/'.$pending->id)->assertStatus(405);
        $this->post('/users/'.$pending->id)->assertStatus(405);
        $this->assertDatabaseHas('users', ['id' => $pending->id, 'is_approved' => false]);
    }
}
