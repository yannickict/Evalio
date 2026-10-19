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
        $path = 'backups/evalio-backup-'.now()->format('Y-m-d_H-i-s').'-'.Str::uuid().'.sql';

        try {
            if (! Storage::disk('local')->put($path, File::get($dumpPath))) {
                throw new RuntimeException('Backup could not be stored.');
            }
        } finally {
            File::delete($dumpPath);
        }

        return $path;
    }
}
