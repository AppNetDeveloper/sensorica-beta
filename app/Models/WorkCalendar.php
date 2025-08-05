<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkCalendar extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'calendar_date',
        'name',
        'type',
        'is_working_day',
        'description',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'calendar_date' => 'date',
        'is_working_day' => 'boolean',
    ];

    /**
     * Obtiene el cliente asociado a este calendario laboral.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope para filtrar por cliente.
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope para filtrar por rango de fechas.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('calendar_date', [$startDate, $endDate]);
    }

    /**
     * Scope para filtrar por tipo de día.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para filtrar días laborables.
     */
    public function scopeWorkingDays($query)
    {
        return $query->where('is_working_day', true);
    }

    /**
     * Scope para filtrar días no laborables.
     */
    public function scopeNonWorkingDays($query)
    {
        return $query->where('is_working_day', false);
    }
}
