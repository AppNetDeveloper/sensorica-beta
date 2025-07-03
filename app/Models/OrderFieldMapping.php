<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFieldMapping extends Model
{
    protected $fillable = [
        'customer_id',
        'source_field',
        'target_field',
        'transformations',
        'is_required'
    ];

    protected $casts = [
        'transformations' => 'array',
        'is_required' => 'boolean'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Aplica las transformaciones al valor
     */
    public function applyTransformations($value)
    {
        if (empty($this->transformations)) {
            return $value;
        }

        foreach ($this->transformations as $transformation) {
            switch ($transformation) {
                case 'trim':
                    $value = trim($value);
                    break;
                case 'uppercase':
                    $value = strtoupper($value);
                    break;
                case 'to_boolean':
                    // Convertir varios formatos de "sí" a booleano 1/0
                    $trueValues = ['yes', 'y', 'true', '1', 'ok', 'si', 'sí'];
                    $value = in_array(strtolower(trim($value)), $trueValues) ? 1 : 0;
                    break;
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                //anadimos date que formate la fecha que se recibe en diferentes formatos
                case 'date':
                    // Si es un string, intentamos convertirlo a timestamp
                    if (is_string($value)) {
                        // Intentar convertir el string a timestamp
                        $timestamp = strtotime($value);
                        if ($timestamp !== false) {
                            $value = date('Y-m-d', $timestamp);
                        }
                    } 
                    // Si ya es un entero (timestamp), lo formateamos directamente
                    else if (is_numeric($value)) {
                        $value = date('Y-m-d', (int)$value);
                    }
                    break;
                // Agregar más transformaciones según sea necesario
            }
        }

        return $value;
    }
}
