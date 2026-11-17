<?php

namespace App\Console\Commands;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRecurringTasks extends Command
{
    protected $signature = 'app:generate-recurring-tasks';

    protected $description = 'Generate upcoming recurring tasks';

    public function handle(): int
    {
        $now = now('Asia/Manila');

        $recurringTasks = Task::query()
    ->where('is_recurring', true)
    ->whereNotNull('next_recurrence_date')
    ->get();

        foreach ($recurringTasks as $task) {
            DB::transaction(function () use ($task, $now) {
                $nextDate = Carbon::parse($task->next_recurrence_date)
                    ->setTimezone('Asia/Manila');

                $endDate = $task->recurrence_end_date
                    ? Carbon::parse($task->recurrence_end_date)->setTimezone('Asia/Manila')
                    : null;

                if ($endDate && $nextDate->gt($endDate)) {
                    $task->update([
                        'is_recurring' => false,
                        'next_recurrence_date' => null,
                    ]);

                    return;
                }

                if ($nextDate->gt($now)) {
    return;
}

                $newTask = Task::firstOrCreate(
                    [
                        'recurrence_parent_id' => $task->id,
                        'due_date' => $nextDate,
                    ],
                    [
                        'title' => $task->title,
                        'description' => $task->description,
                        'priority' => $task->priority,
                        'category' => $task->category,
                        'status' => 'pending',
                        'is_recurring' => false,
                    ]
                );

                $interval = max(1, (int) $task->recurrence_interval);

                $followingDate = match ($task->recurrence_frequency) {
                    'daily' => $nextDate->copy()->addDays($interval),
                    'weekly' => $nextDate->copy()->addWeeks($interval),
                    'monthly' => $nextDate->copy()->addMonthsNoOverflow($interval),
                    'custom' => $nextDate->copy()->addDays($interval),
                    default => null,
                };

                if (!$followingDate || ($endDate && $followingDate->gt($endDate))) {
                    $task->update([
                        'is_recurring' => false,
                        'next_recurrence_date' => null,
                    ]);
                } else {
                    $task->update([
                        'next_recurrence_date' => $followingDate,
                    ]);
                }

                if ($newTask->wasRecentlyCreated) {
                    $this->line("Generated task: {$newTask->title}");
                } else {
                    $this->line("Occurrence already exists: {$newTask->title}");
                }
            });
        }

        $this->info('Recurring task generation completed.');

        return self::SUCCESS;
    }
}