<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'name',
        'token',
    ];

    /**
     * Los procesos asociados a esta línea de producción.
     */
    public function processes()
    {
        return $this->belongsToMany(Process::class, 'production_line_process')
            ->withPivot('order')
            ->orderBy('production_line_process.order');
    }

    /**
     * Los artículos asociados a esta línea de producción.
     */
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'production_line_article')
            ->withPivot('order')
            ->orderBy('production_line_article.order');
    }

    /**
     * Los atributos que deberían estar ocultos para los arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        //'token', // Ocultamos el token por seguridad
    ];

    // Define la relación con la tabla customers
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function barcodes()
    {
        return $this->hasMany(Barcode::class);
    }
    // Relación inversa con Sensor
    public function sensors()
    {
        return $this->hasMany(Sensor::class, 'production_line_id');
    }
    // Relación inversa con OrderStat (opcional)
    public function orderStats()
    {
        return $this->hasMany(OrderStat::class, 'production_line_id');
    }
    // En ProductionLine.php
    public function lastShiftHistory()
    {
        return $this->hasOne(ShiftHistory::class)->latest();
    }
    
    // Relación con los escaneos de códigos de barras
    public function barcodeScans()
    {
        return $this->hasMany(BarcodeScan::class);
    }

}
