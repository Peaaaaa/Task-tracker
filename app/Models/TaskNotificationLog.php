<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskNotificationLog extends Model
{
    protected $fillable = [
        'task_id',
        'push_subscription_id',
        'notification_type',
        'notified_for',
        'sent_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_for' => 'date',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function pushSubscription(): BelongsTo
    {
        return $this->belongsTo(PushSubscription::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
