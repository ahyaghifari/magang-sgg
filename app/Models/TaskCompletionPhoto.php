<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCompletionPhoto extends Model
{
    protected $fillable = [
        'task_id',
        'path',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
