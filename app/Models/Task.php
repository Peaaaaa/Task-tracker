<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Task extends Model
{
    protected $fillable = [
    'title',
    'description',
    'due_date',
    'priority',
    'category',
    'goal_id',
    'status',
    'is_recurring',
    'recurrence_frequency',
    'recurrence_interval',
    'recurrence_end_date',
    'next_recurrence_date',
    'recurrence_parent_id',
];

    protected $casts = [
    'due_date' => 'datetime',
    'is_recurring' => 'boolean',
    'recurrence_interval' => 'integer',
    'recurrence_end_date' => 'datetime',
    'next_recurrence_date' => 'datetime',
];

public function attachments(): HasMany
{
    return $this->hasMany(TaskAttachment::class);
}

public function goal(): BelongsTo
{
    return $this->belongsTo(Goal::class);
}

public function focusSessions(): HasMany
{
    return $this->hasMany(FocusSession::class);

}

}