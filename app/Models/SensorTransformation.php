<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorTransformation extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'production_line_id',
        'min_value',
        'mid_value',
        'max_value',
        'input_topic',
        'output_topic',
        'active',
        'name',
        'description',
        'below_min_value_output',
        'min_to_mid_value_output',
        'mid_to_max_value_output',
        'above_max_value_output',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_value' => 'float',
        'mid_value' => 'float',
        'max_value' => 'float',
        'active' => 'boolean',
    ];

    /**
     * Obtiene la línea de producción asociada a esta transformación.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
