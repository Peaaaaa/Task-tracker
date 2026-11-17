<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Milestone;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    /**
     * Display milestones belonging to a goal.
     */
    public function index(Goal $goal)
    {
        return response()->json(
            $goal->milestones()->latest()->get()
        );
    }

    /**
     * Create a milestone for a goal.
     */
    public function store(Request $request, Goal $goal)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
        ]);

        $milestone = $goal->milestones()->create($validated);

        return response()->json([
            'message' => 'Milestone created successfully.',
            'milestone' => $milestone,
        ], 201);
    }

    /**
     * Display one milestone.
     */
    public function show(Milestone $milestone)
    {
        return response()->json($milestone);
    }

    /**
     * Update a milestone.
     */
    public function update(Request $request, Milestone $milestone)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
            'is_completed' => 'sometimes|boolean',
        ]);

        $milestone->update($validated);

        return response()->json([
            'message' => 'Milestone updated successfully.',
            'milestone' => $milestone->fresh(),
        ]);
    }

    /**
     * Delete a milestone.
     */
    public function destroy(Milestone $milestone)
    {
        $milestone->delete();

        return response()->json([
            'message' => 'Milestone deleted successfully.',
        ]);
    }
}
