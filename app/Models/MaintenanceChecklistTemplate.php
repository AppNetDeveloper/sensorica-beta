<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceChecklistTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'production_line_id',
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function items()
    {
        return $this->hasMany(MaintenanceChecklistItem::class, 'template_id')->orderBy('sort_order');
    }
}
