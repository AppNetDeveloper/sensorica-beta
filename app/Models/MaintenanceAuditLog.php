<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_id',
        'user_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function logAction(Maintenance $maintenance, string $action, string $description = null, array $oldValues = null, array $newValues = null)
    {
        return self::create([
            'maintenance_id' => $maintenance->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
