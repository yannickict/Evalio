<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnairePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_questionnaire_pages_require_authentication(): void
    {
        $this->get(route('questionnaires.index'))->assertRedirect(route('login'));
        $this->get(route('questionnaires.create'))->assertRedirect(route('login'));
    }

    public function test_signed_in_user_can_navigate_library_and_editor_preview(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create());
        $this->get(route('questionnaires.index'))->assertOk()
            ->assertSee(route('questionnaires.create'), false)->assertSee('Questionnaire library');
        $this->get(route('questionnaires.create'))->assertOk()
            ->assertSee(route('questionnaires.index'), false)
            ->assertSee('2 sample questions')->assertSee('Saving questionnaires will be available soon.');
    }
}
