<?php

// app/Models/HostMonitor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HostMonitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_host', 'total_memory', 'memory_free', 'memory_used',
        'memory_used_percent', 'disk', 'cpu'
    ];

    public function hostList()
    {
        return $this->belongsTo(HostList::class, 'id_host');
    }
}
