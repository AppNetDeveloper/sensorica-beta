<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiQueuePrint extends Model
{
    use HasFactory;

    protected $fillable = ['modbus_id', 'value', 'used', 'url_back', 'token_back'];

    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }
}
