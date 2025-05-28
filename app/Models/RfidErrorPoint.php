<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidErrorPoint extends Model
{
    use HasFactory;

    /** 
     * Nombre de la tabla.
     * (Laravel por convención usaría rfid_error_points; 
     *  si tu tabla se llama diferente indícalo con protected $table)
     */
    // protected $table = 'rfid_error_points';

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'name',
        'value',
        'rfid_ant_name',
        'model_product',
        'order_id',
        'count_total',
        'count_total_1',
        'count_shift_1',
        'count_order_1',
        'time_11',
        'epc',
        'tid',
        'rssi',
        'serialno',
        'ant',
        'unic_code_order',
        'production_line_id',
        'product_lists_id',
        'operator_id',
        'operator_post_id',
        'rfid_detail_id',
        'rfid_reading_id',
        'note',
    ];

    /* -------------------------------------------------
     | Relaciones belongsTo
     |-------------------------------------------------*/

    /**
     * Línea de producción a la que pertenece el error.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Producto (product_lists) implicado.
     */
    public function productList()
    {
        // foreignKey explícita porque no es "product_list_id" sino "product_lists_id"
        return $this->belongsTo(ProductList::class, 'product_lists_id');
    }

    /**
     * Operario que estaba trabajando (opcional).
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Puesto / estación del operario en el momento del error (opcional).
     */
    public function operatorPost()
    {
        return $this->belongsTo(OperatorPost::class, 'operator_post_id');
    }

    /**
     * Detalle RFID asociado.
     */
    public function rfidDetail()
    {
        return $this->belongsTo(RfidDetail::class);
    }

    /**
     * Lectura RFID concreta donde se detectó el punto de error.
     */
    public function rfidReading()
    {
        return $this->belongsTo(RfidReading::class);
    }

    public function rfidColor()
    {
        // tabla: rfid_colors  |  PK: id  |  FK: rfid_color_id
        return $this->belongsTo(RfidColor::class, 'rfid_color_id');
    }
}
