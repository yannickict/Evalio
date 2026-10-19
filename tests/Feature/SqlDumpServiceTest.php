<?php

namespace Tests\Feature;

use App\Services\SqlDumpService;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class SqlDumpServiceTest extends TestCase
{
    public function test_docker_export_uses_connection_credentials_without_password_arguments(): void
    {
        config(['database.default' => 'mysql', 'backups.driver' => 'docker', 'backups.docker_binary' => 'docker', 'backups.compose_binary' => null]);
        Process::fake(['*' => Process::result(output: '-- database export')]);

        $path = app(SqlDumpService::class)->create();
        try {
            $this->assertSame('-- database export', rtrim(file_get_contents($path)));
            Process::assertRan(fn ($process) => $process->command[0] === 'docker'
                && in_array('MYSQL_PWD', $process->command, true)
                && in_array('--port=3306', $process->command, true)
                && ! in_array('--password', $process->command, true));
        } finally {
            unlink($path);
        }
    }

    public function test_failed_export_does_not_return_a_backup(): void
    {
        config(['database.default' => 'mysql', 'backups.driver' => 'native']);
        Process::fake(['*' => Process::result(errorOutput: 'Access denied', exitCode: 1)]);

        $this->expectException(RuntimeException::class);
        app(SqlDumpService::class)->create();
    }

    public function test_explicit_compose_executable_bypasses_docker_plugin_discovery(): void
    {
        config(['database.default' => 'mysql', 'backups.driver' => 'docker', 'backups.compose_binary' => 'C:/Docker/docker-compose.exe']);
        Process::fake(['*' => Process::result(output: '-- database export')]);

        $path = app(SqlDumpService::class)->create();
        try {
            Process::assertRan(fn ($process) => array_slice($process->command, 0, 3)
                === ['C:/Docker/docker-compose.exe', 'exec', '-T']);
        } finally {
            unlink($path);
        }
    }
}
