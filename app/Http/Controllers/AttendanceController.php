<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display all attendance records.
     */
    public function index()
    {
        $attendance = Attendance::with('meeting')->get();

        return response()->json($attendance);
    }

    /**
     * Store a new attendance record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'attendee_name' => 'required|string|max:255',
            'status' => 'required|in:present,absent',
        ]);

        $attendance = Attendance::create($validated);

        return response()->json([
            'message' => 'Attendance recorded successfully',
            'attendance' => $attendance,
        ], 201);
    }

    /**
     * Display one attendance record.
     */
    public function show(Attendance $attendance)
    {
        $attendance->load('meeting');

        return response()->json($attendance);
    }

    /**
     * Update an attendance record.
     */
    public function update(
        Request $request,
        Attendance $attendance
    ) {
        $validated = $request->validate([
            'meeting_id' => 'sometimes|exists:meetings,id',
            'attendee_name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:present,absent',
        ]);

        $attendance->update($validated);

        return response()->json([
            'message' => 'Attendance updated successfully',
            'attendance' => $attendance,
        ]);
    }

    /**
     * Delete an attendance record.
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json([
            'message' => 'Attendance deleted successfully',
        ]);
    }
}