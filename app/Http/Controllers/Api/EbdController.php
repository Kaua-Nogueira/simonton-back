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
        }])
        ->with(['members' => function($q) {
            $q->wherePivot('role', 'teacher');
        }])
        ->get();

        return response()->json($classes);
    }

    public function stats()
    {
        $totalStudents = DB::table('class_enrollments')
            ->where('role', 'student')
            ->where('year', date('Y'))
            ->distinct('member_id')
            ->count();

        $activeClasses = SundaySchoolClass::count();
        
        $avgAttendance = AttendanceRecord::avg('present_count') ?? 0;
        
        // Calculate percentage based on total students
        $attendanceRate = $totalStudents > 0 ? ($avgAttendance / $totalStudents) * 100 : 0;

        return response()->json([
            'total_students' => $totalStudents,
            'active_classes' => $activeClasses,
            'avg_attendance_rate' => round($attendanceRate, 1)
        ]);
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
     * Store a newly created class.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $class = SundaySchoolClass::create($validated);
        return response()->json($class, 201);
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, $id)
    {
        $class = SundaySchoolClass::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $class->update($validated);
        return response()->json($class);
    }

    /**
     * Remove the specified class.
     */
    public function destroy($id)
    {
        $class = SundaySchoolClass::findOrFail($id);
        
        // Check if there are enrollments or attendance before deleting? 
        // For now, let's just delete or use soft deletes if available (BaseModel might handle it).
        $class->delete();
        
        return response()->json(['message' => 'Classe excluída com sucesso.']);
    }

    /**
     * Enroll a member in a class.
     */
    public function enroll(Request $request, $id)
    {
        $validated = $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:members,id',
            'role' => 'required|in:student,teacher,secretary',
            'year' => 'nullable|integer'
        ]);

        $year = $validated['year'] ?? date('Y');
        $insertedCount = 0;

        foreach ($validated['member_ids'] as $memberId) {
            // Check if already enrolled in this class for this year
            $exists = DB::table('class_enrollments')
                ->where('sunday_school_class_id', $id)
                ->where('member_id', $memberId)
                ->where('year', $year)
                ->exists();

            if (!$exists) {
                DB::table('class_enrollments')->insert([
                    'sunday_school_class_id' => $id,
                    'member_id' => $memberId,
                    'role' => $validated['role'],
                    'year' => $year,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertedCount++;
            }
        }

        return response()->json([
            'message' => "{$insertedCount} matrícula(s) realizada(s) com sucesso.",
            'inserted_count' => $insertedCount
        ]);
    }

    /**
     * Unenroll a member from a class.
     */
    public function unenroll(Request $request, $id, $memberId)
    {
        DB::table('class_enrollments')
            ->where('sunday_school_class_id', $id)
            ->where('member_id', $memberId)
            ->where('year', $request->input('year', date('Y')))
            ->delete();

        return response()->json(['message' => 'Membro removido da classe.']);
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
            'magazine_count' => 'required|integer|min:0',
            'teacher_id' => 'required|exists:members,id',
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
                'magazine_count' => $validated['magazine_count'],
                'teacher_id' => $validated['teacher_id'],
                'attendees' => $attendees,
            ]
        );

        return response()->json($record, 201);
    }
}
