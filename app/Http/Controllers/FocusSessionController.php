<?php

namespace App\Http\Controllers;

use App\Models\FocusSession;
use Illuminate\Http\Request;

class FocusSessionController extends Controller
{
    // Get focus session history and statistics
    public function index()
    {
        $sessions = FocusSession::with('task')
            ->latest()
            ->get();

        $completedFocusSessions = FocusSession::where('session_type', 'focus')
            ->where('is_completed', true)
            ->count();

        return response()->json([
            'sessions' => $sessions,
            'completed_focus_sessions' => $completedFocusSessions,
        ]);
    }

    // Save a focus or break session
    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'nullable|exists:tasks,id',
            'duration_minutes' => 'required|integer|min:1|max:180',
            'session_type' => 'required|in:focus,break',
            'is_completed' => 'required|boolean',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);

        $session = FocusSession::create($validated);

        return response()->json([
            'message' => 'Focus session saved successfully.',
            'session' => $session->load('task'),
        ], 201);
    }
}