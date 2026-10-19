<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class SqlDumpService
{
    public function create(): string
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('SQL exports require a MySQL connection.');
        }

        $database = $connection->getConfig();
        $driver = config('backups.driver');
        if (! in_array($driver, ['native', 'docker'], true)) {
            throw new RuntimeException('Unsupported database export driver.');
        }

        if ($driver === 'docker') {
            $command = config('backups.compose_binary')
                ? [config('backups.compose_binary')]
                : [config('backups.docker_binary'), 'compose'];
            $command = array_merge($command, ['exec', '-T', '-e', 'MYSQL_PWD', config('backups.docker_service'), 'mysqldump']);
        } else {
            $command = [config('backups.binary')];
        }

        $command = array_merge($command, [
            '--no-defaults',
            '--protocol=TCP',
            '--host='.($driver === 'docker' ? config('backups.docker_host') : $database['host']),
            '--port='.($driver === 'docker' ? config('backups.docker_port') : $database['port']),
            '--user='.$database['username'],
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            '--skip-triggers',
            '--set-gtid-purged=OFF',
            '--hex-blob',
            '--',
            $database['database'],
        ]);

        File::ensureDirectoryExists(storage_path('app/private/dumps'));
        $path = storage_path('app/private/dumps/'.Str::uuid().'.sql');

        try {
            $result = Process::path(base_path())->timeout(120)
                ->env(['MYSQL_PWD' => (string) $database['password']])
                ->run($command);
        } catch (\Throwable $exception) {
            throw new RuntimeException('The MySQL export process could not run ('.$exception::class.'). Check the executable path and process permissions.');
        }

        if (! $result->successful() || $result->output() === '') {
            $error = $result->errorOutput();
            if ((string) $database['password'] !== '') {
                $error = str_replace((string) $database['password'], '[redacted]', $error);
            }

            throw new RuntimeException('The MySQL export failed: '.substr($error, 0, 2000));
        }

        try {
            if (File::put($path, $result->output()) === false) {
                throw new RuntimeException('The database export could not be written.');
            }

            return $path;
        } catch (\Throwable) {
            File::delete($path);

            throw new RuntimeException('The MySQL database export could not be completed.');
        }
    }
}
