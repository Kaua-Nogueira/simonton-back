<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        // Church roles usually public or just basic view?
        $this->authorize('viewAny', Role::class);
        return response()->json(Role::all()->groupBy('type'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Role::class);
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:office,function',
            'description' => 'nullable|string'
        ]);

        $role = Role::create($validated);
        return response()->json($role, 201);
    }

    // Assign a role to a member
    public function assignRole(Request $request, \App\Models\Member $member)
    {
        // Check permission to update member
        $this->authorize('update', $member);
        
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $newRole = Role::findOrFail($validated['role_id']);

        // Check if ALREADY has this specific role active -> Error (Duplicate)
        $alreadyAssigned = $member->roles()
            ->where('role_id', $validated['role_id'])
            ->where(function ($query) {
                $query->whereNull('member_role.end_date')
                      ->orWhere('member_role.end_date', '>', now());
            })
            ->exists();

        if ($alreadyAssigned) {
            return response()->json(['message' => 'O membro já possui este papel ativo.'], 422);
        }

        // Exclusive Office Logic
        if ($newRole->type === 'office') {
            // Find any other active OFFICE and finalize it
            $activeOffices = $member->roles()
                ->where('type', 'office')
                ->where(function ($query) {
                    $query->whereNull('member_role.end_date')
                          ->orWhere('member_role.end_date', '>', now());
                })
                ->get();

            foreach ($activeOffices as $activeOffice) {
                // Set end_date to the day before the new start date, or same day?
                // detailed requirement: "então quando um oficio for adicionado, o outro tem que ser finalizado"
                // Let's set end_date = start_date of new role.
                $member->roles()->updateExistingPivot($activeOffice->id, [
                    'end_date' => $validated['start_date']
                ]);
            }
        }

        $member->roles()->attach($validated['role_id'], [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
        ]);

        return response()->json(['message' => 'Role assigned successfully']);
    }

    public function deleteAssignment(\App\Models\Member $member, Role $role)
    {
        $this->authorize('update', $member);
        
        if (request()->has('pivot_id')) {
            $member->roles()->newPivotStatement()->where('id', request('pivot_id'))->delete();
        } else {
             $member->roles()->detach($role->id);
        }

        return response()->json(['message' => 'Assignment removed']);
    }
    
    public function getHistory(\App\Models\Member $member)
    {
        $this->authorize('view', $member); // Viewing member details including roles
        // Return roles loaded with pivots
        return response()->json($member->roles);
    }
    
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:office,function',
            'description' => 'nullable|string'
        ]);

        $role->update($validated);
        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);
        // Check if role is used?
        // For now, let's allow deletion. The constraint foreign key on member_role is 'cascade' 
        // as per migration: $table->foreignId('role_id')->constrained()->onDelete('cascade');
        // So this will remove all assignments history too.
        $role->delete();
        return response()->json(null, 204);
    }
}
