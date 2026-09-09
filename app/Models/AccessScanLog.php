<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log mentah tiap tap sidik jari dari `access_logs` — DICATAT SEMUA, termasuk
 * scan yang `employee_id`-nya tidak cocok NIP intern mana pun (matched = false).
 * Diisi oleh `attendance:sync`.
 */
class AccessScanLog extends Model
{
    protected $fillable = [
        'employee_id', 'intern_id', 'nip', 'matched',
        'scanned_at', 'scan_date', 'scan_time', 'device_name',
    ];

    protected $casts = [
        'matched' => 'boolean',
        'scanned_at' => 'datetime',
        'scan_date' => 'date',
    ];

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }
}
