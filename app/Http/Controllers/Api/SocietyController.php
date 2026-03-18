<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Society;

class SocietyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Society::class);
        
        $query = Society::withCount('members');

        // Filter by policy
        $societies = $query->get()->filter(function ($society) use ($request) {
            return $request->user()->can('view', $society);
        });

        return response()->json($societies->values());
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


    public function show(Society $society, Request $request)
    {
        $this->authorize('view', $society);

        // authorize('view', $society) is already handled by standard middleware or explicit call above.
        // We removed the redundant custom check to allow policy-based access (leaders).
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

        // Protection for system societies: don't allow changing name or abbreviation
        if ($society->is_system) {
            unset($validated['name']);
            unset($validated['abbreviation']);
        }

        $society->update($validated);

        return response()->json($society);
    }

    public function destroy(Society $society)
    {
        $this->authorize('delete', $society);

        if ($society->is_system) {
            return response()->json(['message' => 'Sociedades padrão da IPB não podem ser removidas.'], 403);
        }

        $society->delete();
        return response()->noContent();
    }

    public function globalReport(Request $request)
    {
        if (!$request->user()->can('societies.index') && !$request->user()->hasRole('Admin')) {
            return response()->json(['message' => 'Você não tem permissão para ver o relatório geral.'], 403);
        }

        $societies = Society::withCount('members')->get();
        
        $totalBalance = \App\Models\SocietyFinancialMovement::where('is_confirmed', true)->sum(DB::raw('CASE WHEN type = "income" THEN amount ELSE -amount END'));

        $upcomingActivities = \App\Models\SocietyActivity::where('date', '>=', date('Y-m-d'))
            ->with('society')
            ->orderBy('date')
            ->take(5)
            ->get();

        $obligationStats = \App\Models\SocietyObligation::selectRaw('status, count(*) as count, sum(amount) as total')
            ->groupBy('status')
            ->get();

        return response()->json([
            'societies' => $societies,
            'total_balance' => $totalBalance,
            'upcoming_activities' => $upcomingActivities,
            'obligations' => $obligationStats
        ]);
    }
}
