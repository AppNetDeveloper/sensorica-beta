<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupLevel extends Model
{
    // Definición de los campos asignables en masa
    protected $fillable = [
        'reference_id',
        'id_group',
        'level',
        'uds',
        'total',
        'measure',
        'eanCode',
        'envase',
    ];

    // Relación inversa: cada GroupLevel pertenece a una Reference
    public function reference()
    {
        return $this->belongsTo(Reference::class, 'reference_id', 'id');
    }
}
