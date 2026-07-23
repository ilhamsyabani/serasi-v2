<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();
        return view('internal.adminit.user.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('internal.adminit.user.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nip' => 'required|string|max:30|unique:users,nip',
            'nama' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8',
        ]);

        $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        User::create($data + ['is_aktif' => true]);

        return redirect()->route('internal.adminit.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('internal.adminit.user.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nip' => 'required|string|max:30|unique:users,nip,' . $user->id,
            'nama' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'is_aktif' => 'required|boolean',
            'password' => 'nullable|string|min:8',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $user->update($data);

        return redirect()->route('internal.adminit.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('internal.adminit.users.index')->with('success', 'User berhasil dihapus.');
    }
}
