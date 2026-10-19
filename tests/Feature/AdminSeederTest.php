<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_only_one_approved_admin_without_demo_data(): void
    {
        config(['admin.email' => 'owner@example.com', 'admin.password' => 'long-test-password']);
        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $user = User::sole();
        $this->assertSame('owner@example.com', $user->email);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->is_approved);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('long-test-password', $user->password));
        foreach (['courses', 'course_sessions', 'questionnaire_templates', 'feedback_forms'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }

        config(['admin.password' => 'a-different-password']);
        $this->seed(AdminSeeder::class);
        $this->assertTrue(Hash::check('long-test-password', $user->fresh()->password));
    }

    public function test_missing_password_creates_no_user(): void
    {
        config(['admin.email' => 'owner@example.com', 'admin.password' => null]);
        try {
            $this->seed(AdminSeeder::class);
            $this->fail('Expected missing credentials to be rejected.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_existing_user_is_not_promoted(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        config(['admin.email' => $user->email, 'admin.password' => 'long-test-password']);
        try {
            $this->seed(AdminSeeder::class);
            $this->fail('Expected an existing non-admin account to be rejected.');
        } catch (RuntimeException) {
            $this->assertFalse($user->fresh()->hasRole('admin'));
            $this->assertDatabaseCount('users', 1);
        }
    }

    public function test_different_existing_admin_prevents_an_additional_admin(): void
    {
        User::factory()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);
        config(['admin.email' => 'owner@example.com', 'admin.password' => 'long-test-password']);
        try {
            $this->seed(AdminSeeder::class);
            $this->fail('Expected the second admin to be rejected.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
        }
    }
}
