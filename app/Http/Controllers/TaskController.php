<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Get all tasks.
     */
    public function index(Request $request)
    {
        $query = Task::query();

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
if ($request->filled('priority')) {
    $query->where('priority', $request->priority);
}

// Filter by category
if ($request->filled('category')) {
    $query->where('category', $request->category);
}

$tasks = $query->latest()->get();

return response()->json($tasks);
}

    /**
     * Create a new task.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
    'title' => 'required|string|max:150',
    'description' => 'nullable|string',
    'due_date' => 'nullable|date_format:Y-m-d H:i:s',
    'priority' => 'required|in:low,medium,high',
    'category' => 'required|in:School,Work,Personal,Health,Other',
    'goal_id' => 'nullable|exists:goals,id',
    'status' => 'nullable|in:pending,completed',

    'is_recurring' => 'sometimes|boolean',
    'recurrence_frequency' => 'nullable|required_if:is_recurring,1|in:daily,weekly,monthly,custom',
    'recurrence_interval' => 'sometimes|integer|min:1|max:365',
    'recurrence_end_date' => 'nullable|date',
]);

        if (!empty($validated['is_recurring']) && $validated['is_recurring']) {
    if (empty($validated['due_date'])) {
        return response()->json([
            'message' => 'A due date is required for recurring tasks.',
        ], 422);
    }

    $interval = max(1, (int) ($validated['recurrence_interval'] ?? 1));

    $firstDate = Carbon::createFromFormat(
        'Y-m-d H:i:s',
        $validated['due_date'],
        'Asia/Manila'
    );

    $nextDate = match ($validated['recurrence_frequency']) {
        'daily' => $firstDate->copy()->addDays($interval),
        'weekly' => $firstDate->copy()->addWeeks($interval),
        'monthly' => $firstDate->copy()->addMonthsNoOverflow($interval),
        'custom' => $firstDate->copy()->addDays($interval),
    };

    $validated['next_recurrence_date'] = $nextDate;

    if (!empty($validated['recurrence_end_date'])) {
        $validated['recurrence_end_date'] = Carbon::parse(
            $validated['recurrence_end_date'],
            'Asia/Manila'
        )->endOfDay();
    }
} else {
    $validated['is_recurring'] = false;
    $validated['recurrence_frequency'] = null;
    $validated['next_recurrence_date'] = null;
}

$task = Task::create($validated);

        return response()->json([
            'message' => 'Task created successfully.',
            'task' => $task,
        ], 201);
    }

    /**
     * Get one task.
     */
    public function show(Task $task)
    {
        return response()->json($task);
    }

    /**
     * Update a task.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
    'title' => 'required|string|max:150',
    'description' => 'nullable|string',
    'due_date' => 'nullable|date_format:Y-m-d H:i:s',
    'priority' => 'required|in:low,medium,high',
    'category' => 'required|in:School,Work,Personal,Health,Other',
    'goal_id' => 'nullable|exists:goals,id',
    'status' => 'required|in:pending,completed',

    'is_recurring' => 'sometimes|boolean',
    'recurrence_frequency' => 'nullable|required_if:is_recurring,1|in:daily,weekly,monthly,custom',
    'recurrence_interval' => 'sometimes|integer|min:1|max:365',
    'recurrence_end_date' => 'nullable|date',
]);

        $task->update($validated);

        return response()->json([
            'message' => 'Task updated successfully.',
            'task' => $task,
        ]);
    }

    /**
     * Delete a task.
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    /**
     * Toggle task status.
     */
    public function toggle(Task $task)
    {
        $task->update([
            'status' => $task->status === 'completed'
                ? 'pending'
                : 'completed',
        ]);

        return response()->json([
            'message' => 'Task status updated successfully.',
            'task' => $task,
        ]);
    }
}