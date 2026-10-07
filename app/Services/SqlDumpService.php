<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class SqlDumpService
{
    public function create(): string
    {
        $path = storage_path('app/private/dumps/demo.sql');

        // Replace this placeholder with the database export tool on the server.
        if (! File::isFile($path) || ! is_readable($path) || File::size($path) === 0) {
            throw new RuntimeException('The placeholder SQL dump is missing, unreadable, or empty.');
        }

        return $path;
    }
}
