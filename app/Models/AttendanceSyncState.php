<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Watermark incremental untuk `attendance:sync` — satu baris saja.
 */
class AttendanceSyncState extends Model
{
    protected $fillable = ['last_synced_at'];

    protected $casts = ['last_synced_at' => 'datetime'];

    /** Singleton watermark. */
    public static function singleton(): self
    {
        return static::firstOrCreate([]);
    }
}
