<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SocietyMandate;
use App\Models\MandateRole;
use Illuminate\Support\Facades\DB;

class SocietyMandateController extends Controller
{
    public function index($societyId)
    {
        $mandates = SocietyMandate::where('society_id', $societyId)
            ->with(['roles.member'])
            ->orderBy('year', 'desc')
            ->get();
        return response()->json($mandates);
    }

    public function store(Request $request, $societyId)
    {
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
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'role_name' => 'required|string', // Presidente, Vice, Sec. de Missões...
            'role_type' => 'required|in:board,cause'
        ]);

        $validated['mandate_id'] = $mandateId;

        $role = MandateRole::create($validated);
        return response()->json($role, 201);
    }

    public function removeRole($societyId, $mandateId, $roleId)
    {
        MandateRole::destroy($roleId);
        return response()->noContent();
    }
}
