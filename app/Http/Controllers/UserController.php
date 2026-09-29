<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()?->role?->name === 'admin', 403);

        $non_approved_users = User::where('is_approved', false)
            ->orderBy('created_at')
            ->get();

        $approved_users = User::where('is_approved', true)
            ->with('role')
            ->orderBy('created_at')
            ->get();

        return view('users', [
            'approved_users' => $approved_users,
            'non_approved_users' => $non_approved_users,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->role?->name === 'admin', 403);
        abort_if($user->is_approved, 403);

        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user->role_id = $data['role_id'];
        $user->is_approved = true;
        $user->save();

        return redirect()->route('users')
            ->with('status', 'User approved and role assigned.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->role?->name === 'admin', 403);
        abort_if($user->is_approved, 403);

        $user->delete();

        return redirect()->route('users')->with('status', 'Pending registration deleted.');
    }
}
