<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SundaySchoolClass;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EbdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classes = SundaySchoolClass::withCount(['enrollments as students_count' => function ($query) {
            $query->where('role', 'student');
        }])->get();

        return response()->json($classes);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $class = SundaySchoolClass::with(['members' => function($query) {
            $query->orderBy('name');
        }])->findOrFail($id);

        return response()->json($class);
    }

    /**
     * Store attendance record.
     */
    public function storeAttendance(Request $request, $classId)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'visitors_count' => 'required|integer|min:0',
            'bible_count' => 'required|integer|min:0',
            'offering_amount' => 'required|numeric|min:0',
            'attendees' => 'array', // Array of member IDs
            'attendees.*' => 'exists:members,id',
        ]);

        $attendees = $validated['attendees'] ?? [];
        $presentCount = count($attendees);

        $record = AttendanceRecord::updateOrCreate(
            [
                'sunday_school_class_id' => $classId,
                'date' => $validated['date'],
            ],
            [
                'present_count' => $presentCount,
                'visitors_count' => $validated['visitors_count'],
                'bible_count' => $validated['bible_count'],
                'offering_amount' => $validated['offering_amount'],
                'attendees' => $attendees,
            ]
        );

        return response()->json($record, 201);
    }
}
