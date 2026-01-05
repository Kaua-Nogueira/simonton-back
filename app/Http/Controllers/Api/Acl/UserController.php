<?php

namespace App\Http\Controllers\Api\Acl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return \App\Models\User::with('roles')->paginate(20);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\Illuminate\Http\Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);
        
        $validated = $request->validate([
            'roles' => 'array',
            'permissions' => 'array' // Direct permissions
        ]);

        if (isset($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }
        
        if (isset($validated['permissions'])) {
            $user->permissions()->sync($validated['permissions']);
        }

        return response()->json($user->load(['roles', 'permissions']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
