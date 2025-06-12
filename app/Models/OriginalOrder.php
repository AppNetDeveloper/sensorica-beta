<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OriginalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'client_number',
        'order_details',
        'processed'
    ];

    protected $casts = [
        'order_details' => 'json',
        'processed' => 'boolean'
    ];

    public function processes()
    {
        return $this->hasMany(OriginalOrderProcess::class);
    }
}
