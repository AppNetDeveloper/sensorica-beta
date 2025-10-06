<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarcodeScanAfter extends Model
{
    use HasFactory;

    protected $table = 'barcode_scans_after';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'barcode_scan_id',
        'production_order_id',
        'production_line_id',
        'barcoder_id',
        'original_order_id',
        'original_order_process_id',
        'order_id',
        'grupo_numero',
        'scanned_at',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scanned_at' => 'datetime',
        'meta' => 'array',
    ];

    public function barcodeScan()
    {
        return $this->belongsTo(BarcodeScan::class);
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function barcoder()
    {
        return $this->belongsTo(Barcode::class);
    }

    public function originalOrder()
    {
        return $this->belongsTo(OriginalOrder::class);
    }

    public function originalOrderProcess()
    {
        return $this->belongsTo(OriginalOrderProcess::class);
    }
}
