<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    // Especificamos el nombre de la tabla
    protected $table = 'references';

    // La clave primaria es 'id', es de tipo string y no es autoincrementable
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Definición de los campos asignables en masa
    protected $fillable = [
        'id',
        'customerId',
        'families',
        'eanCode',
        'rfidCode',
        'description',
        'value',
        'magnitude',
        'measure',
        'envase',
        'tolerancia_min',
        'tolerancia_max',
    ];

    // Relación uno a muchos con GroupLevel
    public function groupLevels()
    {
        return $this->hasMany(GroupLevel::class, 'reference_id', 'id');
    }
}
