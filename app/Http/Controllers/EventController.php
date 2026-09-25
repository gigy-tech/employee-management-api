<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display all events.
     */
    public function index()
    {
        return response()->json(Event::all());
    }

    /**
     * Create a new event.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required',
            'venue' => 'required|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event,
        ], 201);
    }

    /**
     * Display one event.
     */
    public function show(string $id)
    {
        $event = Event::findOrFail($id);

        return response()->json($event);
    }

    /**
     * Update an event.
     */
    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'time' => 'sometimes|required',
            'venue' => 'sometimes|required|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => $event,
        ]);
    }

    /**
     * Delete an event.
     */
    public function destroy(string $id)
    {
        $event = Event::findOrFail($id);

        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully',
        ]);
    }
}