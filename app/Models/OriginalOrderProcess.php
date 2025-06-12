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
        'finished'
    ];

    protected $casts = [
        'created' => 'boolean',
        'finished' => 'boolean'
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
