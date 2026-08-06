<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $roleFilter = $request->get('role');
        $statusFilter = $request->get('status');

        $query = User::with('role');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->where('role_id', $roleFilter);
        }

        if ($statusFilter !== '' && $statusFilter !== null) {
            $isAktif = $statusFilter === '1';
            $query->where('is_aktif', $isAktif);
        }

        $users = $query->latest()->paginate(10)->withQueryString();
        $roles = Role::all();

        return view('internal.adminit.user.index', compact('users', 'roles', 'search', 'roleFilter', 'statusFilter'));
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
            'no_whatsapp' => 'nullable|string|max:20|regex:/^[\d\s\-\+]+$/',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8',
        ]);

        $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        $data['is_aktif'] = true;
        User::create($data);

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
            'no_whatsapp' => 'nullable|string|max:20|regex:/^[\d\s\-\+]+$/',
            'role_id' => 'required|exists:roles,id',
            'is_aktif' => 'nullable|string',
            'password' => 'nullable|string|min:8',
        ]);

        $data['is_aktif'] = $request->has('is_aktif');

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
