<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BackupService
{
    public function __construct(private SqlDumpService $dumpService) {}

    public function create(): string
    {
        $dumpPath = $this->dumpService->create();
        $path = 'backups/demo-backup-'.now()->format('Y-m-d_H-i-s').'-'.Str::uuid().'.sql';

        if (! Storage::disk('local')->put($path, File::get($dumpPath))) {
            throw new RuntimeException('Backup could not be stored.');
        }

        return $path;
    }
}
