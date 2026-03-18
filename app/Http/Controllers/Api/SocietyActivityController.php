<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocietyActivity;

class SocietyActivityController extends Controller
{
    public function index($societyId)
    {
        return response()->json(SocietyActivity::where('society_id', $societyId)->get());
    }

    public function store(Request $request, $societyId)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'date' => 'required|date',
            'time' => 'nullable',
            'type' => 'required|string',
            'description' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
            'estimated_revenue' => 'nullable|numeric',
        ]);

        $validated['society_id'] = $societyId;
        $validated['estimated_cost'] = $validated['estimated_cost'] ?? 0;
        $validated['estimated_revenue'] = $validated['estimated_revenue'] ?? 0;

        return response()->json(SocietyActivity::create($validated), 201);
    }

    public function update(Request $request, $societyId, $activityId)
    {
        $activity = SocietyActivity::where('society_id', $societyId)->findOrFail($activityId);
        
        $validated = $request->validate([
            'title' => 'sometimes|string',
            'date' => 'sometimes|date',
            'time' => 'nullable',
            'type' => 'sometimes|string',
            'description' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
            'estimated_revenue' => 'nullable|numeric',
        ]);

        $activity->update($validated);
        return response()->json($activity);
    }
}
