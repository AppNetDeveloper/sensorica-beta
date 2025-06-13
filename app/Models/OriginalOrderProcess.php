<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OriginalOrderProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_order_id',
        'process_id',
        'created',
        'finished',
        'finished_at'
    ];

    protected $casts = [
        'created' => 'boolean',
        'finished' => 'boolean',
        'finished_at' => 'datetime'
    ];
    
    protected $dates = [
        'finished_at'
    ];

    public function originalOrder()
    {
        return $this->belongsTo(OriginalOrder::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
