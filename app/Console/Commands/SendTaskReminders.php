<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\Task;
use App\Models\TaskNotificationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders {--type=all : due_today, overdue, or all}';

    protected $description = 'Send daily reminders for tasks due today and overdue tasks';

    public function handle(): int
    {
        $type = $this->option('type');

        if (! in_array($type, ['all', 'due_today', 'overdue'], true)) {
            $this->error('Invalid type. Use all, due_today, or overdue.');

            return self::FAILURE;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ];

        if (
            empty($auth['VAPID']['subject']) ||
            empty($auth['VAPID']['publicKey']) ||
            empty($auth['VAPID']['privateKey'])
        ) {
            $this->error('Web Push VAPID configuration is incomplete.');

            return self::FAILURE;
        }

        $webPush = new WebPush($auth);

        $today = Carbon::today(config('app.timezone'))->toDateString();

        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            $this->warn('No browser subscriptions found.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach (['due_today', 'overdue'] as $notificationType) {
            if ($type !== 'all' && $type !== $notificationType) {
                continue;
            }

            $tasks = Task::query()
                ->where('status', 'pending')
                ->when(
                    $notificationType === 'due_today',
                    fn ($query) => $query->whereDate('due_date', $today)
                )
                ->when(
                    $notificationType === 'overdue',
                    fn ($query) => $query->whereDate('due_date', '<', $today)
                )
                ->get();

            foreach ($tasks as $task) {
                foreach ($subscriptions as $browserSubscription) {
                    $alreadySent = TaskNotificationLog::query()
                        ->where('task_id', $task->id)
                        ->where('push_subscription_id', $browserSubscription->id)
                        ->where('notification_type', $notificationType)
                        ->whereDate('notified_for', $today)
                        ->whereNotNull('sent_at')
                        ->exists();

                    if ($alreadySent) {
                        $skipped++;
                        continue;
                    }

                    $payload = [
                        'title' => $notificationType === 'due_today'
                            ? 'Task Due Today'
                            : 'Overdue Task Reminder',
                        'body' => $notificationType === 'due_today'
                            ? $task->title . ' is due today.'
                            : $task->title . ' is overdue.',
                        'url' => '/',
                    ];

                    try {
                        $subscription = Subscription::create([
                            'endpoint' => $browserSubscription->endpoint,
                            'publicKey' => $browserSubscription->public_key,
                            'authToken' => $browserSubscription->auth_token,
                            'contentEncoding' => $browserSubscription->content_encoding,
                        ]);

                        $report = $webPush->sendOneNotification(
                            $subscription,
                            json_encode($payload, JSON_THROW_ON_ERROR)
                        );

                        if ($report->isSuccess()) {
                            TaskNotificationLog::updateOrCreate(
                                [
                                    'task_id' => $task->id,
                                    'push_subscription_id' => $browserSubscription->id,
                                    'notification_type' => $notificationType,
                                    'notified_for' => $today,
                                ],
                                [
                                    'sent_at' => now(),
                                ]
                            );

                            $sent++;
                        } else {
                            $failed++;

                            $this->warn(
                                'Delivery failed for task #' . $task->id .
                                ': ' . $report->getReason()
                            );

                            if ($report->isSubscriptionExpired()) {
                                $browserSubscription->delete();
                                $this->warn('Expired browser subscription removed.');
                            }
                        }
                    } catch (Throwable $exception) {
                        $failed++;

                        $this->error(
                            'Error for task #' . $task->id .
                            ': ' . $exception->getMessage()
                        );
                    }
                }
            }
        }

        $this->info("Reminders sent: {$sent}");
        $this->info("Already sent/skipped: {$skipped}");
        $this->info("Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
