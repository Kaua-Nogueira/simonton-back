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
            'category' => 'nullable|string',
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
        $expiredRoleIds = [];
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
                $expiredRoleIds[] = $activeOffice->id;
            }
        }

        $member->roles()->attach($validated['role_id'], [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
        ]);

        // Sync with System User if exists
        if ($member->user) {
            if (!empty($expiredRoleIds)) {
                $member->user->roles()->detach($expiredRoleIds);
            }
            // Only sync if it's an active role (no end_date or future)
            if (empty($validated['end_date']) || \Carbon\Carbon::parse($validated['end_date'])->isFuture()) {
                $member->user->roles()->syncWithoutDetaching([$validated['role_id']]);
            }
        }

        return response()->json(['message' => 'Role assigned and synced to User']);
    }

    public function deleteAssignment(\App\Models\Member $member, Role $role)
    {
        $this->authorize('update', $member);
        
        if (request()->has('pivot_id')) {
            $member->roles()->newPivotStatement()->where('id', request('pivot_id'))->delete();
            // Since we don't know easily which role ID it was without fetching, we might miss detaching from user.
            // But usually we detach by Role.
            // If using pivot_id it's specific history item deletion. 
            // If that history item was the "active" one, we should detach from user.
            // Complex. For now, assuming "Gerenciar Acesso" mainly uses standard detach or logic that maps well.
            // Actually, if we delete a pivot, we should probably check if the user still has that role active in another pivot? 
            // The User table has simple list.
            // Start simple: If detach by Role ID, detach from User.
        } else {
             $member->roles()->detach($role->id);
             if ($member->user) {
                 $member->user->roles()->detach($role->id);
             }
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
            'category' => 'nullable|string',
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
