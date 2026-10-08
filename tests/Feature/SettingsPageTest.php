<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_settings_and_import_navigation(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));
        $this->get(route('settings.index'))->assertOk()->assertSee('Create backup')->assertSee('Import CSV')->assertSee('Export SQL dump');
        $this->get(route('settings.index'))->assertSee(route('settings.imports'), false);
        $this->get(route('settings.imports'))->assertOk()
            ->assertSee(route('settings.sessions-import.create'), false)
            ->assertSee(route('settings.courses-import.create'), false)
            ->assertSee('Import sessions')->assertSee('Import courses');
        foreach (['sessions', 'courses'] as $type) {
            $this->get(route('settings.'.$type.'-import.create'))->assertOk()
                ->assertSee(route('settings.'.$type.'-import.store'), false)
                ->assertSee('enctype="multipart/form-data"', false)
                ->assertSee('name="_token"', false);
        }
        $this->get(route('home'))->assertOk()->assertSee(route('settings.index'), false);
    }

    public function test_guests_and_non_admins_cannot_access_settings(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
        $this->get(route('settings.imports'))->assertRedirect(route('login'));
        foreach (['instructor', 'editor'] as $role) {
            $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]));
            $this->get(route('settings.index'))->assertForbidden();
            $this->get(route('settings.imports'))->assertForbidden();
        }
    }
}
