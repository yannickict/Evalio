<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(['de', 'en'])],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $destination = $data['return_to'] ?? '/';

        // The language form can return only to a path on this application.
        if (! str_starts_with($destination, '/')
            || str_starts_with($destination, '//')
            || str_contains($destination, '\\')
            || preg_match('/[\x00-\x20]/', $destination)) {
            $destination = '/';
        }

        $response = $request->expectsJson()
            ? response()->json(['locale' => $data['locale']])
            : redirect($destination);

        return $response->cookie('locale', $data['locale'], 525600, '/', null, $request->isSecure(), true, false, 'lax');
    }
}
