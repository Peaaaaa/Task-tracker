<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    protected $fillable = [
        'goal_id',
        'title',
        'description',
        'target_date',
        'is_completed',
    ];

    protected $casts = [
        'target_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}