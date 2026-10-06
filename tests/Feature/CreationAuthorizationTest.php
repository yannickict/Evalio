<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function allowedRoles(): array
    {
        return ['admin' => ['admin'], 'editor' => ['editor']];
    }

    #[DataProvider('allowedRoles')]
    public function test_admins_and_editors_can_access_creation_and_see_buttons(string $role): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create([
            'role_id' => Role::where('name', $role)->sole()->id,
        ]));

        $this->get(route('courses.index'))->assertOk()->assertSee(route('courses.create'), false);
        $this->get(route('questionnaires.index'))->assertOk()->assertSee(route('questionnaires.create'), false);
        $this->get(route('courses.create'))->assertOk();
        $this->get(route('questionnaires.create'))->assertOk();

        foreach (['courses.store', 'questionnaires.store', 'questionnaires.preview'] as $route) {
            $this->post(route($route), [])->assertRedirect()->assertSessionHasErrors();
        }
    }

    public function test_instructors_cannot_access_creation_or_see_creation_buttons(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create());

        $this->get(route('courses.index'))->assertOk()->assertDontSee(route('courses.create'), false);
        $this->get(route('questionnaires.index'))->assertOk()->assertDontSee(route('questionnaires.create'), false);
        $this->get(route('courses.create'))->assertForbidden();
        $this->get(route('questionnaires.create'))->assertForbidden();

        foreach (['courses.store', 'questionnaires.store', 'questionnaires.preview'] as $route) {
            $this->post(route($route), [])->assertForbidden();
        }

        $this->assertDatabaseCount('courses', 0);
        $this->assertDatabaseCount('questionnaire_templates', 0);
    }

    public function test_users_without_a_role_cannot_access_creation(): void
    {
        $user = User::factory()->approved()->create();
        $user->setRelation('role', null);
        $this->actingAs($user);

        $this->get(route('courses.create'))->assertForbidden();
        $this->get(route('questionnaires.create'))->assertForbidden();
        foreach (['courses.store', 'questionnaires.store', 'questionnaires.preview'] as $route) {
            $this->post(route($route), [])->assertForbidden();
        }
    }
}
