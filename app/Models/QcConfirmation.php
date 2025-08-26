<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'production_order_id',
        'original_order_id',
        'operator_id',
        'notes',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function originalOrder()
    {
        return $this->belongsTo(OriginalOrder::class, 'original_order_id');
    }
}
