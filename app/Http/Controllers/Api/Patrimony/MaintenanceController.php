<?php

namespace App\Http\Controllers\Api\Patrimony;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\PreventiveSchedule;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    // Requests
    public function indexRequests(Request $request)
    {
        $query = MaintenanceRequest::with(['asset', 'location', 'requester', 'assignedTo']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }

    public function storeRequest(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'asset_id' => 'nullable|exists:assets,id',
            'location_id' => 'nullable|exists:locations,id',
            'requester_id' => 'nullable|exists:users,id', // Or members
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        // If not explicit requester, use auth user if applicable
        if (empty($validated['requester_id']) && auth()->check()) {
            $validated['requester_id'] = auth()->id();
        }

        $maintenance = MaintenanceRequest::create($validated);
        return response()->json($maintenance, 201);
    }

    public function showRequest(MaintenanceRequest $maintenance)
    {
        return response()->json($maintenance->load(['asset', 'location', 'requester', 'assignedTo']));
    }

    public function updateRequest(Request $request, MaintenanceRequest $maintenance)
    {
        $validated = $request->validate([
            'status' => 'in:open,analyzing,in_repair,done,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'cost' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'completed_at' => 'nullable|date',
        ]);

        $maintenance->update($validated);
        return response()->json($maintenance);
    }

    // Schedules
    public function indexSchedules()
    {
        return response()->json(PreventiveSchedule::with(['asset', 'location'])->get());
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'asset_id' => 'nullable|exists:assets,id',
            'location_id' => 'nullable|exists:locations,id',
            'frequency_days' => 'required|integer',
            'last_performed_at' => 'nullable|date',
            'next_due_date' => 'nullable|date',
        ]);
        
        $schedule = PreventiveSchedule::create($validated);
        return response()->json($schedule, 201);
    }
}
