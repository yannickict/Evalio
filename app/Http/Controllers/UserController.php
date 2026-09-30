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
        $wasApproved = $user->is_approved;

        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user->role_id = $data['role_id'];
        $user->is_approved = true;
        $user->save();

        $destination = $user->is(Auth::user()) && $user->role()->first()?->name !== 'admin'
            ? 'home' : 'users';

        return redirect()->route($destination)
            ->with('status', $wasApproved ? 'User role updated.' : 'User approved and role assigned.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->role?->name === 'admin', 403);
        abort_if($user->is(Auth::user()), 403);

        if ($user->courseSessions()->exists()) {
            return redirect()->route('users')->withErrors([
                'user' => 'This user is assigned to course sessions. Reassign those sessions to another instructor before deleting this user.',
            ]);
        }

        $user->delete();

        return redirect()->route('users')->with('status', $user->is_approved ? 'User deleted.' : 'Pending registration deleted.');
    }
}
