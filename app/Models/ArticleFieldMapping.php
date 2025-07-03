<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleFieldMapping extends Model
{
    protected $table = 'article_field_mappings';
    
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

    /**
     * Relación con el cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Aplica las transformaciones al valor
     */
    public function applyTransformations($value)
    {
        return $this->applyTransformation($value);
    }
    
    /**
     * Aplica las transformaciones al valor (método original)
     */
    public function applyTransformation($value)
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
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                case 'number':
                    $value = (float) $value;
                    break;
                case 'to_boolean':
                    // Convertir varios formatos de "sí" a booleano 1/0
                    $trueValues = ['yes', 'y', 'true', '1', 'ok', 'si', 'sí', 'Si'];
                    $value = in_array(strtolower(trim($value)), $trueValues) ? 1 : 0;
                    break;
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
                case 'to_float':
                    $value = (float) $value;
                    break;
                case 'to_integer':
                    $value = (int) $value;
                    break;
                // Agregar más transformaciones según sea necesario
            }
        }

        return $value;
    }
}
