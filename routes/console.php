
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send reminders for tasks due today at 8:00 AM.
Schedule::command('tasks:send-reminders --type=due_today')
    ->dailyAt('08:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

// Send reminders for overdue tasks at 9:00 AM.
Schedule::command('tasks:send-reminders --type=overdue')
    ->dailyAt('09:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

    // Generate recurring tasks every minute.
Schedule::command('app:generate-recurring-tasks')
    ->everyMinute()
    ->timezone('Asia/Manila')
    ->withoutOverlapping();
