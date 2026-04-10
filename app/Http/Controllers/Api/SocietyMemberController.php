<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SocietyMember;
use App\Models\Society;

class SocietyMemberController extends Controller
{
    public function index($societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('view', $society);

        $members = SocietyMember::where('society_id', $societyId)
            ->with('member')
            ->orderBy('status')
            ->get();
        return response()->json($members);
    }

    public function store(Request $request, $societyId)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'status' => 'required|in:active,cooperating,emeritus',
            'pact_date' => 'nullable|date'
        ]);

        $validated['society_id'] = $societyId;

        // Check if already exists
        $exists = SocietyMember::where('society_id', $societyId)
            ->where('member_id', $validated['member_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Membro já cadastrado nesta sociedade.'], 422);
        }

        $societyMember = SocietyMember::create($validated);

        return response()->json($societyMember, 201);
    }

    public function update(Request $request, $societyId, SocietyMember $societyMember)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        if ((int) $societyMember->society_id !== (int) $societyId) {
            return response()->json(['message' => 'Membro não pertence à sociedade informada.'], 403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:active,cooperating,emeritus',
            'pact_date' => 'nullable|date'
        ]);

        $societyMember->update($validated);

        return response()->json($societyMember);
    }

    public function destroy($societyId, SocietyMember $societyMember)
    {
        $society = Society::findOrFail($societyId);
        $this->authorize('update', $society);

        if ((int) $societyMember->society_id !== (int) $societyId) {
            return response()->json(['message' => 'Membro não pertence à sociedade informada.'], 403);
        }

        $societyMember->delete();
        return response()->noContent();
    }
}
