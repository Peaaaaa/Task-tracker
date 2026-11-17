<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FocusSession extends Model
{
    protected $fillable = [
        'task_id',
        'duration_minutes',
        'session_type',
        'is_completed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_completed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}