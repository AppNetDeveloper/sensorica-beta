<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLineHourlyTotal extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'process_id',
        'total_time',
        'captured_at',
    ];

    protected $casts = [
        'total_time' => 'integer',
        'captured_at' => 'datetime',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
