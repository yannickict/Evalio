<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SqlDumpService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SettingsActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_storage_write_is_reported_instead_of_claiming_success(): void
    {
        $this->mockDump();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->once()->andReturn($disk);
        $this->signInAdmin();

        $this->post(route('settings.backup'))->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors('backup')->assertSessionMissing('status');
    }

    public function test_backup_stores_separate_copies_without_overwriting_previous_backups(): void
    {
        Storage::fake('local');
        $source = $this->mockDump();
        $this->signInAdmin();

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('settings.backup'))->assertRedirect(route('settings.index'))
                ->assertSessionHasNoErrors()->assertSessionHas('status');
        }

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(2, $files);
        foreach ($files as $file) {
            $this->assertSame(file_get_contents($source), Storage::disk('local')->get($file));
        }
        $this->assertFileExists($source);
    }

    public function test_dump_download_preserves_the_source_file(): void
    {
        $source = $this->mockDump();
        $this->signInAdmin();
        $response = $this->post(route('settings.sql-dump'))->assertOk()
            ->assertDownload('demo-dump.sql')->assertHeader('Content-Type', 'application/sql');

        $this->assertSame($source, $response->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertFileExists($source);
    }

    public function test_missing_dump_displays_errors_and_creates_no_backup(): void
    {
        Storage::fake('local');
        $this->withoutVite();
        $this->mock(SqlDumpService::class)->shouldReceive('create')->twice()
            ->andThrow(new RuntimeException('Missing dump.'));
        $this->signInAdmin();

        foreach (['settings.backup' => 'The backup could not be created.',
            'settings.sql-dump' => 'The SQL dump could not be downloaded.'] as $route => $message) {
            $this->followingRedirects()->post(route($route))->assertOk()->assertSee($message);
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_guests_and_non_admins_cannot_run_settings_actions(): void
    {
        foreach (['settings.backup', 'settings.sql-dump'] as $route) {
            $this->post(route($route))->assertRedirect(route('login'));
        }
        foreach (['editor', 'instructor'] as $role) {
            $this->actingAs(User::factory()->approved()->create([
                'role_id' => Role::where('name', $role)->sole()->id,
            ]));
            foreach (['settings.backup', 'settings.sql-dump'] as $route) {
                $this->post(route($route))->assertForbidden();
            }
        }
    }

    private function mockDump(): string
    {
        // A versioned harmless file keeps tests independent of real local dumps.
        $path = base_path('tests/Fixtures/demo-dump.sql');
        $this->mock(SqlDumpService::class)->shouldReceive('create')->andReturn($path);

        return $path;
    }

    private function signInAdmin(): void
    {
        $this->actingAs(User::factory()->approved()->create([
            'role_id' => Role::where('name', 'admin')->sole()->id,
        ]));
    }
}
