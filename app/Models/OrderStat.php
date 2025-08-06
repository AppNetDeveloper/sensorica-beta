<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStat extends Model
{
    use HasFactory;

    // Especifica la tabla asociada a este modelo.
    protected $table = 'order_stats';

    // Permite la asignación masiva de estos campos.
    protected $fillable = [
        'product_list_id', //Id de la product_list 
        'production_line_id', //Id de la linea de produccion
        'order_id', //Id de la orden de produccion
        'box', //Numero de caja
        'units_box', //Unidades por caja
        'units', //Unidades totales
        'units_per_minute_real', //Unidades por minuto real
        'units_per_minute_theoretical', //Unidades por minuto teorico
        'units_made_real', //Unidades hechas real
        'seconds_per_unit_real', //Segundos por unidad real
        'seconds_per_unit_theoretical', //Segundos por unidad teorico
        'units_made_real', //Unidades hechas real
        'units_made_theoretical', //Unidades hechas teorico
        'sensor_stops_count', //Contador de paros de sensor
        'sensor_stops_time', //Tiempo de paros de sensor
        'production_stops_time', //Tiempo de paros de produccion
        'units_made', //Unidades hechas
        'units_pending', //Unidades pendientes
        'units_delayed', //Unidades retrasadas
        'slow_time', //Tiempo lento
        'fast_time', //Tiempo rapido
        'out_time', //Tiempo fuera del tiempo de production previsto
        'theoretical_end_time', //Tiempo teorico de finalizacion
        'real_end_time', //Tiempo real de finalizacion
        'oee', //OEE
        'oee_sensors', //OEE de sensores
        'oee_modbus', //OEE de modbus
        'oee_rfid', //OEE de rfid
        'weights_0_shiftNumber', //Numero de turno
        'weights_0_shiftKg', //Kg de turno
        'weights_0_orderNumber', //Numero de orden
        'weights_0_orderKg', //Kg de orden
        'weights_1_shiftNumber', //Numero de turno
        'weights_1_shiftKg', //Kg de turno
        'weights_1_orderNumber', //Numero de orden
        'weights_1_orderKg', //Kg de orden
        'weights_2_shiftNumber', //Numero de turno
        'weights_2_shiftKg', //Kg de turno
        'weights_2_orderNumber', //Numero de orden
        'weights_2_orderKg', //Kg de orden
        'weights_3_shiftNumber', //Numero de turno
        'weights_3_shiftKg', //Kg de turno
        'weights_3_orderNumber', //Numero de orden
        'weights_3_orderKg', //Kg de orden
        'down_time', //Tiempo de paro
        'prepair_time' //Tiempo de reparacion
    ];

    // Asegúrate de que se gestionen las marcas de tiempo automáticamente.
    public $timestamps = true;
    // Relación con ProductionLine
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'production_line_id');
    }

    // Relación con ProductList
    public function productList()
    {
        return $this->belongsTo(ProductList::class, 'product_list_id');
    }
    
    /**
     * Obtiene los operadores que han trabajado en esta orden a través de la tabla pivot.
     */
    public function operators()
    {
        return $this->belongsToMany(Operator::class, 'order_stats_operators', 'order_stat_id', 'operator_id')
                    ->withPivot('shift_history_id', 'time_spent', 'notes')
                    ->withTimestamps();
    }
    
    /**
     * Obtiene los registros de turnos asociados a esta orden a través de la tabla pivot.
     */
    public function shiftHistories()
    {
        return $this->belongsToMany(ShiftHistory::class, 'order_stats_operators', 'order_stat_id', 'shift_history_id')
                    ->withPivot('operator_id', 'time_spent', 'notes')
                    ->withTimestamps();
    }
    
    /**
     * Obtiene las relaciones directas con la tabla order_stats_operators.
     */
    public function orderStatOperators()
    {
        return $this->hasMany(OrderStatOperator::class);
    }
}
