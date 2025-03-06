<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierOrderReference extends Model
{
    protected $table = 'supplier_order_references';
    
    // Indicamos que la clave primaria es "id", no autoincrementable y de tipo string.
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_name',
        'descrip',
        'value',
        'measure'
    ];

    public function supplierOrders()
    {
        return $this->hasMany(SupplierOrder::class, 'refer_id', 'id');
    }
}
