<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityIssue extends Model
{
    use HasFactory;

    protected $table = 'quality_issues';

    protected $fillable = [
        'production_line_id',
        'production_order_id',
        'original_order_id',
        'original_order_id_qc',
        'operator_id',
        'texto',
    ];

    /**
     * Production order related to the quality issue.
     */
    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    /**
     * Original order linked to the issue (source order).
     */
    public function originalOrder()
    {
        return $this->belongsTo(OriginalOrder::class, 'original_order_id');
    }

    /**
     * Duplicated QC original order created to handle the issue.
     */
    public function originalOrderQc()
    {
        return $this->belongsTo(OriginalOrder::class, 'original_order_id_qc');
    }

    /**
     * Production line where the issue occurred.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'production_line_id');
    }

    /**
     * Operator who reported the issue.
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }
}
