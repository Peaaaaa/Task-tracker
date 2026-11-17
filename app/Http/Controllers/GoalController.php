<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    /**
     * Display all goals with their progress.
     */
    public function index()
    {
        $goals = Goal::with(['milestones', 'tasks'])
            ->latest()
            ->get()
            ->map(function ($goal) {
                $totalMilestones = $goal->milestones->count();
                $completedMilestones = $goal->milestones
                    ->where('is_completed', true)
                    ->count();

                $totalTasks = $goal->tasks->count();
                $completedTasks = $goal->tasks
                    ->where('status', 'completed')
                    ->count();

                $totalItems = $totalMilestones + $totalTasks;
                $completedItems = $completedMilestones + $completedTasks;

                $goal->progress = $totalItems > 0
    ? (int) round(($completedItems / $totalItems) * 100)
    : 0;

// Automatically update goal status
if ($goal->status !== 'cancelled') {
    if ($goal->progress === 100) {
        $goal->status = 'completed';
    } elseif ($goal->progress > 0) {
        $goal->status = 'in_progress';
    } else {
        $goal->status = 'not_started';
    }

    Goal::where('id', $goal->id)->update([
    'status' => $goal->status,
]);
}

return $goal;
            });

        return response()->json($goals);
    }

    /**
     * Create a new goal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
            'status' => 'sometimes|in:not_started,in_progress,completed,cancelled',
        ]);

        $goal = Goal::create($validated);

        return response()->json([
            'message' => 'Goal created successfully.',
            'goal' => $goal,
        ], 201);
    }

    /**
     * Display one goal with its milestones and tasks.
     */
    public function show(Goal $goal)
    {
        $goal->load(['milestones', 'tasks']);

        $totalMilestones = $goal->milestones->count();
        $completedMilestones = $goal->milestones
            ->where('is_completed', true)
            ->count();

        $totalTasks = $goal->tasks->count();
        $completedTasks = $goal->tasks
            ->where('status', 'completed')
            ->count();

        $totalItems = $totalMilestones + $totalTasks;
        $completedItems = $completedMilestones + $completedTasks;

        $goal->progress = $totalItems > 0
            ? (int) round(($completedItems / $totalItems) * 100)
            : 0;

        return response()->json($goal);
    }

    /**
     * Update an existing goal.
     */
    public function update(Request $request, Goal $goal)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
            'status' => 'sometimes|in:not_started,in_progress,completed,cancelled',
        ]);

        $goal->update($validated);

        return response()->json([
            'message' => 'Goal updated successfully.',
            'goal' => $goal->fresh(),
        ]);
    }

    /**
     * Delete a goal.
     */
    public function destroy(Goal $goal)
    {
        $goal->delete();

        return response()->json([
            'message' => 'Goal deleted successfully.',
        ]);
    }
}
