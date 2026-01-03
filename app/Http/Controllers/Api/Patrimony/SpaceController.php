<?php

namespace App\Http\Controllers\Api\Patrimony;

use App\Http\Controllers\Controller;
use App\Models\SpaceBooking;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $query = SpaceBooking::with(['location', 'requester']);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_time', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->orderBy('start_time')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'requester_id' => 'nullable|exists:members,id', // Or users
            'event_name' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        // Check conflicts
        $conflict = SpaceBooking::where('location_id', $validated['location_id'])
            ->where('status', '!=', 'rejected')
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhere(function ($q2) use ($validated) {
                      $q2->where('start_time', '<=', $validated['start_time'])
                         ->where('end_time', '>=', $validated['end_time']);
                  });
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Conflito de horário nesse local.'], 422);
        }

        $booking = SpaceBooking::create(array_merge($validated, ['status' => 'pending']));
        return response()->json($booking, 201);
    }

    public function updateStatus(Request $request, SpaceBooking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,cancelled',
            'rejection_reason' => 'nullable|required_if:status,rejected|string',
        ]);

        $booking->update([
            'status' => $validated['status'],
            'approved_by' => auth()->id(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return response()->json($booking);
    }
}
