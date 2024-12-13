<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScadaOrderList extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scada_order_list';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'scada_order_id',
        'process',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'process' => 'integer',
    ];

    /**
     * Relationship with the ScadaOrder model.
     *
     * Each ScadaOrderList entry belongs to a ScadaOrder.
     */
    public function processes()
    {
        return $this->hasMany(ScadaOrderListProcess::class, 'scada_order_list_id');
    }

    public function scadaOrder()
    {
        return $this->belongsTo(ScadaOrder::class, 'scada_order_id');
    }
}
