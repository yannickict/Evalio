<?php

namespace App\Http\Controllers;

use App\Services\SqlDumpService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SqlDumpController extends Controller
{
    public function store(SqlDumpService $dumpService): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = $dumpService->create();
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()->route('settings.index')->withErrors([
                'sql_dump' => __('The SQL dump could not be downloaded.'),
            ]);
        }

        return response()->download($path, 'demo-dump.sql', [
            'Content-Type' => 'application/sql',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
