<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function index()
    {
        return response()->json(Meeting::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required',
            'venue' => 'required|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'participants' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $meeting = Meeting::create($validated);

        return response()->json([
            'message' => 'Meeting created successfully',
            'meeting' => $meeting,
        ], 201);
    }

    public function show(string $id)
    {
        $meeting = Meeting::findOrFail($id);

        return response()->json($meeting);
    }

    public function update(Request $request, string $id)
    {
        $meeting = Meeting::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'time' => 'sometimes|required',
            'venue' => 'sometimes|required|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'participants' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $meeting->update($validated);

        return response()->json([
            'message' => 'Meeting updated successfully',
            'meeting' => $meeting,
        ]);
    }

    public function destroy(string $id)
    {
        $meeting = Meeting::findOrFail($id);

        $meeting->delete();

        return response()->json([
            'message' => 'Meeting deleted successfully',
        ]);
    }
}