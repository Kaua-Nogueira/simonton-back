<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Society;

class SocietyController extends Controller
{
    public function index()
    {
        return response()->json(Society::withCount('members')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:10',
            'min_age' => 'nullable|integer',
            'max_age' => 'nullable|integer',
            'gender_restriction' => 'nullable|in:M,F',
            'description' => 'nullable|string'
        ]);

        $society = Society::create($validated);

        return response()->json($society, 201);
    }

    public function show(Society $society)
    {
        // Load basic dashboards stats
        $society->loadCount(['members', 'activities']);
        return response()->json($society);
    }

    public function update(Request $request, Society $society)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'abbreviation' => 'sometimes|string|max:10',
            'min_age' => 'nullable|integer',
            'max_age' => 'nullable|integer',
            'gender_restriction' => 'nullable|in:M,F',
            'description' => 'nullable|string'
        ]);

        $society->update($validated);

        return response()->json($society);
    }

    public function destroy(Society $society)
    {
        $society->delete();
        return response()->noContent();
    }
}
