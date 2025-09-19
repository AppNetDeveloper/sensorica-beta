<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCallbackMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'source_field',
        'target_field',
        'transformation',
        'is_required'
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Accessor para transformations (devuelve array desde string)
     */
    public function getTransformationsAttribute()
    {
        return $this->transformation ? explode(',', $this->transformation) : [];
    }

    /**
     * Relación con el cliente
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Aplica la transformación configurada al valor
     */
    public function applyTransformation($value)
    {
        if (empty($this->transformation) || is_null($value)) {
            return $value;
        }

        switch ($this->transformation) {
            case 'trim':
                return trim($value);
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'number':
                return is_numeric($value) ? (float)$value : $value;
            case 'date':
                return date('Y-m-d H:i:s', strtotime($value));
            case 'to_boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            default:
                return $value;
        }
    }
}
