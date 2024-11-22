<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorConnectionStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_connection_id',
        'status',
    ];

    /**
     * Relación con MonitorConnection.
     */
    public function monitorConnection()
    {
        return $this->belongsTo(MonitorConnection::class);
    }
}
