<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SocietyMandate;
use App\Models\MandateRole;
use App\Models\Society;
use Illuminate\Support\Facades\DB;

class SocietyMandateController extends Controller
{
    public function index($societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('view', $society);

        $mandates = SocietyMandate::where('society_id', $societyId)
            ->with(['roles.member'])
            ->orderBy('year', 'desc')
            ->get();
        return response()->json($mandates);
    }

    public function store(Request $request, $societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $validated = $request->validate([
            'year' => 'required|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $validated['society_id'] = $societyId;

        $mandate = SocietyMandate::create($validated);
        return response()->json($mandate, 201);
    }

    public function addRole(Request $request, $societyId, $mandateId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $mandate = SocietyMandate::where('society_id', $societyId)->findOrFail($mandateId);

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'role_name' => 'required|string',
            'role_type' => 'required|in:board,cause'
        ]);

        $validated['mandate_id'] = $mandate->id;

        $role = MandateRole::create($validated);
        return response()->json($role, 201);
    }

    public function batchAddRoles(Request $request, $societyId, $mandateId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $mandate = SocietyMandate::where('society_id', $societyId)->findOrFail($mandateId);

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*.member_id' => 'required|exists:members,id',
            'roles.*.role_name' => 'required|string',
            'roles.*.role_type' => 'required|in:board,cause'
        ]);

        $created = [];
        DB::transaction(function () use ($validated, $mandate, &$created) {
            foreach ($validated['roles'] as $roleData) {
                $roleData['mandate_id'] = $mandate->id;
                $created[] = MandateRole::create($roleData);
            }
        });

        return response()->json($created, 201);
    }

    public function removeRole($societyId, $mandateId, $roleId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $mandate = SocietyMandate::where('society_id', $societyId)->findOrFail($mandateId);
        $role = MandateRole::where('mandate_id', $mandate->id)->findOrFail($roleId);
        $role->delete();

        return response()->noContent();
    }
}
