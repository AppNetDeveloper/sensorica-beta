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
        'transformation',
    ];

    protected $casts = [
        'transformation' => 'array',
    ];

    /**
     * Relación con el cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Aplica la transformación al valor si existe
     */
    public function applyTransformation($value)
    {
        if (!$this->transformation) {
            return $value;
        }

        $transformation = $this->transformation;

        // Aplicar multiplicador si existe
        if (isset($transformation['multiplier'])) {
            $value = floatval($value) * floatval($transformation['multiplier']);
        }

        // Aplicar prefijo si existe
        if (isset($transformation['prefix'])) {
            $value = $transformation['prefix'] . $value;
        }

        // Aplicar sufijo si existe
        if (isset($transformation['suffix'])) {
            $value = $value . $transformation['suffix'];
        }

        // Aplicar formato si existe
        if (isset($transformation['format'])) {
            switch ($transformation['format']) {
                case 'uppercase':
                    $value = strtoupper($value);
                    break;
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                case 'trim':
                    $value = trim($value);
                    break;
            }
        }

        return $value;
    }
}
