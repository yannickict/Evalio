<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_settings_and_placeholder_buttons(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));
        $this->get(route('settings.index'))->assertOk()->assertSee('Create backup')->assertSee('Import CSV')->assertSee('Export SQL dump');
        $this->get(route('home'))->assertOk()->assertSee(route('settings.index'), false);
    }

    public function test_guests_and_non_admins_cannot_access_settings(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
        foreach (['instructor', 'editor'] as $role) {
            $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]));
            $this->get(route('settings.index'))->assertForbidden();
        }
    }
}
