<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceChecklistResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_id',
        'checklist_item_id',
        'checked',
        'notes',
    ];

    protected $casts = [
        'checked' => 'boolean',
    ];

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function checklistItem()
    {
        return $this->belongsTo(MaintenanceChecklistItem::class, 'checklist_item_id');
    }
}
