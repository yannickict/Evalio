<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $pendingUsers = User::where('is_approved', false)
            ->orderBy('created_at')
            ->get();

        $approvedUsers = User::where('is_approved', true)
            ->with('role')
            ->orderBy('created_at')
            ->get();

        return view('pages.users.index', [
            'approvedUsers' => $approvedUsers,
            'pendingUsers' => $pendingUsers,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $wasApproved = $user->is_approved;

        $data = $request->validated();

        $user->role_id = $data['role_id'];
        $user->is_approved = true;
        $user->save();

        $destination = $user->is(Auth::user()) && $user->role()->first()?->name !== 'admin'
            ? 'home' : 'users.index';

        return redirect()->route($destination)
            ->with('status', $wasApproved ? 'User role updated.' : 'User approved and role assigned.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->is(Auth::user()), 403);

        if ($user->courseSessions()->exists()) {
            return redirect()->route('users.index')->withErrors([
                'user' => 'This user is assigned to course sessions. Reassign those sessions to another instructor before deleting this user.',
            ]);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', $user->is_approved ? 'User deleted.' : 'Pending registration deleted.');
    }
}
