<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'name',
        'token',
        'mqtt_topic_barcodes',
        'mqtt_topic_orders',
        'mqtt_topic_finish',
        'mqtt_topic_pause',
        'mqtt_topic_shift',
        'machine_id',
        'ope_id',
        'order_notice',
        'last_barcode',
        'ip_zerotier',
        'user_ssh',
        'port_ssh',
        'ip_barcoder',
        'user_ssh_password',
        'port_barcoder'.
        'conexion_type',

    ];

    protected $hidden = [
        //'token',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
