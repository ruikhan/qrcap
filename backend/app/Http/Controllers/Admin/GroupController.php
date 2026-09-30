<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        return Group::withCount('members')->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));
    }

    public function show(Group $group)
    {
        return response()->json($group->load('members:id,name,email,identifier')->loadCount('members'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:groups,name',
            'description' => 'nullable|string|max:1000',
            'member_ids' => 'array',
            'member_ids.*' => 'integer|exists:users,id',
        ]);

        $group = Group::create(collect($data)->only(['name', 'description'])->all());
        $group->members()->sync($data['member_ids'] ?? []);

        return response()->json($group->loadCount('members'), 201);
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:groups,name,' . $group->id,
            'description' => 'nullable|string|max:1000',
            'member_ids' => 'array',
            'member_ids.*' => 'integer|exists:users,id',
        ]);

        $group->update(collect($data)->only(['name', 'description'])->all());
        if (array_key_exists('member_ids', $data)) {
            $group->members()->sync($data['member_ids']);
        }

        return $group->loadCount('members');
    }
}