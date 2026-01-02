<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function index()
    {
        $meetings = \App\Models\Meeting::withCount('resolutions')->orderBy('date', 'desc')->paginate(10);
        return response()->json($meetings);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'required',
            'location' => 'nullable|string',
            'type' => 'required|in:Ordinária,Extraordinária',
            'scope' => 'required|in:council,assembly',
        ]);

        $meeting = \App\Models\Meeting::create($validated);
        
        return response()->json($meeting, 201);
    }

    public function populateAttendance(\Illuminate\Http\Request $request, \App\Models\Meeting $meeting)
    {
        // Clear existing? Or just add missing? Let's just add missing to preserve status.
        // Logic:
        // if scope == council -> get Pastors and Presbyters
        // if scope == assembly -> get all active members

        $query = \App\Models\Member::where('status', 'active');

        if ($meeting->scope === 'council') {
            $query->whereHas('roles', function($q) {
                $q->whereIn('name', ['Pastor', 'Presbítero']);
            });
        }

        $members = $query->get();
        $count = 0;

        foreach ($members as $member) {
            $exists = $meeting->attendances()->where('member_id', $member->id)->exists();
            if (!$exists) {
                $meeting->attendances()->create([
                    'member_id' => $member->id,
                    'status' => 'Ausente', // Default
                    'role_name' => $member->roles->pluck('name')->join(', ') ?: 'Membro'
                ]);
                $count++;
            }
        }

        return response()->json([
            'message' => "Lista de presença atualizada. $count novos nomes adicionados.",
            'attendances' => $meeting->attendances()->with('member')->get()
        ]);
    }

    public function show(\App\Models\Meeting $meeting)
    {
        $meeting->load(['attendances.member', 'resolutions.responsible', 'documents', 'presidingOfficer', 'secretary']);
        return response()->json($meeting);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Meeting $meeting)
    {
        $validated = $request->validate([
            'date' => 'sometimes|date',
            'time' => 'sometimes',
            'location' => 'sometimes',
            'type' => 'sometimes|in:Ordinária,Extraordinária',
            'scope' => 'sometimes|in:council,assembly',
            'status' => 'sometimes|in:Rascunho,Finalizada',
            'opening_prayer' => 'nullable|string',
            'previous_minutes_reading' => 'nullable|string',
            'expedient' => 'nullable|string',
            'reports' => 'nullable|string',
            'closing_prayer' => 'nullable|string',
            'presiding_officer_id' => 'nullable|exists:members,id',
            'secretary_id' => 'nullable|exists:members,id',
        ]);

        $meeting->update($validated);

        return response()->json($meeting);
    }

    public function pdf(\App\Models\Meeting $meeting)
    {
        $meeting->load(['attendances.member', 'resolutions.responsible', 'presidingOfficer', 'secretary']);
        
        // Cast date to Carbon instance if not already (should be handled by casts in model, but ensuring)
        // In Model: protected $casts = ['date' => 'date'];
        
        return view('reports.meeting-minutes', compact('meeting'));
    }

    public function destroy(\App\Models\Meeting $meeting)
    {
        $meeting->delete();
        return response()->noContent();
    }
}
