<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_landing_page_has_code_input_and_login_link(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('id="session-code"', false)
            ->assertSee('maxlength="6"', false)
            ->assertSee(route('login'), false)
            ->assertDontSee('Users')
            ->assertDontSee('Log out');
    }

    public function test_instructors_and_editors_see_only_their_navigation(): void
    {
        foreach (['instructor', 'editor'] as $role) {
            $user = User::factory()->approved()->create([
                'role_id' => Role::where('name', $role)->firstOrFail()->id,
            ]);
            $response = $this->actingAs($user)->get(route('home'))->assertOk()
                ->assertSee(route('overview'), false)->assertSee('Overview')->assertSee('Log out')->assertDontSee('Users');
            $this->assertActiveLink($response->getContent(), route('home'));
        }
    }

    public function test_admin_navigation_marks_only_the_current_page_active(): void
    {
        $admin = User::factory()->approved()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
        ]);

        foreach (['home', 'users', 'overview'] as $route) {
            $response = $this->actingAs($admin)->get(route($route))->assertOk()
                ->assertSee(route('users'), false)->assertSee('Users');
            $this->assertActiveLink($response->getContent(), route($route));
        }
    }

    private function assertActiveLink(string $html, string $url): void
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//nav//a[@aria-current="page"]');

        $this->assertCount(1, $links);
        $this->assertSame($url, $links->item(0)->getAttribute('href'));
        $this->assertStringContainsString('bg-success', $links->item(0)->getAttribute('class'));
    }
}
