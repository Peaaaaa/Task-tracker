<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    protected $fillable = [
        'task_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'cloudinary_public_id',
        'cloudinary_url',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
