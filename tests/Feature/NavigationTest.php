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

    public function test_home_has_no_confirmation_without_a_status_message(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('role="status"', false);
    }

    public function test_home_confirmation_is_accessible_escaped_and_above_the_code_form(): void
    {
        $message = '<script>alert("status")</script>';
        $response = $this->withSession(['status' => $message])->get(route('home'))->assertOk()
            ->assertSee($message)->assertDontSee($message, false);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        $this->assertCount(1, $xpath->query('//main/section/*[@role="status" and @aria-live="polite"]'));
        $this->assertCount(1, $xpath->query('//*[@role="status"]/following-sibling::*//form[@action="'.route('questionnaire').'"]'));
    }

    public function test_admin_navigation_marks_only_the_current_page_active(): void
    {
        $admin = User::factory()->approved()->create([
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
        ]);

        foreach (['home', 'users', 'overview', 'courses', 'questionnaires.index'] as $route) {
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
        $link = $links->item(0);
        if (! $link instanceof \DOMElement) {
            $this->fail('Expected the active navigation link to be a DOM element.');
        }
        $this->assertSame($url, $link->getAttribute('href'));
        $this->assertStringContainsString('bg-success', $link->getAttribute('class'));
    }

    public function test_navigation_has_direct_mobile_logout_and_a_desktop_profile_dropdown(): void
    {
        $response = $this->actingAs(User::factory()->approved()->create())->get(route('home'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertCount(1, $xpath->query('//nav//button[@data-bs-target="#main-navigation" and @aria-controls="main-navigation" and @aria-expanded="false"]'));
        $this->assertCount(1, $xpath->query('//nav//*[@id="main-navigation"]'));
        $logoutForm = 'form[@method="POST" and @action="'.route('logout').'"]';
        $mobileLogout = '//*[@id="main-navigation"]/'.$logoutForm.'[contains(concat(" ", normalize-space(@class), " "), " d-md-none ")]';
        $desktopProfile = '//*[@id="main-navigation"]/div[contains(concat(" ", normalize-space(@class), " "), " d-none ") and contains(concat(" ", normalize-space(@class), " "), " d-md-block ")]';
        $desktopMenu = $desktopProfile.'/div[@aria-labelledby="profile-menu-toggle" and contains(concat(" ", normalize-space(@class), " "), " dropdown-menu ") and not(contains(concat(" ", normalize-space(@class), " "), " show "))]';

        $this->assertCount(2, $xpath->query('//nav//form[@action="'.route('logout').'"]'));
        foreach ([$mobileLogout, $desktopMenu.'/'.$logoutForm] as $form) {
            $this->assertCount(1, $xpath->query($form));
            $this->assertCount(1, $xpath->query($form.'/input[@name="_token" and @type="hidden"]'));
            $this->assertCount(1, $xpath->query($form.'/button[@type="submit" and normalize-space(.)="Log out"]'));
        }
        $this->assertCount(1, $xpath->query($desktopProfile.'/button[@id="profile-menu-toggle" and @type="button" and @data-bs-toggle="dropdown" and @aria-expanded="false" and @aria-label]'));
        $this->assertCount(1, $xpath->query('//form[@method="GET" and @action="'.route('questionnaire').'"]//input[@name="code" and @required]'));
    }
}
