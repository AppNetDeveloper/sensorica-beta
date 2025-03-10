<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class HostList extends Model
{
    use HasFactory;

    protected $fillable = ['host', 'token', 'name', 'user_id', 'emails', 'phones', 'telegrams'];

    public function hostMonitors()
    {
        return $this->hasMany(HostMonitor::class, 'id_host');
    }
}
