<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class BackupController extends Controller
{
    public function store(BackupService $backupService): RedirectResponse
    {
        try {
            $backupService->create();
        } catch (RuntimeException|FileNotFoundException $exception) {
            report($exception);

            return redirect()->route('settings.index')->withErrors([
                'backup' => 'The backup could not be created.',
            ]);
        }

        return redirect()->route('settings.index')->with(
            'status',
            'Demo backup stored. This is not a real database backup.',
        );
    }
}
