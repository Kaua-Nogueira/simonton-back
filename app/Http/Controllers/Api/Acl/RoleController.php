<?php

namespace App\Http\Controllers\Api\Acl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', \App\Models\Role::class);
        return \App\Models\Role::withCount(['users', 'permissions'])->get();
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $this->authorize('create', \App\Models\Role::class);
        $validated = $request->validate([
            'name' => 'required|string|unique:roles',
            'type' => 'required|string',
            'description' => 'nullable|string',
            'permissions' => 'array'
        ]);

        $role = \App\Models\Role::create($validated);
        
        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return response()->json($role, 201);
    }

    public function show($id)
    {
        $role = \App\Models\Role::with('permissions')->findOrFail($id);
        $this->authorize('view', $role);
        return $role;
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {
        $role = \App\Models\Role::findOrFail($id);
        $this->authorize('update', $role);
        
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'type' => 'required|string',
            'description' => 'nullable|string',
            'permissions' => 'array'
        ]);

        $role->update($validated);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return response()->json($role);
    }

    public function destroy($id)
    {
        $role = \App\Models\Role::findOrFail($id);
        $this->authorize('delete', $role);
        $role->delete();
        return response()->noContent();
    }
}
