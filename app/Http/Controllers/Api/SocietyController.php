<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Society;

class SocietyController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Society::class);
        return response()->json(Society::withCount('members')->get());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Society::class);
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
        $this->authorize('view', $society);
        // Stats: Members
        $society->loadCount(['members', 'activities']);
        $memberStats = [
            'active' => $society->members()->where('status', 'active')->count(),
            'cooperating' => $society->members()->where('status', 'cooperating')->count(),
            'emeritus' => $society->members()->where('status', 'emeritus')->count(),
        ];

        // Stats: Financial Balance
        $movements = \App\Models\SocietyFinancialMovement::where('society_id', $society->id)->get();
        $balance = $movements->where('type', 'income')->sum('amount') - $movements->where('type', 'expense')->sum('amount');

        // Stats: Leadership (Current President)
        $currentPres = null;
        $currentMandate = \App\Models\SocietyMandate::where('society_id', $society->id)
            ->where('year', date('Y'))
            ->with(['roles' => function($q) {
                $q->where('role_name', 'like', '%Presidente%')->with('member');
            }])
            ->first();
            
        if ($currentMandate) {
            $presRole = $currentMandate->roles->first();
            if ($presRole) {
                $currentPres = $presRole->member->name;
            }
        }

        return response()->json([
            'society' => $society,
            'stats' => [
                'members' => $memberStats,
                'balance' => $balance,
                'president' => $currentPres ?? 'Não definido',
                'current_year' => date('Y')
            ]
        ]);
    }

    public function update(Request $request, Society $society)
    {
        $this->authorize('update', $society);
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
        $this->authorize('delete', $society);
        $society->delete();
        return response()->noContent();
    }
}
