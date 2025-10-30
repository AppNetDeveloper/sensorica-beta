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

    /**
     * Calcula el número de días laborables entre dos fechas para un cliente.
     * Si no hay calendario configurado, usa lunes-viernes por defecto.
     *
     * @param int $customerId
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return int
     */
    public static function getWorkingDaysBetween($customerId, $start, $end)
    {
        if (!$start || !$end || $start->gt($end)) {
            return 0;
        }

        // Obtener todos los días del rango desde el calendario
        $calendarDays = static::forCustomer($customerId)
            ->betweenDates($start, $end)
            ->get()
            ->keyBy(fn($day) => $day->calendar_date->format('Y-m-d'));

        // Si no hay calendario configurado, usar lunes-viernes por defecto
        if ($calendarDays->isEmpty()) {
            return self::countWeekdaysBetween($start, $end);
        }

        // Contar días laborables configurados en el calendario
        $workingDays = 0;
        $current = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($current->lte($endDay)) {
            $dateKey = $current->format('Y-m-d');

            if (isset($calendarDays[$dateKey])) {
                // Si está en el calendario, usar el valor configurado
                if ($calendarDays[$dateKey]->is_working_day) {
                    $workingDays++;
                }
            } else {
                // Si no está en el calendario, asumir lunes-viernes como laborable
                if ($current->isWeekday()) {
                    $workingDays++;
                }
            }

            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calcula el número de días NO laborables entre dos fechas para un cliente.
     *
     * @param int $customerId
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return int
     */
    public static function getNonWorkingDaysBetween($customerId, $start, $end)
    {
        if (!$start || !$end || $start->gt($end)) {
            return 0;
        }

        $totalDays = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $workingDays = self::getWorkingDaysBetween($customerId, $start, $end);

        return $totalDays - $workingDays;
    }

    /**
     * Cuenta días de semana (lunes-viernes) entre dos fechas.
     * Método auxiliar cuando no hay calendario configurado.
     *
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return int
     */
    protected static function countWeekdaysBetween($start, $end)
    {
        $weekdays = 0;
        $current = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($current->lte($endDay)) {
            if ($current->isWeekday()) {
                $weekdays++;
            }
            $current->addDay();
        }

        return $weekdays;
    }

    /**
     * Obtiene un mapa de días laborables para un rango de fechas.
     * Útil para caché y optimización de múltiples consultas.
     *
     * @param int $customerId
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return array Array con formato ['Y-m-d' => true/false]
     */
    public static function getWorkingDaysMap($customerId, $start, $end)
    {
        if (!$start || !$end || $start->gt($end)) {
            return [];
        }

        // Obtener calendario configurado
        $calendarDays = static::forCustomer($customerId)
            ->betweenDates($start, $end)
            ->get()
            ->keyBy(fn($day) => $day->calendar_date->format('Y-m-d'));

        $map = [];
        $current = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($current->lte($endDay)) {
            $dateKey = $current->format('Y-m-d');

            if (isset($calendarDays[$dateKey])) {
                $map[$dateKey] = $calendarDays[$dateKey]->is_working_day;
            } else {
                // Fallback: lunes-viernes
                $map[$dateKey] = $current->isWeekday();
            }

            $current->addDay();
        }

        return $map;
    }
}
