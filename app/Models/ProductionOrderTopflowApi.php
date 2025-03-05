<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderTopflowApi extends Model
{
    // Especifica el nombre de la tabla
    protected $table = 'production_orders_topflow_api';

    // Define _id como clave primaria, que es de tipo string y no autoincrementable
    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Campos asignables en masa
    protected $fillable = [
        '_id',
        'client_id',         // Reemplaza el antiguo "id"
        'customerOrderId',   // Número de Pedido del Cliente (opcional)
        'clientId',          // Referencia del Pedido del Cliente
        'code',              // Código de Barras / RFID
        'deliveryDate',      // Fecha de Expedición
        'referId',           // Referencia o Artículo
        'quantity',          // Cantidad
        'paletsQtty',        // Número de palets
    ];
}
