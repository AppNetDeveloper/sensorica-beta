<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'password',
        'pin',
        'email',
        'phone',
        'count_shift',
        'count_order',
        'active',
    ];
    // Hidden fields for security purposes
    protected $hidden = [
        'password'
    ];

    // Relationship with Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function operatorPosts()
    {
        return $this->hasMany(OperatorPost::class, 'operator_id', 'id');
    }
    public function shiftHistories()
    {
        return $this->hasMany(ShiftHistory::class, 'operator_id');
    }
    
    // Relación con los escaneos de códigos de barras
    public function barcodeScans()
    {
        return $this->hasMany(BarcodeScan::class);
    }
    
    /**
     * Obtiene las estadísticas de órdenes en las que ha trabajado este operador.
     */
    public function orderStats()
    {
        return $this->belongsToMany(OrderStat::class, 'order_stats_operators', 'operator_id', 'order_stat_id')
                    ->withPivot('shift_history_id', 'time_spent', 'notes')
                    ->withTimestamps();
    }
}
