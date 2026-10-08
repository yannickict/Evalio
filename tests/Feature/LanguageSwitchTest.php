<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->setLocale('de');
    }

    public function test_guests_can_select_either_language_and_return_to_the_current_page(): void
    {
        foreach (['de', 'en'] as $locale) {
            $this->post(route('language.update'), ['locale' => $locale, 'return_to' => '/questionnaire?code=123456'])
                ->assertRedirect('/questionnaire?code=123456')->assertCookie('locale', $locale);
        }
    }

    public function test_language_cookie_controls_guest_and_authenticated_pages(): void
    {
        $this->withCookie('locale', 'en')->get(route('home'))->assertOk()
            ->assertSee('lang="en"', false)->assertSee('Share your feedback.');
        $this->get(route('login'))->assertOk()->assertSee('Welcome back')->assertSee('Deutsch')->assertSee('English');
        $this->actingAs(User::factory()->approved()->create())->get(route('profile.show'))->assertOk()
            ->assertSee('Account profile')->assertSee('lang="en"', false);
        $this->withCookie('locale', 'de')->get(route('profile.show'))->assertOk()
            ->assertSee('Benutzerprofil')->assertSee('lang="de"', false);
    }

    public function test_invalid_language_input_is_rejected_and_invalid_cookie_uses_default(): void
    {
        $this->post(route('language.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale')->assertCookieMissing('locale');
        $this->withCookie('locale', 'fr')->get(route('home'))->assertOk()->assertSee('lang="de"', false);
    }

    public function test_return_path_cannot_redirect_outside_the_application(): void
    {
        foreach (['https://example.com', '//example.com', '/\\example.com', "/\nexample.com"] as $path) {
            $this->post(route('language.update'), ['locale' => 'en', 'return_to' => $path])->assertRedirect('/');
        }
    }

    public function test_editor_can_change_language_before_refreshing_its_draft(): void
    {
        $this->postJson(route('language.update'), ['locale' => 'en'])
            ->assertOk()->assertJson(['locale' => 'en'])->assertCookie('locale', 'en');
    }

    public function test_switch_is_available_on_guest_auth_and_authenticated_layouts(): void
    {
        foreach (['home', 'login', 'register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('id="language-switch"', false)
                ->assertSee(route('language.update'), false)->assertSee('name="_token"', false)
                ->assertSee('Deutsch')->assertSee('English');
        }
        $this->actingAs(User::factory()->approved()->create())->get(route('profile.show'))->assertOk()
            ->assertSee('id="language-switch"', false);
    }
}
