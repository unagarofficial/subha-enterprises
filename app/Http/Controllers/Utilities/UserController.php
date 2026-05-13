<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users    = User::with('branch')->orderBy('user_name')->get();
        $branches = Branch::orderBy('br_name')->get();

        return view('utilities.users.index', compact('users', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string|max:50|unique:users,user_name',
            'password'  => 'required|string|min:6|confirmed',
            'br_code'   => 'required|exists:branches,br_code',
            'user_type' => 'required|in:ADMIN,USER',
        ]);

        // Pass plain text — hashed cast on model handles bcrypt
        User::create([
            'user_name' => $request->user_name,
            'password'  => $request->password,
            'br_code'   => $request->br_code,
            'user_type' => $request->user_type,
        ]);

        return back()->with('success', 'User "' . $request->user_name . '" created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'user_name' => ['required', 'string', 'max:50', Rule::unique('users', 'user_name')->ignore($id)],
            'password'  => 'nullable|string|min:6|confirmed',
            'br_code'   => 'required|exists:branches,br_code',
            'user_type' => 'required|in:ADMIN,USER',
        ]);

        $data = [
            'user_name' => $request->user_name,
            'br_code'   => $request->br_code,
            'user_type' => $request->user_type,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        if ((int) $id === (int) session('user_id')) {
            return back()->with('error', 'Cannot delete currently logged-in user.');
        }

        User::findOrFail($id)->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        User::findOrFail($id)->update(['password' => $request->password]);

        return back()->with('success', 'Password reset successfully.');
    }

    // ── Change Password (all users) ──────────────────────────────────────────

    public function changePasswordForm()
    {
        return view('utilities.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'          => 'required|string',
            'password'                  => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail(session('user_id'));

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $user->update(['password' => $request->password]);

        return back()->with('success', 'Password changed successfully.');
    }
}
