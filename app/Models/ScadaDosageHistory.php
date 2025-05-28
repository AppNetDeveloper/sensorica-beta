<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScadaDosageHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scada_dosage_history'; // Especificamos el nombre exacto de la tabla

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'operator_name',
        'orderId',
        'dosage_kg',
        'material_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dosage_kg' => 'decimal:3', // Coincide con la precisión definida en la migración
        'orderId' => 'string', // O 'string' si tu orderId no es numérico
        // 'created_at' => 'datetime', // Ya manejado por defecto si usas timestamps
        // 'updated_at' => 'datetime', // Ya manejado por defecto si usas timestamps
    ];

    // Aquí puedes definir relaciones en el futuro, por ejemplo:
    // public function order()
    // {
    //     return $this->belongsTo(Order::class, 'orderId');
    // }
}
