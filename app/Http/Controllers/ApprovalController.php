<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()?->role?->name === 'admin', 403);

        $users = User::where('is_approved', false)
            ->orderBy('created_at')
            ->get();

        return view('approve', ['users' => $users]);
    }

    public function update(User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->role?->name === 'admin', 403);
        abort_if($user->is_approved, 403);

        $user->is_approved = true;
        $user->save();

        return redirect()->route('approve')->with('status', 'User approved. They can now log in.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->role?->name === 'admin', 403);
        abort_if($user->is_approved, 403);

        $user->delete();

        return redirect()->route('approve')->with('status', 'Pending registration deleted.');
    }
}
