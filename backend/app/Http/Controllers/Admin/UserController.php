<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('identifier', 'like', "%{$request->search}%"))
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(25);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'identifier' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'identifier' => $data['identifier'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
        $user->roles()->sync($roleIds);

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'identifier' => ['nullable', 'string'],
            'account_status' => ['sometimes', 'in:active,suspended,disabled'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->update(collect($data)->except('roles')->toArray());

        if (isset($data['roles'])) {
            $user->roles()->sync(Role::whereIn('name', $data['roles'])->pluck('id'));
        }

        return response()->json($user->fresh('roles'));
    }
}