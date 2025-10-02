<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'description',
        'required',
        'sort_order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(MaintenanceChecklistTemplate::class, 'template_id');
    }

    public function responses()
    {
        return $this->hasMany(MaintenanceChecklistResponse::class, 'checklist_item_id');
    }
}
